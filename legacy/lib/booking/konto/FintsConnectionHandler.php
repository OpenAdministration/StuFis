<?php

namespace booking\konto;

use App\Exceptions\LegacyDieException;
use App\Models\FintsInstitute;
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
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class FintsConnectionHandler
{
    private FinTs $finTs;

    private ?BaseAction $activeAction;

    public Logger $logger;

    /**
     * FintsConnectionHandler2 constructor.
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

        if (! is_null($tanModeInt)) {
            $this->finTs->selectTanMode($tanModeInt, $tanMediumName);
        }

        $this->logger = new Logger('fints', [
            new RotatingFileHandler(SYSBASE.'/runtime/logs/fints.log',
                maxFiles: 7,
                level: DEV ? Level::Debug : Level::Info,
            ),
        ]);
        $this->finTs->setLogger($this->logger);
        $this->logger->info('FINTS request for credential', ['credentialId' => $this->credentialId]);
    }

    public static function saveCredentials(mixed $blz, mixed $bankuser, mixed $name): int
    {
        $db = DBConnector::getInstance();

        return (int) $db->dbInsert('konto_credentials', [
            'blz' => $blz,
            'owner_id' => $db->getUser()['id'],
            'bank_username' => $bankuser,
            'name' => $name,
        ]);
    }

    /**
     * try to login. If wrong credentials, delete saved pw and add Flash to PageRenderer
     *
     * @return bool $success returns if password is correct
     *
     * @throws NeedsTanException
     */
    public function login(): bool
    {
        // resume execution if any
        $resumableAction = $this->resumableAction();
        if ($resumableAction instanceof DialogInitialization) {
            if (! $resumableAction->isDone()) {
                throw new NeedsTanException($resumableAction, 'Tan wird zum Login benötigt');
            }
            $this->setCache('logged-in', true);
            $this->saveAction();

            return true;
        }
        // regular execution
        try {
            $this->logger->info('Start login', ['credId' => $this->credentialId]);
            if ($this->finTs->getSelectedTanMode() === null) {
                HTMLPageRenderer::addFlash(BT::TYPE_INFO, 'Vor dem ersten Login muss der TAN Modus gesetzt werden');
                HTMLPageRenderer::redirect(URIBASE.'konto/credentials/'.$this->credentialId.'/tan-mode');
            }
            $loginAction = $this->finTs->login();
            $this->saveAction($loginAction);
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
            $this->logger->error('Login: Connection failed', ['exception' => $e]);
            ErrorHandler::handleException($e, 'Kann keine Verbindung zum Bank Server aufbauen');
        } catch (ServerException|UnexpectedResponseException $e) {
            $this->setCache('logged-in', null);
            static::deleteLoginPassword($this->credentialId);
            $this->logger->error('Login failed', ['exception' => $e]);
            HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'Login nicht erfolgreich, bitte überprüfe die Login Daten', $e->getMessage());

            return false;
        }
    }

    public function logout(): bool
    {
        try {
            $this->logger->info('Logout', ['credId' => $this->credentialId]);
            $this->finTs->close(); // logout @ server
            $this->forgetCachedCredentials($this->credentialId);
            HTMLPageRenderer::addFlash(BT::TYPE_SUCCESS, 'Erfolgreich ausgeloggt');
        } catch (CurlException|ServerException|UnexpectedResponseException $e) {
            // A logout that cannot reach the bank is not worth an error page - the local
            // session data is dropped either way below.
            $this->logger->error('Logout failed', ['exception' => $e]);
            HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'Logout fehlgeschlagen', $e->getMessage());
            $this->forgetCachedCredentials($this->credentialId);

            return false;
        }

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
        } catch (CurlException|ServerException|UnexpectedResponseException $e) {
            $this->logger->info('Fetch TAN Modes failed', ['exception' => $e]);
            ErrorHandler::handleException($e, 'TAN Modi können nicht empfangen werden - Verbringung zur Bank gestört');
        }
        if (empty($tanModes)) {
            return [];
        }

        return array_map(static function (TanMode $tanMode) {
            return '['.$tanMode->getId().'] '.$tanMode->getName();
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
        } catch (CurlException|ServerException|UnexpectedResponseException $e) {
            $this->logger->error('Tan kann nicht empfangen werden - Verbindung zur Bank gestört', ['exception' => $e]);
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
     *
     * @throws NeedsTanException
     */
    public function getSepaAccounts(): array
    {
        if ($this->isCached('SepaAccounts')) {
            return $this->getCache('SepaAccounts');
        }
        $action = $this->resumableAction();
        if ($action instanceof GetSEPAAccounts) {
            if (! $action->isDone()) {
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
     * @param  $shortIban  string DE12[...]0009 styled: DE120009
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
        return substr($fullIban, 0, 4).substr($fullIban, -4);
    }

    private function saveAction(?BaseAction $action = null): void
    {
        // remember action if any
        $this->activeAction = $action;
        if ($action?->needsTan() && ! $action?->isDone()) {
            // chache it if tan is missing
            $this->logger->info('Save Action - TAN needed', ['credId' => $this->credentialId, 'action' => $action::class]);
            $this->setCache('action', $action);
            if ($this->isDecoupledTanMode() && $this->getCache('decoupled-next-check') === null) {
                // Seeded once per pending action, not on every call through here: a decoupled
                // TanRequest gets refreshed on every failed checkDecoupledSubmission() (see
                // confirmDecoupledTan()), which re-enters this same branch, and re-seeding on
                // each of those would keep pushing the earliest allowed check into the future
                // instead of counting down towards it.
                $tanMode = $this->finTs->getSelectedTanMode();
                $this->setCache('decoupled-checks', 0);
                $this->setCache('decoupled-next-check', time() + $tanMode->getFirstDecoupledCheckDelaySeconds());
            }
        } else {
            // delete it from cache otherwise
            $this->setCache('action', null);
            $this->setCache('decoupled-checks', null);
            $this->setCache('decoupled-next-check', null);
            if ($action === null) {
                // Only dropping the action outright - a fresh start, or the "belongs to
                // something else" branch in getStatements() - drops the scope with it. A
                // *finished* action that is still handed in here (submitTan() or
                // checkDecoupledSubmission() just completed it) must keep its scope: that is
                // what lets getStatements() recognise it as its own action on the next call and
                // return its result, instead of discarding it as belonging to another request
                // and starting a fresh one that needs another TAN.
                $this->setCache('action-scope', null);
            }
        }
        // save persist in cache
        $this->setCache('persist', $this->finTs->persist());
    }

    /**
     * Whether the bank access's selected TAN mode is a decoupled one, i.e. the user confirms
     * on their banking app instead of typing a TAN. getSelectedTanMode() can throw
     * InvalidArgumentException if the persisted mode id no longer matches anything in a
     * refreshed BPD; that is not this method's problem to raise, so it is treated the same as
     * "no mode selected yet".
     */
    public function isDecoupledTanMode(): bool
    {
        try {
            return $this->finTs->getSelectedTanMode()?->isDecoupled() ?? false;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    private function isCached(string|int $key): bool
    {
        return request()?->session()->exists("fints.$this->credentialId.$key");
    }

    private function setCache(string|int $key, mixed $value): void
    {
        request()?->session()->put("fints.$this->credentialId.$key", $value);
    }

    private function getCache(string|int $key)
    {
        return request()?->session()->get("fints.$this->credentialId.$key");
    }

    private function forgetCachedCredentials(int $credential_id): void
    {
        self::forgetSession($credential_id);
    }

    /**
     * Drops everything the session holds for a bank access: password, the persisted dialog
     * state and the logged-in marker. Static because deleting a bank access has to clear it
     * whether or not there is a live connection to hang the call off.
     */
    public static function forgetSession(int $credentialId): void
    {
        request()?->session()->forget("fints.$credentialId");
    }

    /**
     * creates FINTS Connection Instance. Password needs to be set already
     *
     * @return static
     *
     * @throws LegacyDieException
     */
    public static function load(int $credentialId): self
    {
        $db = DBConnector::getInstance();
        $res = $db->dbFetchAll('konto_credentials',
            [DBConnector::FETCH_ASSOC],
            ['konto_credentials.*', 'bank' => 'fints_institutes.*'],
            [
                'konto_credentials.owner_id' => $db->getUser()['id'],
                'konto_credentials.id' => $credentialId,
            ],
            [[
                'type' => 'inner',
                'table' => 'fints_institutes',
                'on' => ['konto_credentials.blz', 'fints_institutes.blz'],
            ]]
        );

        if (count($res) === 1) {
            $res = $res[0];
        } else {
            throw new LegacyDieException(500, 'found multiple DB entries');
        }

        if (! self::hasPassword($credentialId)) {
            throw new LegacyDieException(400, "Bank Passwort für Credentials $credentialId benötigt");
        }
        $username = $res['bank_username'];

        $credentials = Credentials::create($username, self::getPassword($credentialId));

        if (trim((string) FINTS_REGNR) === '') {
            // FinTsOptions::validate() would raise "Product name required!" as an
            // uncaught InvalidArgumentException, i.e. an error page with no clue.
            throw new LegacyDieException(
                500,
                'Für den Bankzugang fehlt die FinTS-Registrierungsnummer (FINTS_REG_NR in der Konfiguration). '.
                'Bitte wende dich an die Administration.'
            );
        }

        if (empty($res['bank.pin_tan_address'])) {
            throw new LegacyDieException(
                500,
                "Für die BLZ {$res['blz']} führt die Bankenliste keinen FinTS-Zugang (PIN/TAN). ".
                'Dieses Institut unterstützt den Abruf nicht oder die Bankenliste ist veraltet.'
            );
        }

        // Refuse before the PIN is on the wire. The synced list only ever yields HTTPS, so
        // in practice this catches an address carried over from the retired konto_bank
        // table, which nobody ever validated - until the first sync overwrites it.
        if (! FintsInstitute::hasSecurePinTanAddress($res['bank.pin_tan_address'])) {
            throw new LegacyDieException(
                500,
                "Die hinterlegte FinTS-Adresse für die BLZ {$res['blz']} ist nicht mit https:// ".
                'gesichert; PIN und TAN würden unverschlüsselt übertragen. Der Abruf wurde '.
                'abgebrochen. Bitte aktualisiere die Bankenliste (php artisan '.
                'stufis:fints-institutes-update) oder wende dich an die Administration.'
            );
        }

        $options = new FinTsOptions;
        $options->url = $res['bank.pin_tan_address'];
        $options->bankCode = $res['blz'];
        $options->productName = FINTS_REGNR;
        // The concatenation binds tighter than ?:, so this used to evaluate as
        // (('4.4.3'.DEV) ? '-dev' : '') - an always-truthy string, which reported the
        // version to the bank as literally "-dev" regardless of what is installed.
        // config('stufis.version') is the pretty version; getRootPackage()['version']
        // would be the normalised one ("4.4.4.0" rather than "4.4.4").
        $options->productVersion = config('stufis.version').(DEV ? '-dev' : '');

        $tanModeInt = null;
        if ($res['tan_mode'] !== 'null' && ! is_null($res['tan_mode'])) {
            $tanModeInt = (int) $res['tan_mode'];
        }
        $tanMediumName = null;
        if ($res['tan_medium_name'] !== 'null' && ! is_null($res['tan_medium_name'])) {
            $tanMediumName = $res['tan_medium_name'];
        }

        return new self($credentialId, $options, $credentials, $tanModeInt, $tanMediumName);
    }

    /**
     * @param  BaseAction  $action  - has the result afterwards if successful
     *
     * @throws NeedsTanException
     */
    private function execute(BaseAction $action): void
    {
        try {
            $this->finTs->execute($action);
            $this->saveAction($action);
            if ($action->needsTan()) {
                // TODO decoupled tan stuff here
                throw new NeedsTanException($action);
            }
        } catch (CurlException|ServerException|UnexpectedResponseException $e) {
            $this->logger->error('Aktion nicht ausgeführt', ['exception' => $e]);
            ErrorHandler::handleException($e, 'Verbindung zur Bank gestört - Aktion nicht ausgeführt');
        }
    }

    /**
     * @return bool if system has unclosed session, which was logged in to bank before
     */
    public static function hasActiveSession(int $credentialId): bool
    {
        return request()?->session()->exists(["fints.$credentialId.persist", "fints.$credentialId.logged-in"]) && self::hasPassword($credentialId);
    }

    public static function setLoginPassword(int $credentialId, string $pw): void
    {
        request()?->session()->put("fints.$credentialId.password", $pw);
    }

    public static function deleteLoginPassword(int $credentialId): void
    {
        request()?->session()->forget("fints.$credentialId.password");
    }

    public static function hasPassword(int $credentialId): bool
    {
        return request()?->session()->exists("fints.$credentialId.password");
    }

    private static function getPassword(int $credentialId): string
    {
        return request()?->session()->get("fints.$credentialId.password");
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
            $this->saveAction($action);
        } catch (CurlException $e) {
            $this->logger->error('Submit Tan: no Connection', ['exception' => $e]);
            HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'Konnte keine Verbindung zum Server aufbauen', $e->getMessage());

            return false;
        } catch (ServerException|UnexpectedResponseException $e) {
            // A rejected TAN arrives as UnexpectedResponseException("Bank has not accepted
            // TAN: ...") from FinTs::submitTan(). That extends RuntimeException, while
            // ServerException extends Exception - two unrelated hierarchies, so catching
            // only the latter turned a mistyped TAN into an error page.
            $this->logger->error('Wrong Tan', ['exception' => $e]);
            HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'TAN nicht akzeptiert', $e->getMessage());

            return false;
        } catch (InvalidArgumentException $e) {
            // The library refuses to take a TAN for a decoupled TAN mode. Reaching here in
            // practice would mean a stale page (e.g. still open in another tab) posted a 'tan'
            // field even though the current TAN mode is decoupled - the confirmation page
            // renders no such field. This is a safety net for that edge case, not the expected
            // path.
            $this->logger->error('TAN submission rejected by the library', ['exception' => $e]);
            HTMLPageRenderer::addFlash(
                BT::TYPE_DANGER,
                'Für dieses TAN-Verfahren wird keine TAN eingegeben',
                'Bitte lade die Seite neu und bestätige die Anfrage stattdessen in der Banking-App.'
            );

            return false;
        }

        return true;
    }

    /**
     * For a decoupled TAN mode, asks the bank whether the user has confirmed the pending
     * action on their banking app yet - the counterpart to submitTan() for modes that carry no
     * TAN at all. Modelled closely on submitTan(): same logging, same three catch arms.
     *
     * @return bool true once the bank confirms the action is done
     */
    public function confirmDecoupledTan(): bool
    {
        $this->logger->info('Confirm decoupled TAN', ['credId' => $this->credentialId]);
        $action = $this->getCache('action');
        if ($action === null) {
            HTMLPageRenderer::addFlash(BT::TYPE_INFO, 'Es liegt keine offene Anfrage vor, die bestätigt werden könnte');

            return false;
        }
        try {
            $tanMode = $this->finTs->getSelectedTanMode();
            $maxChecks = $tanMode->getMaxDecoupledChecks();
            $usedChecks = $this->getCache('decoupled-checks') ?? 0;
            if ($maxChecks > 0 && $usedChecks >= $maxChecks) {
                HTMLPageRenderer::addFlash(
                    BT::TYPE_DANGER,
                    'Die Freigabe wurde nicht rechtzeitig bestätigt',
                    'Die Bank hat innerhalb der erlaubten Versuche keine Freigabe gemeldet. Die Anfrage muss erneut gestartet werden.'
                );
                $this->saveAction(); // drops the pending action, it cannot be resumed anymore

                return false;
            }

            $nextCheck = $this->getCache('decoupled-next-check');
            if ($nextCheck !== null && time() < $nextCheck) {
                // No sleep() here: this runs inside a request that holds the session lock, and
                // sleeping while holding it would block every other tab/request of the same
                // user for the same duration.
                HTMLPageRenderer::addFlash(
                    BT::TYPE_INFO,
                    'Bitte noch '.($nextCheck - time()).' Sekunden warten, bevor erneut bei der Bank nachgefragt werden kann'
                );

                return false;
            }

            $done = $this->finTs->checkDecoupledSubmission($action);
            $this->setCache('decoupled-checks', $usedChecks + 1);
            $this->setCache('decoupled-next-check', time() + $tanMode->getPeriodicDecoupledCheckDelaySeconds());
            // The library requires the FinTs instance to be persist()-ed again after every
            // checkDecoupledSubmission() call, whether or not it returned true - its internal
            // dialog state moves on regardless. saveAction() is what does that persist() call.
            $this->saveAction($action);

            if (! $done) {
                HTMLPageRenderer::addFlash(BT::TYPE_INFO, 'Die Bank hat die Freigabe noch nicht gesehen - bitte in der Banking-App bestätigen und erneut versuchen');
            }

            return $done;
        } catch (CurlException $e) {
            $this->logger->error('Confirm decoupled TAN: no Connection', ['exception' => $e]);
            HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'Konnte keine Verbindung zum Server aufbauen', $e->getMessage());

            return false;
        } catch (ServerException|UnexpectedResponseException $e) {
            $this->logger->error('Confirm decoupled TAN failed', ['exception' => $e]);
            HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'Anfrage bei der Bank fehlgeschlagen', $e->getMessage());

            return false;
        } catch (InvalidArgumentException $e) {
            // The library refuses checkDecoupledSubmission() for anything other than a
            // decoupled mode with a pending TanRequest. Reaching here would mean the TAN mode
            // changed underneath an open confirmation page - a safety net, not the expected
            // path.
            $this->logger->error('Confirm decoupled TAN rejected by the library', ['exception' => $e]);
            HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'Diese Anfrage kann nicht bestätigt werden', $e->getMessage());

            return false;
        }
    }

    /**
     * How many of the bank's allowed confirmDecoupledTan() attempts are left, for display on
     * the confirmation page. Null when the mode does not cap them at all (getMaxDecoupledChecks()
     * returning 0 means unlimited).
     */
    public function decoupledChecksRemaining(): ?int
    {
        $maxChecks = $this->finTs->getSelectedTanMode()?->getMaxDecoupledChecks() ?? 0;
        if ($maxChecks <= 0) {
            return null;
        }

        return max(0, $maxChecks - ($this->getCache('decoupled-checks') ?? 0));
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
            $this->saveAction();
            $this->logger->info('Set TAN Mode', ['credId' => $this->credentialId, 'tanMode' => $tanModeId, 'tanMedium' => $tanMediumName]);
        } catch (CurlException|ServerException|UnexpectedResponseException $e) {
            $this->logger->error('BPB fetch failed', ['exception' => $e]);
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

    /**
     * @param  DateTime|null  $start  null asks the bank for its own default range, which is
     *                                what an account without a configured sync_from gets
     */
    public function getStatements(string $iban, ?DateTime $start, DateTime $end): StatementOfAccount
    {
        // What a pending statement request was created for. While it waits for a TAN the
        // action sits in the session, and it used to be resumed on nothing but its type:
        // asking for account A, then opening account B's import URL and entering the TAN
        // there returned A's statements, which the caller then stored under B's konto_id.
        $scope = $this->statementScope($iban, $start, $end);
        $action = $this->resumableAction();
        if ($action instanceof GetStatementOfAccount) {
            if ($this->getCache('action-scope') === $scope) {
                if ($action->isDone()) {
                    $this->saveAction();

                    return $action->getStatement();
                }
                throw new NeedsTanException($action);
            }
            $this->logger->warning('Discarding a pending statement request made for something else', [
                'credId' => $this->credentialId,
                'requested' => $scope,
            ]);
            // Say it out loud as well: the log lands in legacy/runtime/logs/fints.log, which
            // is not somewhere anyone looks, and from the user's side the TAN they were about
            // to enter simply stops applying.
            HTMLPageRenderer::addFlash(
                BT::TYPE_INFO,
                'Der noch offene Umsatzabruf gehörte zu einem anderen Konto oder Zeitraum und wurde verworfen',
                'Der Abruf für dieses Konto wird neu gestartet - dafür ist eine neue TAN nötig.'
            );
            $this->saveAction(); // drops the stale action and its scope
        }
        $this->logger->info('Start Get SEPA Statements', ['credId' => $this->credentialId, $iban]);
        $account = $this->getSepaAccount($iban);
        $account = clone $account; // weird fix, without the clone the session var is changed to DateTime object
        // might be a bug in fints TODO: see if minimal example with the same bug can be found
        $action = GetStatementOfAccount::create($account, $start, $end);
        // Has to be recorded before execute(), which caches the action and then throws
        // NeedsTanException, ending this request.
        $this->setCache('action-scope', $scope);
        $this->execute($action);

        return $action->getStatement();
    }

    private function statementScope(string $iban, ?DateTime $start, DateTime $end): string
    {
        return $iban.'|'.($start?->format('Y-m-d') ?? 'bank-default').'|'.$end->format('Y-m-d');
    }

    public function getLogger(): LoggerInterface
    {
        return $this->finTs->getLogger();
    }
}
