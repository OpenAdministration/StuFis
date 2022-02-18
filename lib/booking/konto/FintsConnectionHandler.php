<?php

namespace booking\konto;

use Composer\InstalledVersions;
use DateTime;
use Fhp\Action\GetSEPAAccounts;
use Fhp\Action\GetStatementOfAccount;
use Fhp\BaseAction;
use Fhp\CurlException;
use Fhp\FinTs;
use Fhp\Model\SEPAAccount;
use Fhp\Model\StatementOfAccount\StatementOfAccount;
use Fhp\Model\TanMode;
use Fhp\Options\Credentials;
use Fhp\Options\FinTsOptions;
use Fhp\Protocol\DialogInitialization;
use Fhp\Protocol\ServerException;
use Fhp\Protocol\UnexpectedResponseException;
use framework\DBConnector;
use framework\render\ErrorHandler;
use framework\render\html\BT;
use framework\render\HTMLPageRenderer;
use InvalidArgumentException;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class FintsConnectionHandler
{
    private FinTs $finTs;

    private ?BaseAction $activeAction;

    private Logger $logger;

    /**
     * FintsConnectionHandler2 constructor.
     * @param int $credentialId
     * @param FinTsOptions $options
     * @param Credentials $credentials
     * @param int|null $tanModeInt
     * @param string|null $tanMediumName
     */
    public function __construct(
        private int $credentialId,
        FinTsOptions $options,
        Credentials $credentials,
        ?int $tanModeInt = null,
        ?string $tanMediumName = null
    ) {
        $persist = $this->getCache('persist');
        $this->finTs = FinTs::new($options, $credentials, $persist);

        if (!is_null($tanModeInt)) {
            $this->finTs->selectTanMode($tanModeInt, $tanMediumName);
        }

        $this->logger = new Logger('fints', [
            new RotatingFileHandler(SYSBASE . '/runtime/logs/fints.log'),
        ]);
        $this->logger->info('FINTS created', ['credentialId' => $this->credentialId]);

        if (DEV) {
            $this->finTs->setLogger($this->logger);
        }
    }

    public static function saveCredentials(mixed $bankId, mixed $bankuser, mixed $name): int
    {
        $db = DBConnector::getInstance();
        return (int) $db->dbInsert('konto_credentials', [
           'bank_id' => $bankId,
           'owner_id' => $db->getUser()['id'],
           'bank_username' => $bankuser,
           'name' => $name,
        ]);
    }

    /**
     * try to login. If wrong credentials, delete saved pw and add Flash to PageRenderer
     * @return bool $success login
     * @throws NeedsTanException
     */
    public function login(): bool
    {
        // resume execution if any
        $resumableAction = $this->resumableAction();
        if ($resumableAction instanceof DialogInitialization) {
            if (!$resumableAction->isDone()) {
                throw new NeedsTanException($resumableAction, 'Tan wird zum Login benötigt');
            }
            $this->setCache('logged-in', true);
            $this->save();
            return true;
        }
        // regular execution
        try {
            $this->logger->info('Start login', ['credId' => $this->credentialId]);
            if ($this->finTs->getSelectedTanMode() === null) {
                HTMLPageRenderer::addFlash(BT::TYPE_INFO, 'Vor dem ersten Login muss der TAN Modus gesetzt werden');
                HTMLPageRenderer::redirect(URIBASE . 'konto/credentials/' . $this->credentialId . '/tan-mode');
            }
            $loginAction = $this->finTs->login();
            $this->save($loginAction);
            if ($loginAction->needsTan()) {
                $this->logger->info('Login needs TAN', ['credId' => $this->credentialId]);
                throw new NeedsTanException(
                    $loginAction,
                    'Tan wird zum Login benötigt'
                );
            }
            HTMLPageRenderer::addFlash(BT::TYPE_SUCCESS, 'Login erfolgreich');
            $this->setCache('logged-in', true);
            return true;
        } catch (CurlException  $e) {
            ErrorHandler::handleException($e, 'Kann keine Verbindung zum Bank Server aufbauen');
        } catch (ServerException|UnexpectedResponseException $e) {
            $this->setCache('logged-in', null);
            static::deleteLoginPassword($this->credentialId);
            HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'Login nicht erfolgreich, bitte überprüfe die Login Daten', $e->getMessage());
            return false;
        }
    }

    public function logout(): bool
    {
        try {
            $this->logger->info('Logout', ['credId' => $this->credentialId]);
            $this->finTs->close(); // logout @ server
        } catch (ServerException $e) {
            HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'Logout fehlgeschlagen', $e->getMessage());
            return false;
        }
        unset($_SESSION['fints'][$this->credentialId]); // delete cache for this credential
        return true;
    }

    /**
     * @return TanMode[]
     */
    public function getUserTanModes(): array
    {
        if ($this->isCached('TanModes')) {
            return $this->getCache('TanModes');
        }
        try {
            $this->logger->info('Fetch TAN Modes', ['credId' => $this->credentialId]);
            $tanModes = $this->finTs->getTanModes();
        } catch (CurlException|ServerException $e) {
            ErrorHandler::handleException($e, 'TAN Modi können nicht empfangen werden - Verbringung zur Bank gestört');
        }
        if (empty($tanModes)) {
            return [];
        }

        return array_map(static function (TanMode $tanMode) {
            return '[' . $tanMode->getId() . '] ' . $tanMode->getName();
        }, $tanModes);
    }

    /**
     * @return array name (uid) => desc
     */
    public function getTanMedias(int $tanModeId): array
    {
        try {
            $this->logger->info('Fetch TAN Medias', ['credId' => $this->credentialId]);
            $tanMedia = $this->finTs->getTanMedia($tanModeId);
            $tanMediumNames = [];
            foreach ($tanMedia as $tanMedium) {
                $name = $tanMedium->getName();
                $phone = $tanMedium->getPhoneNumber() ?? 'keine Telefon-Nr. hinterlegt';
                $tanMediumNames[$name] = "[$name] $phone";
            }

            return $tanMediumNames;
        } catch (CurlException|ServerException $e) {
            ErrorHandler::handleException($e, 'TAN Modi können nicht empfangen werden - Verbindung zur Bank gestört');
        }
    }

    public function getSepaAccount($iban): SEPAAccount
    {
        $accounts = $this->getSepaAccounts();
        $filtered = array_filter($accounts, static function (SEPAAccount $account) use ($iban) {
            return $account->getIban() === $iban;
        });
        if (count($filtered) > 1) {
            HTMLPageRenderer::addFlash(BT::TYPE_WARNING, 'Es existieren mehrere Kontos mit der selben IBAN, bitte kontaktiere einen Administrator', $filtered);
        }
        if (count($filtered) === 0) {
            throw new InvalidArgumentException("Iban $iban nicht vorhanden");
        }
        return array_values($filtered)[0];
    }

    /**
     * @return SEPAAccount[]
     * @throws NeedsTanException
     */
    public function getSepaAccounts(): array
    {
        if ($this->isCached('SepaAccounts')) {
            return $this->getCache('SepaAccounts');
        }
        $action = $this->resumableAction();
        if ($action instanceof GetSEPAAccounts) {
            if (!$action->isDone()) {
                throw new NeedsTanException($action);
            }
        } else {
            $this->logger->info('Fetch SEPA Accounts', ['credId' => $this->credentialId]);
            $action = GetSEPAAccounts::create();
            $this->execute($action);
        }
        $accounts = $action->getAccounts();
        $this->setCache('SepaAccounts', $accounts);
        return $accounts;
    }

    /**
     * @throws NeedsTanException (hardly)
     */
    public function getIbans(): array
    {
        if ($this->isCached('ibans')) {
            return $this->getCache('ibans');
        }

        $accounts = $this->getSepaAccounts();
        $ibans = array_map(static function (SEPAAccount $account) {
            return $account->getIban();
        }, $accounts);
        $this->setCache('ibans', $ibans);
        return $ibans;
    }

    /**
     * @param $shortIban string DE12[...]0009 styled: DE120009
     * @return string|null full iban string from this credential konto
     */
    public function lengthenIban(string $shortIban): ?string
    {
        $ibans = $this->getIbans();
        $ibanStart = substr($shortIban, 0, 4);
        $ibanEnd = substr($shortIban, -4, 4);
        // return only first element -> very high possibility all have the same iban
        $filtered_ibans = array_values(array_filter($ibans, static function (string $el) use ($ibanStart, $ibanEnd) {
            return str_starts_with($el, $ibanStart) && str_ends_with($el, $ibanEnd);
        }));

        return $filtered_ibans[0] ?? null;
    }

    public static function shortenIban(string $fullIban): string
    {
        return substr($fullIban, 0, 4) . substr($fullIban, -4);
    }

    private function save(BaseAction $action = null): void
    {
        // remember action if any
        $this->activeAction = $action;
        if ($action?->needsTan() && !$action?->isDone()) {
            // chache it if tan is missing
            $this->logger->info('Save Action - TAN needed', ['credId' => $this->credentialId, 'action' => $action::class]);
            $this->setCache('action', $action);
        } else {
            // delete it from cache otherwise
            $this->setCache('action', null);
        }
        // save persist in cache
        $this->setCache('persist', $this->finTs->persist());
    }

    private function isCached(string|int $key): bool
    {
        return isset($_SESSION['fints'][$this->credentialId][$key]);
    }

    private function setCache(string|int $key, mixed $value): void
    {
        $_SESSION['fints'][$this->credentialId][$key] = $value;
    }

    private function getCache(string|int $key)
    {
        return $_SESSION['fints'][$this->credentialId][$key] ?? null;
    }

    /**
     * creates FINTS Connection Instance. Password needs to be set already
     * @return static
     */
    public static function load(int $credentialId): self
    {
        $db = DBConnector::getInstance();
        $res = $db->dbFetchAll('konto_credentials',
            [DBConnector::FETCH_ASSOC],
            ['konto_credentials.*', 'bank' => 'konto_bank.*'],
            [
                'konto_credentials.owner_id' => $db->getUser()['id'],
                'konto_credentials.id' => $credentialId,
            ],
            [['type' => 'inner', 'table' => 'konto_bank', 'on' => ['konto_credentials.bank_id', 'konto_bank.id']]]
        );

        if (count($res) === 1) {
            $res = $res[0];
        } else {
            ErrorHandler::handleError(500, 'found multiple DB entries');
        }

        if (!self::hasPassword($credentialId)) {
            ErrorHandler::handleError(400, "Bank Passwort für Credentials $credentialId benötigt");
        }
        $username = $res['bank_username'];

        $credentials = Credentials::create($username, self::getPassword($credentialId));

        $options = new FinTsOptions();
        $options->url = $res['bank.url'];
        $options->bankCode = $res['bank.blz'];
        $options->productName = FINTS_REGNR;
        $options->productVersion = InstalledVersions::getRootPackage()['version'] . DEV ? '-dev' : '';

        $tanModeInt = null;
        if ($res['tan_mode'] !== 'null' && !is_null($res['tan_mode'])) {
            $tanModeInt = (int) $res['tan_mode'];
        }
        $tanMediumName = null;
        if ($res['tan_medium_name'] !== 'null' && !is_null($res['tan_medium_name'])) {
            $tanMediumName = $res['tan_medium_name'];
        }

        return new self($credentialId, $options, $credentials, $tanModeInt, $tanMediumName);
    }

    /**
     * @param BaseAction $action - has the result afterwards if successful
     * @throws NeedsTanException
     */
    private function execute(BaseAction $action): void
    {
        try {
            $this->finTs->execute($action);
            $this->save($action);
            if ($action->needsTan()) {
                // TODO decoupled tan stuff here
                throw new NeedsTanException($action);
            }
        } catch (CurlException|ServerException $e) {
            ErrorHandler::handleException($e, 'Verbindung zur Bank gestört - Aktion nicht ausgeführt', );
        }
    }

    /**
     * @return bool if system has unclosed session, which was logged in to bank before
     */
    public static function hasActiveSession(int $credentialId): bool
    {
        return isset($_SESSION['fints'][$credentialId]['persist'], $_SESSION['fints'][$credentialId]['logged-in']) && self::hasPassword($credentialId);
    }

    public static function setLoginPassword(int $credentialId, string $pw): void
    {
        $_SESSION['fints'][$credentialId]['password'] = $pw;
    }

    public static function deleteLoginPassword(int $credentialId): void
    {
        unset($_SESSION['fints'][$credentialId]['password']);
    }

    public static function hasPassword(int $credentialId): bool
    {
        return isset($_SESSION['fints'][$credentialId]['password']);
    }

    private static function getPassword(int $credentialId): string
    {
        return $_SESSION['fints'][$credentialId]['password'];
    }

    /**
     * @return bool $success
     */
    public function submitTan(string $tan): bool
    {
        $this->logger->info('Submit TAN', ['credId' => $this->credentialId]);
        $action = $this->getCache('action');
        try {
            $this->finTs->submitTan($action, $tan);
            $this->save($action);
        } catch (CurlException $e) {
            HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'Konnte keine Verbindung zum Server aufbauen', $e->getMessage());
            return false;
        } catch (ServerException $e) {
            HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'TAN nicht akzeptiert', $e->getMessage());
            return false;
        }
        return true;
    }

    public function resumableAction(): ?BaseAction
    {
        return $this->activeAction ?? $this->getCache('action') ?? null;
    }

    public function setTanMode(int $tanModeId, ?string $tanMediumName = null): bool
    {
        try {
            $tanMode = $this->finTs->getTanModes()[$tanModeId];
            if ($tanMediumName === null && $tanMode->needsTanMedium()) {
                throw new InvalidArgumentException('Tan Medium wird benötigt');
            }
            $this->save();
            $this->logger->info('Set TAN Mode', ['credId' => $this->credentialId, 'tanMode' => $tanModeId, 'tanMedium' => $tanMediumName]);
        } catch (CurlException|ServerException  $e) {
            ErrorHandler::handleException($e, 'Kann keine Verbindung zum Bank Server aufbauen', 'BPB fetch failed');
        }
        $db = DBConnector::getInstance();
        return $db->dbUpdate(
            table: 'konto_credentials',
            filter: ['id' => $this->credentialId, 'owner_id' => $db->getUser()['id']],
            fields: [
                'tan_mode' => $tanModeId,
                'tan_mode_name' => $tanMode->getName(),
                'tan_medium_name' => $tanMediumName,
            ]
        ) === 1;
    }

    public function getStatements(string $iban, DateTime $start, DateTime $end): StatementOfAccount
    {
        $action = $this->resumableAction();
        if ($action instanceof GetStatementOfAccount) {
            if ($action->isDone()) {
                $this->save();
                return $action->getStatement();
            }
            throw new NeedsTanException($action);
        }
        $this->logger->info('Start Get SEPA Statements', ['credId' => $this->credentialId, $iban]);
        $account = $this->getSepaAccount($iban);
        $account = clone $account; // weird fix, without the clone the session var is changed to DateTime object
        // might be a bug in fints TODO: see if minimal example with the same bug can be found
        $action = GetStatementOfAccount::create($account, $start, $end);
        $this->execute($action);
        $this->finTs->getLogger()->debug('Statements received', $action->getStatement()->getStatements());
        return $action->getStatement();
    }
}
