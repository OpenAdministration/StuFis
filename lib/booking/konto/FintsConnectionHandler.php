<?php


namespace booking\konto;


use Composer\InstalledVersions;
use DateTime;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\KeyProtectedByPassword;
use Fhp\Action\GetSEPAAccounts;
use Fhp\Action\GetStatementOfAccount;
use Fhp\BaseAction;
use Fhp\CurlException;
use Fhp\FinTs;
use Fhp\Model\SEPAAccount;
use Fhp\Model\StatementOfAccount\Statement;
use Fhp\Model\TanMode;
use Fhp\Options\Credentials;
use Fhp\Options\FinTsOptions;
use Fhp\Protocol\ServerException;
use framework\ArrayHelper;
use framework\CryptoHandler;
use framework\DBConnector;
use framework\render\ErrorHandler;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class FintsConnectionHandler
{
    private $credentialId;

    /** @var FinTs */
    private $fints;

    private $loggedIn;

    private $logger;


    protected function __construct(
        int $credentialId,
        FinTsOptions $options,
        Credentials $credentials,
        ?string $persist = null,
        ?int $tanModeInt = null,
        ?string $tanMediumName = null
    )
    {
        $this->credentialId = $credentialId;
        $this->fints = FinTs::new($options, $credentials, $persist);

        if (!is_null($persist)) {
            $this->loggedIn = true;
        }

        if (!is_null($tanModeInt)) {
            $this->fints->selectTanMode($tanModeInt, $tanMediumName);
        }

        $this->logger = new Logger('fints', [
            new RotatingFileHandler(SYSBASE . 'runtime/logs/fints.log')
        ]);

    }

    public static function unlockCredentials($credentialId, $password): bool
    {
        $_SESSION['fints'][$credentialId]['key-password'] = $password;
        $ret = self::load($credentialId);
        if ($ret) {
            //session_write_close();
            return true;
        }
        return false;
    }

    public static function loadable($credentialId) : bool
    {
        return isset($_SESSION['fints'][$credentialId]['key-password']);
    }

    public static function load($credentialId): self
    {
        $db = DBConnector::getInstance();
        $res = $db->dbFetchAll('konto_credentials',
            [DBConnector::FETCH_ASSOC],
            ['konto_credentials.*', 'bank' => 'konto_bank.*'],
            [
                'konto_credentials.owner_id' => $db->getUser()["id"],
                'konto_credentials.id' => $credentialId,
            ],
            [['type' => 'inner', 'table' => 'konto_bank', 'on' => ['konto_credentials.bank_id', 'konto_bank.id']]]
        );

        if (count($res) === 1) {
            $res = $res[0];
        } else {
            ErrorHandler::handleError(500,'found multiple DB entries');
        }

        if (!isset($_SESSION['fints'][$credentialId]['key-password'])) {
            ErrorHandler::handleError(400, "Passwort für Credentials $credentialId benötigt");
        }

        $encryptedKeyString = $res['crypto_key'];
        $encryptedCredentials = $res['encrypted_credentials'];

        $credentialsJson = CryptoHandler::decrypt_by_key_pw(
            $encryptedCredentials,
            $encryptedKeyString,
            $_SESSION['fints'][$credentialId]['key-password']
        );
        if ($credentialsJson === false) {
            ErrorHandler::handleError(500, 'JSON kaputt');
        }

        $credentialArray = json_decode($credentialsJson, true, 512, JSON_THROW_ON_ERROR);
        if (!isset($credentialArray['username'], $credentialArray['password'])) {
            ErrorHandler::handleError(500, 'JSON Inhalte kaputt');
        }
        $credentials = Credentials::create($credentialArray['username'], $credentialArray['password']);

        $options = new FinTsOptions();
        $options->url = $res['bank.url'];
        $options->bankCode = $res['bank.blz'];
        $options->productName = FINTS_REGNR;
        $options->productVersion = InstalledVersions::getRootPackage()['version'] . DEV ? '-dev' : '';

        $tanModeInt = null;
        if ($res['default_tan_mode'] !== "null" && !is_null($res['default_tan_mode'])) {
            $tanModeInt = (int)$res['default_tan_mode'];
        }
        $tanMediumName = null;
        if ($res['default_tan_medium_name'] !== "null" && !is_null($res['default_tan_medium_name'])) {
            $tanMediumName = $res['default_tan_mode'];
        }
        $persist = self::getPersist($credentialId);

        return new self($credentialId, $options, $credentials, $persist, $tanModeInt, $tanMediumName);
    }

    public static function getPersist(int $credentialId): ?string
    {
        if (self::hasPersist($credentialId)) {
            return $_SESSION['fints'][$credentialId]['fints-persist'];
        }
        return null;
    }

    public static function hasPersist(int $credentialId): bool
    {
        return isset($_SESSION['fints'][$credentialId]['fints-persist']);
    }

    public static function lockCredentials($credentialId): array
    {
        unset($_SESSION['fints'][$credentialId]);
        return [true, "Zugangsdaten $credentialId gesperrt"];
    }

    public static function saveCredentials($bankId, $username, $password, $keyPhrase, $name = '')
    {
        try {
            $encKey = KeyProtectedByPassword::createRandomPasswordProtectedKey($keyPhrase);
            $key = $encKey->unlockKey($keyPhrase);
            $credential_array = ['username' => $username, 'password' => $password];
            $credentialJson = json_encode($credential_array, JSON_THROW_ON_ERROR);
            $encCredentialJson = Crypto::encrypt($credentialJson, $key);
            return DBConnector::getInstance()->dbInsert(
                'konto_credentials',
                [
                    'name' => $name,
                    'bank_id' => $bankId,
                    'owner_id' => DBConnector::getInstance()->getUser()['id'],
                    'encrypted_credentials' => $encCredentialJson,
                    'crypto_key' => $encKey->saveToAsciiSafeString()
                ]
            );
        } catch (WrongKeyOrModifiedCiphertextException $ciphertextException) {
            return $ciphertextException->getMessage();
        } catch (EnvironmentIsBrokenException $e) {
            ErrorHandler::handleException($e);
        }
        return false;
    }

    public static function deleteCredential(int $credId) : bool
    {
        return DBConnector::getInstance()->dbDelete('konto_credentials', ['id' => $credId]) === 1;
    }

    public function closeFintsSession(): bool
    {
        try {
            $this->fints->close();
            unset($_SESSION['fints'][$this->credentialId]);
            $this->loggedIn = false;
            return true;
        } catch (ServerException $e) {
            return false;
        }
    }

    public function getTanModes(): array
    {
        try {
            $tanModes = $this->fints->getTanModes();
        } catch (CurlException | ServerException $e) {
            ErrorHandler::handleException($e, 'TAN Modi können nicht empfangen werden - Verbringung zur Bank gestört');
        }
        if (empty($tanModes)) {
            return [];
        }

        return array_map(static function (TanMode $tanMode) {
            return "[" . $tanMode->getId() . "] " . $tanMode->getName();
        }, $tanModes);
    }

    /**
     * @param int $tanModeInt
     * @return array
     * @throws CurlException
     * @throws ServerException
     */
    public function getTanMedia(int $tanModeInt): array
    {
        $this->fints->selectTanMode($tanModeInt); //FIXME: might be unexpected behavior and unneeded
        try {
            return $this->fints->getTanMedia($tanModeInt);
        } catch (InvalidArgumentException $e) {
            return [];
        }
    }

    public function saveDefaultTanMode(int $credentialId, int $tanModeInt, ?string $tanMediumName = null): bool
    {
        try {
            $tanMode = $this->fints->getTanModes()[$tanModeInt];
            if(!$tanMode->needsTanMedium() && $tanMediumName !== null){
                ErrorHandler::handleError(400, 'Tan Mode does not need medium, but there was one supplied');
            }
            $tanModeName = $tanMode->getName();

            $ret = DBConnector::getInstance()->dbUpdate(
                'konto_credentials',
                ['id' => $credentialId],
                [
                    'default_tan_mode' => $tanModeInt,
                    'default_tan_mode_name' => $tanModeName,
                    'default_tan_medium_name' => $tanMediumName,
                ]
            );
            return $ret === 1;
        } catch (CurlException | ServerException $e) {
            ErrorHandler::handleException($e, 'Tan Mode Name kann nicht ermittelt werden');
        }
        return false;
    }

    /**
     * @param string $tan
     * @return array [bool $success, string $msg]
     */
    public function submitTan(string $tan): array
    {
        try {
            if ($this->hasTanSessionInformation()) {
                /** @var BaseAction $restoredAction */
                [$restoredAction, $params] = $this->loadTanActionFromSession();
                $this->fints->submitTan($restoredAction, $tan);
                if($restoredAction->isDone()){
                    $this->deleteTanSessionInformation();
                    if(!empty($params) && is_subclass_of($params[0], BaseAction::class)){
                        $className = ArrayHelper::remove($params, 0);
                        $restoredAction = new $className();
                        $this->prepareAction($restoredAction,$params);
                    }
                }
                $this->savePersistant();
                $result = $this->evaluateAction($restoredAction, $params);
                $this->savePersistant();
                return [true, $result];
            }
        } catch (CurlException | ServerException $e) {
            return [false, $e->getMessage()];
        }
        return [false, "Aktion nicht gefunden"];
    }

    public function hasTanSessionInformation(): bool
    {
        return isset($_SESSION['fints'][$this->credentialId]['tan']);
    }

    #[ArrayShape([
        0 => BaseAction::class,
        1 => 'array'
    ])]
    private function loadTanActionFromSession(): array
    {
        $tan = $_SESSION['fints'][$this->credentialId]['tan'];
        $fintsPersist = $tan['fints-persist'];
        $actionSerialized = $tan['action-serialized'];
        $actionClassName = $tan['action-classname'];
        $params = $tan['action-param'];

        /** @var $action BaseAction */
        $action = new $actionClassName();
        $action->unserialize($actionSerialized);

        $this->fints->loadPersistedInstance($fintsPersist);

        return [$action, $params];
    }

    public function deleteTanSessionInformation(): void
    {
        unset($_SESSION['fints'][$this->credentialId]['tan']);
    }

    private function savePersistant(): void
    {
        $_SESSION['fints'][$this->credentialId]['fints-persist'] = $this->fints->persist();
    }

    /**
     * @param BaseAction $action
     * @param array $param
     * @return array [bool $success, string $msg]
     */
    private function evaluateAction(BaseAction $action, array $param = []): array
    {
        if (!$action->isDone()) {
            return [false, "Aktion " . get_class($action) . " benötigt immer noch eine TAN - ausführen nicht möglich"];
        }

        switch (get_class($action)) {

            case GetStatementOfAccount::class:
                /** @var GetStatementOfAccount $action */
                $statements = $action->getStatement();

                $kontoId = $param['konto-id'];

                $db = DBConnector::getInstance();

                $lastKontoRow = $db->dbFetchAll(
                    'konto',
                    [DBConnector::FETCH_ASSOC],
                    ['*'],
                    ['konto_id' => $kontoId],
                    [],
                    ['id' => false],
                    [],
                    1
                );
                $lastKontoId = 0;
                $oldSaldoCent = null;
                $tryRewind = false;
                $rewindDiff = 0;
                $skipped = false;

                $kontoRow = $db->dbFetchAll('konto_type', [DBConnector::FETCH_ASSOC], ['*'], ['id' => $kontoId])[0];
                $syncUntil = DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $kontoRow['sync_until']);

                if (!empty($lastKontoRow)) {
                    $lastKontoRow = $lastKontoRow[0];
                    $lastKontoId = $lastKontoRow['id'];
                    $lastKontoSaldo = $lastKontoRow['saldo'];
                    $oldSaldoCent = $this->convertToCent($lastKontoSaldo);
                    $tryRewind = true;
                }

                $db->dbBegin();
                $transactionData = [];

                $dateString = date_create()->format(DBConnector::SQL_DATE_FORMAT);

                foreach ($statements->getStatements() as $statement) {
                    $dateString = $statement->getDate()->format(DBConnector::SQL_DATE_FORMAT);
                    $saldoCent = $this->convertToCent($statement->getStartBalance(), $statement->getCreditDebit());
                    if ($tryRewind === false && $oldSaldoCent !== null && $oldSaldoCent !== $saldoCent) {
                        $db->dbRollBack();
                        return [false, "$oldSaldoCent !== $saldoCent at statement from $dateString"];
                    }
                    //echo "Statement $dateString Saldo: $saldoCent";
                    foreach ($statement->getTransactions() as $transaction) {
                        $valCent = $this->convertToCent($transaction->getAmount(), $transaction->getCreditDebit());
                        $saldoCent += $valCent;

                        if ($tryRewind === true) {
                            //do rewind if necessary
                            $rewindRow = $db->dbFetchAll(
                                'konto',
                                [DBConnector::FETCH_ASSOC],
                                ['id'],
                                [
                                    'konto_id' => $kontoId,
                                    'value' => $this->convertCentForDB($valCent),
                                    'saldo' => $this->convertCentForDB($saldoCent),
                                    'date' => $transaction->getBookingDate()?->format('Y-m-d'),
                                    'valuta' => $transaction->getValutaDate()?->format('Y-m-d'),
                                    'customer_ref' => $transaction->getEndToEndID(),
                                ],
                                [],
                                ['id' => false],
                                [],
                                1
                            );
                            if(count($rewindRow) === 1){
                                $rewindId = $rewindRow[0]['id'];
                                $rewindDiff = $lastKontoId - $rewindId + 1;
                                $tryRewind = false;
                            }
                        }

                        if($rewindDiff > 0){
                            $rewindDiff--;
                            $skipped = $skipped === false ? 1 :  $skipped + 1;
                            continue; // skip this entry, it was in the db before
                        }

                        // are we exceeding sync_until?
                        if($syncUntil && $transaction->getValutaDate()?->diff($syncUntil)->invert === 1){
                            break 2;
                        }

                        $transactionData[] = [
                            'id' => ++$lastKontoId,
                            'konto_id' => $param['konto-id'],
                            'date' => $transaction->getBookingDate()?->format('Y-m-d'),
                            'valuta' => $transaction->getValutaDate()?->format('Y-m-d'),
                            'type' => $transaction->getBookingText(),
                            'empf_iban' => $transaction->getAccountNumber(),
                            'empf_bic' => $transaction->getBankCode(),
                            'empf_name' => $transaction->getName(),
                            'primanota' => $transaction->getPN(),
                            'value' => $this->convertCentForDB($valCent),
                            'saldo' => $this->convertCentForDB($saldoCent),
                            'zweck' => $transaction->getMainDescription(),
                            'comment' => $transaction->getTextKeyAddition(),
                            'gvcode' => $transaction->getBookingCode(),
                            'customer_ref' => $transaction->getEndToEndID(),
                        ];
                    }
                    $oldSaldoCent = $saldoCent;
                }

                if(count($transactionData) > 0){
                    $db->dbInsertMultiple('konto', array_keys($transactionData[0]), ...$transactionData);
                    $db->dbUpdate('konto_type', ['id' => $kontoId], ['last_sync' => $dateString]);
                }
                $ret = $db->dbCommitRollbackOnFailure();

                if ($ret === true) {
                    $msg = count($transactionData) . " Einträge importiert.";
                } else {
                    $msg = "Ein Fehler ist aufgetreten - DBRollback - Import von " .
                        count($transactionData) . " Einträgen ausstehend.";
                }
                if(DEV && $skipped !== false){
                    $msg .= " $skipped Einträge wurden übersprungen";
                }

                return [$ret, $msg];

            case BaseAction::class: // login Action
                return [true, "Login erfolgreich"];
            default:
                return [false, "Aktion " . get_class($action) . " nicht bekannt, kann nicht ausgeführt werden"];
        }
    }

    /**
     * @param float|string $amount
     * @param string|null $creditDebit either @see Statement::CD_DEBIT or @see Statement::CD_CREDIT, if null its
     *                    assumed by sign of $amount
     * @return float|int
     */
    private function convertToCent($amount, string $creditDebit = null)
    {
        $float = (float)$amount;
        $int = (int)round($float * 100);

        if(is_null($creditDebit)){
            $sign = ($float > 0) - ($float < 0);
            return $sign * $int;
        }
        return ($creditDebit === Statement::CD_DEBIT ? -1 : 1) * $int;
    }

    private function convertCentForDB(int $amount): string
    {
        // rounds implicit
        return number_format($amount / 100.0, 2, '.', '');
    }

    /**
     * @param string $shortIban
     * @return array
     * @throws NeedsTanException
     */
    public function saveNewSepaStatements(string $shortIban): array
    {
        $accounts = $this->getSEPAAccounts();
        $iban = $this->getIbanFromShort($shortIban);

        // filter the full matching IBAN
        $account = array_values(array_filter($accounts, static function (SEPAAccount $el) use ($iban) {
            return $el->getIban() === $iban;
        }))[0];

        $dbKontos = DBConnector::getInstance()->dbFetchAll('konto_type',
            [DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY],
            ['iban', '*']
        );
        $dbKonto = $dbKontos[$iban];
        $syncFrom = DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $dbKonto['sync_from']);
        $lastSync = DateTime::createFromFormat(DBConnector::SQL_DATETIME_FORMAT, $dbKonto['last_sync']);
        $syncUntil = DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $dbKonto['sync_until']);

        // set default for lastsync
        if ($lastSync === false) {
            $lastSync = clone $syncFrom;
        }

        if ($syncUntil === false) {
            $syncUntil = date_create();
        }

        if ($syncUntil->diff(date_create())->invert === -1) { // if in the future
            $syncUntil = date_create();
        }

        //find earliest
        if ($syncFrom->diff($lastSync)->invert === 0) { //if last sync is older
            $startDate = $lastSync;
        } else {
            $startDate = $syncFrom;
        }

        $action = GetStatementOfAccount::create($account, $startDate, $syncUntil);
        $param = ['konto-id' => $dbKonto['id']];
        $action = $this->prepareAction($action, $param);
        return $this->evaluateAction($action, $param);
    }

    /**
     * @return SEPAAccount[]
     * @throws NeedsTanException
     */
    public function getSEPAAccounts(): array
    {
        if (isset($_SESSION['fints'][$this->credentialId]['SepaAccounts'])) {
            return $_SESSION['fints'][$this->credentialId]['SepaAccounts'];
        }

        $action = GetSEPAAccounts::create();
        $action = $this->prepareAction($action, []);

        /** @var GetSEPAAccounts $action */
        $accounts = $action->getAccounts();
        $allIbans = [];
        foreach ($accounts as $SEPAAccount) {
            $allIbans[] = $SEPAAccount->getIban();
        }

        $_SESSION['fints'][$this->credentialId]['ibans'] = $allIbans;
        $_SESSION['fints'][$this->credentialId]['bic'] = $accounts[0]->getBic();
        $_SESSION['fints'][$this->credentialId]['SepaAccounts'] = $accounts;

        return $accounts;
    }

    /**
     * @param BaseAction $action if provided will take this action instead of reload from db
     * @param array $param
     * @return BaseAction
     */
    private function prepareAction(BaseAction $action, array $param): BaseAction
    {
        if (!$this->loggedIn) {
            // there was a login (attempt) before
            if($this->hasTanSessionInformation()){
                [$loginAction, $param] = $this->loadTanActionFromSession();
                /** @var BaseAction $loginAction */
                if (!$loginAction->isDone()){
                    throw new NeedsTanException('Tan wird zum login benötigt', $loginAction);
                }
                $this->loggedIn = true;
                $actionClass = ArrayHelper::remove($param, 0);
                $action = new $actionClass();
                $this->deleteTanSessionInformation();
                return $this->prepareAction($action, $param);
            }
            try {
                $loginAction = $this->fints->login();
                if ($loginAction->needsTan()) {
                    $this->saveTanInfoToSession($loginAction, [$action::class, ...$param]);
                    throw new NeedsTanException('Für den Login wird eine TAN benötigt', $action);
                }
                //TODO: log the login
                //$this->saveActionToDB($action, $actionId, self::STATE_INIT_ACTION);
                $this->loggedIn = true;
            } catch (ServerException $e) {
                ErrorHandler::handleException($e, 'Fehler beim Login - Zugangsdaten (Bank) vermutlich falsch.');
            } catch (CurlException $e) {
                ErrorHandler::handleException($e, 'Fehler beim Login - Bank Server nicht erreichbar.');
            }
        }
        // check if action in session is finished or unfinished
        if ($this->hasTanSessionInformation()) {
            [$action, $param] = $this->loadTanActionFromSession();
            if (!$action->isDone()) {
                // unfinished action -> render tan input again
                throw new NeedsTanException('Eine TAN wird benötigt', $action);
            }
            //should never happen but for safety
            $this->deleteTanSessionInformation();
            return $action;

        }
        // login successful, no action in session found -> execute main action
        try {
            $this->fints->execute($action);
            if ($action->needsTan()) {
                $this->saveTanInfoToSession($action, $param);
                throw new NeedsTanException('Eine TAN wird für die Aktion benötigt', $action);
            }
        } catch (CurlException $e) {
            ErrorHandler::handleException($e, "Keine Verbindung zur Bank möglich");
        } catch (ServerException $e) {
            ErrorHandler::handleException($e, "Konto Aktion konnte nicht ausgeführt werden ");
        }
        return $action;

    }

    private function saveTanInfoToSession(BaseAction $action, $param): void
    {
        // save persisant stuff for later
        $tan['fints-persist'] = $this->fints->persist();
        $tan['action-serialized'] = $action->serialize();
        $tan['action-classname'] = get_class($action);
        $tan['action-param'] = $param;
        //$tan['tan-mode'] = $this->fints->getSelectedTanMode()->getId();

        $_SESSION['fints'][$this->credentialId]['tan'] = $tan;

        if (DEV) {
            echo "<pre>" . var_export($_SESSION['fints'][$this->credentialId]['tan']['action-classname'], true) . "</pre>";
        }
    }

    /**
     * @throws NeedsTanException
     */
    public function getIbanFromShort($shortIban)
    {
        $allIbans = $this->getIbans();

        $ibanStart = substr($shortIban, 0, 4);
        $ibanEnd = substr($shortIban, -4, 4);
        // return only first element -> very high possibility all have the same iban
        return array_values(array_filter($allIbans, static function (string $el) use ($ibanStart, $ibanEnd) {
            return (strpos($el, $ibanStart) === 0) && (substr($el, -4, 4) === $ibanEnd);
        }))[0];
    }

    /**
     * @return string[]
     * @throws NeedsTanException
     */
    public function getIbans(): array
    {
        if (!isset($_SESSION['fints'][$this->credentialId]['SepaAccounts'])) {
            $this->getSEPAAccounts();
        }
        return $_SESSION['fints'][$this->credentialId]['ibans'];
    }

    /**
     * @param $newPassword
     * @return array [bool $success, string $msg]
     */
    public function changePassword($newPassword) : array
    {
        $db = DBConnector::getInstance();
        $res = $db->dbFetchAll('konto_credentials',
            [DBConnector::FETCH_ASSOC],
            ['encrypted_credentials', 'crypto_key'],
            [
                'owner_id' => $db->getUser()["id"],
                'id' => $this->credentialId,
            ]
        );

        if (count($res) === 1) {
            $res = $res[0];
        } else {
            return [false, 'Die Credentials konnten nicht gefunden werden'];
        }

        if (!isset($_SESSION['fints'][$this->credentialId]['key-password'])) {
            return [false, "Passwort für Credentials $this->credentialId benötigt"];
        }

        $encryptedKeyString = $res['crypto_key'];
        $encryptedCredentials = $res['encrypted_credentials'];

        $credentialsJson = CryptoHandler::decrypt_by_key_pw(
            $encryptedCredentials,
            $encryptedKeyString,
            $_SESSION['fints'][$this->credentialId]['key-password']
        );
        if ($credentialsJson === false) {
            return [false, 'Die Zugangsdaten konnten nicht entschlüsselt werden'];
        }

        try {
            $encKey = KeyProtectedByPassword::createRandomPasswordProtectedKey($newPassword);
            $key = $encKey->unlockKey($newPassword);
            $encCredentialJson = Crypto::encrypt($credentialsJson, $key);
            $ret = DBConnector::getInstance()->dbUpdate(
                'konto_credentials',
                ['id' => $this->credentialId],
                [
                    'encrypted_credentials' => $encCredentialJson,
                    'crypto_key' => $encKey->saveToAsciiSafeString()
                ]
            );
        } catch (WrongKeyOrModifiedCiphertextException | EnvironmentIsBrokenException $e) {
           return [false, 'Ein Crypto Fehler ist aufgetreten ' . $e->getMessage()];
        }

        return [$ret === 1, $ret === 1 ? 'Das Passwort wurde erfolgreich gewechselt' : 'Das Passwort konnte nicht gewechselt werden'];
    }

}