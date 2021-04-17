<?php


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
use Fhp\Model\TanRequestChallengeImage;
use Fhp\Options\Credentials;
use Fhp\Options\FinTsOptions;
use Fhp\Protocol\ServerException;

require_once(FRAMEWORK_PATH.'/external_libraries/crypto/defuse-crypto.phar');

class FinTSHandler extends Renderer
{


    const STATE_LOGIN = 'need-login';
    const STATE_INIT_ACTION = 'inited';
    const STATE_EXEC_ACTION = 'executable';
    const STATE_DONE = 'done';

    /** @var FinTs */
    private $fints;

    private $credentialId;

    private $routeInfo;
    private $kontoId;

    private $shortIBAN;
    /**
     * @var bool
     */
    private $loggedIn;


    public function __construct(array $routeInfo)
    {

        $this->routeInfo = $routeInfo;
        $this->loggedIn = false;

        if(isset($this->routeInfo['credential-id'])){
            $this->credentialId = (int) $this->routeInfo['credential-id'];
        }

        if(isset($this->routeInfo['short-iban'])){
            $this->shortIBAN = $this->routeInfo['short-iban'];
        }
    }

    public function lockCredentials($credentialId) : array
    {
        unset($_SESSION['fints'][$credentialId]);
        return [true, "Zugangsdaten $credentialId gesperrt"];
    }

    public function unlockCredentials($credentialId, $password) : bool
    {
        $_SESSION['fints'][$credentialId]['key-password'] = $password;
        $ret = $this->loadCredentials($credentialId);
        if($ret){
            //session_write_close();
            return true;
        }

        return false;
    }

    public function render()
    {
        $pageAction = $this->routeInfo['action'];

        switch ($pageAction){
            case 'pick-my-credentials':
                $this->renderCredentialPick();
                return;
            case 'new-credentials':
                $this->renderNewCredentials();
                return;
        }

        if(!isset($_SESSION['fints'][$this->credentialId]['key-password'])){
            $this->renderPasswordForm($this->credentialId);
            return;
        }

        $this->loadCredentials($this->credentialId);


        switch ($pageAction){

            case 'pick-sepa-konto':
                $this->renderSepaAccountsList();
                break;
            case 'pick-sepa-action':
                $this->renderSepaActionPick();
                break;
            case 'pick-tan-mode':
                $this->renderTanModePicker();
                break;
            case 'pick-tan-medium':
                $this->renderTanMediumPicker();
                break;
            case 'sepa-details':
                $this->renderSepaDetails();
                break;
            case 'new-sepa-konto-import':
                $this->renderNewSepaKontoImport();
                break;
            default:
                ErrorHandler::_errorExit("Action $pageAction nicht bekannt");
                break;
        }
    }

    public function saveCredentials($bankId, $username, $password, $keyPhrase, $name = ''){
        try {
            $encKey = KeyProtectedByPassword::createRandomPasswordProtectedKey($keyPhrase);
            $key = $encKey->unlockKey($keyPhrase);
            $credential_array = ['username' => $username, 'password' => $password];
            $credentialJson = json_encode($credential_array);
            $encCredentialJson = \Defuse\Crypto\Crypto::encrypt($credentialJson, $key);
        }catch (WrongKeyOrModifiedCiphertextException $ciphertextException){
            return $ciphertextException->getMessage();
        }

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
    }

    public function loadCredentials($credentialId) : bool
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

        if(count($res) === 1){
            $res = $res[0];
        }else{
            ErrorHandler::_errorExit("This should also not happen ...");
        }

        if(!isset($_SESSION['fints'][$credentialId]['key-password'])){
            ErrorHandler::_errorExit("Passwort für Credentials $credentialId benötigt");
        }

        $encryptedKeyString = $res['crypto_key'];
        $encryptedCredentials = $res['encrypted_credentials'];

        $credentialsJson = Crypto::decrypt_by_key_pw(
            $encryptedCredentials,
            $encryptedKeyString,
            $_SESSION['fints'][$credentialId]['key-password']
        );
        if($credentialsJson === false){
            return false;
        }
        $credentialArray = json_decode($credentialsJson, true);
        if(!isset($credentialArray['username'], $credentialArray['password'])){
            return false;
        }
        $credentials =  Credentials::create($credentialArray['username'], $credentialArray['password']);

        $options = new FinTsOptions();
        $options->url = $res['bank.url'];
        $options->bankCode = $res['bank.blz'];
        $options->productName = FINTS_REGNR;
        $options->productVersion = FINTS_SOFTWARE_VERSION;

        if(isset($_SESSION['fints'][$credentialId]['fints-persist'])){
            $persist = (string) $_SESSION['fints'][$credentialId]['fints-persist'];
            $fints = FinTs::new($options, $credentials, $persist);
        }else{
            $fints = FinTs::new($options, $credentials);
            if($res['default_tan_mode'] !== "null" && !is_null($res['default_tan_mode'])){
                $tanModeInt = (int) $res['default_tan_mode'];
                $fints->selectTanMode($tanModeInt);
            }
        }
        $this->fints = $fints;
        return true;
    }

    /**
     * @param BaseAction $action
     * @param int|string|null $actionId
     * @param string|null $state
     * @return array|bool - ActionID
     */
    private function saveActionToDB(BaseAction $action, $actionId = null, $state = null)
    {
        if(is_null($actionId)){ // if action is new
            $state = self::STATE_LOGIN;
            $actionId = DBConnector::getInstance()->dbInsert(
                'konto_action_log',
                [
                    'konto_id' => $this->kontoId,
                    'konto_credential_id' => $this->credentialId,
                    'state' => $state,
                    'action_class' => get_class($action),
                    'last_persist_fints' => $this->fints->persist(),
                    'timestamp' => date_create('now')->format('Y-m-d H:i:s'),
                ]
            );
            return [$actionId, $state];
        }
        $tan = [];
        if(isset($this->givenTan)){
            $tan = ['given_tan' => $this->givenTan];
        }
        $serializedAction = [];
        if($action->needsTan()){
            $serializedAction = ['action_serialized' => $action->serialize()];
        }

        $res = DBConnector::getInstance()->dbUpdate(
            'konto_action_log',
            ['id' => $actionId],
            [
                'state' => $state,
                'last_persist_fints' => $this->fints->persist(),
                'timestamp' => date_create('now')->format('Y-m-d H:i:s'),
            ] + $tan + $serializedAction
        );

        return $res !== false;

    }

    /**
     * @param int $actionId
     * @return array [BaseAction $action, string $state, string $actionClassName]
     */
    private function loadActionFromDB(int $actionId) : array
    {
        $row = DBConnector::getInstance()->dbFetchAll(
            'konto_action_log',
            [DBConnector::FETCH_ASSOC],
            ['*'],
            ['id' => $actionId]
        );
        $actionClassName = $row['action_class'];
        $action = new $actionClassName();

        if(!($action instanceof BaseAction)){
            ErrorHandler::_errorExit($row['action_class'] . 'is not a valid BaseAction');
        }

        $action->unserialize($row['action_serialized']);
        $this->fints->loadPersistedInstance($row['last_fints_persist']);
        $state = (string) $row['state'];

        return [$action, $state, $actionClassName];
    }


    /**
     * @throws CurlException
     * @throws ServerException
     */
    private function renderTanModePicker() {

        $tanModes = $this->fints->getTanModes();
        if (empty($tanModes)) {
            echo 'Your bank does not support any TAN modes!';
            return;
        }

        $tanModeNames = array_map(static function (TanMode $tanMode) {
            return "[" . $tanMode->getId(). "] " . $tanMode->getName();
        }, $tanModes);

        echo "<form method='post' action=" . URIBASE . "rest/konto/tan-mode/save class='ajax-form'>";
        $this->renderHeadline("Bitte TAN-Modus auswählen");
        $this->renderHiddenInput('credential-id', $this->credentialId);
        $this->renderRadioButtons($tanModeNames, 'tan-mode');
        $this->renderNonce();
        echo "<button class='btn btn-primary' type='submit'>Speichern</button>";
        echo "</form>";

    }

    private function renderTanMediumPicker()
    {
        $tanModeInt = (int) $this->routeInfo['tan-mode-id'];
        $this->fints->selectTanMode($tanModeInt);

        try {
            $tanMedium = $this->fints->getTanMedia($tanModeInt);
        } catch (InvalidArgumentException $e) {
            HTMLPageRenderer::redirect(URIBASE . "konto/credentials/");
            return;
        } catch (\Exception $e) {
            ErrorHandler::_errorExit($e->getMessage());
        }

        if (empty($tanMedium)) {
            HTMLPageRenderer::redirect(URIBASE . "konto/credentials/");
            return;
        }

        $tanModeNames = array_map(static function (TanMode $tanMode) {
            return "[" . $tanMode->getId(). "] " . $tanMode->getName();
        }, $tanMedium);

        echo "<form method='post' action=" . URIBASE . "rest/konto/tan-mode/save class='ajax-form'>";
        $this->renderHeadline("Bitte TAN-Modus auswählen");
        $this->renderHiddenInput('tan-mode-id', $this->credentialId);
        $this->renderHiddenInput('credential-id', $this->credentialId);
        $this->renderNonce();
        $this->renderRadioButtons($tanModeNames, 'tan-mode');
        echo "<button class='btn btn-primary' type='submit'>Speichern</button>";
        echo "</form>";
    }

    public function saveDefaultTanMode(int $credentialId, int $tanMode): bool
    {
        $tanModeName = $this->fints->getTanModes()[$tanMode]->getName();
        $ret = DBConnector::getInstance()->dbUpdate(
            'konto_credentials',
            ['id' => $credentialId],
            [
                'default_tan_mode' => $tanMode,
                'default_tan_mode_name' => $tanModeName,
            ]
        );
        return $ret === 1;
    }

    /**
     * @param BaseAction $action if provided will take this action instead of reload from db
     * @param int|null $actionId will receive Action from DB
     * @return false|BaseAction
     */
    private function prepareAction(BaseAction $action, $actionId = null)
    {
        if(!isset($_SESSION['fints'][$this->credentialId]['fints-persist']) && $this->loggedIn === false){
            // there was a login (attempt) before
            try {
                $loginAction = $this->fints->login();
                if($loginAction->needsTan()){
                    $this->renderTanInput($loginAction);
                    return false;
                }
                //TODO: log the login
                //$this->saveActionToDB($action, $actionId, self::STATE_INIT_ACTION);
                $this->loggedIn = true;
            }catch (ServerException $e){
                ErrorHandler::_errorExit("Fehler beim Login: " . $e->getMessage());
            }catch (\Exception $e){
                ErrorHandler::_errorExit($e->getMessage());
            }
        }
        // check if action in session is finished or unfinished
        if(isset($_SESSION['fints'][$this->credentialId]['action-serialized'], $_SESSION['fints'][$this->credentialId]['action-classname'])){
            $action->unserialize($_SESSION['fints'][$this->credentialId]['action-serialized']);
            if($action->isDone()){
                unset($_SESSION['fints'][$this->credentialId]['action-serialized'], $_SESSION['fints'][$this->credentialId]['action-classname']);
                if($_SESSION['fints'][$this->credentialId]['action-classname'] === get_class($action)){
                    // if this action was the main action return finished action
                    //session_write_close();
                    return $action;
                }
            }else{
                // unfinished action -> render tan input again
                $this->renderTanInput($action);
                return false;
            }
        }
        // login successful, no action in session found -> execute main action
        try {
            $this->fints->execute($action);
            if ($action->needsTan()) {
                $this->renderTanInput($action);
                return false;
            }
        } catch (CurlException $e) {
            ErrorHandler::_errorExit("Keine Verbindung zur Bank möglich");
        } catch (ServerException $e) {
            ErrorHandler::_errorExit("Konto Aktion konnte nicht ausgeführt werden " . $e->getMessage());
        }
        return $action;

    }

    /**
     * @param string $tan
     * @param int $credentialId
     * @return array [bool $success, string $msg]
     */
    public function submitTan(string $tan, int $credentialId) : array
    {
        if(isset($_SESSION['fints'][$credentialId]['action-serialized'], $_SESSION['fints'][$credentialId]['action-classname'])) {
            /** @var BaseAction $restoredAction */
            $restoredAction = new $_SESSION['fints'][$credentialId]['action-classname']();
            $restoredAction->unserialize($_SESSION['fints'][$credentialId]['action-serialized']);
            try {
                $this->fints->submitTan($restoredAction, $tan);
                $_SESSION['fints'][$credentialId]['fints-persist'] = $this->fints->persist();
                unset($_SESSION['fints'][$credentialId]['action-serialized'], $_SESSION['fints'][$credentialId]['action-classname']);
                //session_write_close();
                //TODO: log tan
                return $this->evaluateAction($restoredAction, $credentialId);

            } catch (CurlException $e) {
                ErrorHandler::_errorExit("Keine Verbindung zur Bank möglich");
            } catch (ServerException $e) {
                ErrorHandler::_errorExit("Tan nicht akzeptiert " . $e->getMessage());
            }
        }
        return [false, "Aktion kann nicht gefunden werden"];
    }

    private function renderNewCredentials()
    {
        $banks = DBConnector::getInstance()->dbFetchAll('konto_bank');
        $this->renderHeadline('Lege neue Zugangsdaten an');
        $this->renderAlert('Hinweis', [
            'Datenschutz, PW usw Hinweise hier'
        ], 'info');
        $searchAttr = count($banks) > 5 ? "data-live-search='true'" : "";
        ?>
        <form action="<?= URIBASE ?>rest/konto/credentials/save" method="POST" role="form" class="ajax-form">

            <div class="form-group">
                <label for="name">Name des Zugangs</label>
                <input id="name" name="name" type="text" class="form-control">
            </div>

            <div class="form-group">
                <label for="bank-id">Bank</label>
                <select id="bank-id" name='bank-id' class="selectpicker form-control" <?= $searchAttr ?>>
                    <?php foreach ($banks as $bank){
                        echo "<option data-subtext='BLZ: {$bank['blz']}' value='{$bank['id']}'>{$bank['name']}</option>";
                    } ?>
                </select>
            </div>

            <div class="form-group">
                <label for="bank-username">Bank Username</label>
                <input id="bank-username" name="bank-username" type="text" class="form-control">
            </div>
            <div class="form-group">
                <label for="bank-password">Bank Passwort</label>
                <input id="bank-password" name="bank-password" type="password" class="form-control">
            </div>
            <div class="form-group">
                <label for="password-custom">Eigenes Passwort</label>
                <input id="password-custom" name="password-custom" type="password" class="form-control">
            </div>
            <?php $this->renderNonce() ?>
            <button type="submit"
                    class="btn btn-primary  <?= (AUTH_HANDLER)::getInstance()->hasGroup(
                        "ref-finanzen-kv"
                    ) ? "" : "user-is-not-kv" ?>"
                <?= (AUTH_HANDLER)::getInstance()->hasGroup("ref-finanzen-kv") ? "" : "disabled" ?>>
                Speichern
            </button>
        </form>
        <?php
    }

    public function getIbans($credentialId)
    {

        if(isset($_SESSION['fints'][$this->credentialId]['ibans'])){
            return $_SESSION['fints'][$this->credentialId]['ibans'];
        }

        $action = GetSEPAAccounts::create();
        $action = $this->prepareAction($action);

        if($action === false){
            return false;
        }
        /** @var SEPAAccount[] $accounts */
        $accounts = $action->getAccounts();
        $allIbans = [];
        foreach ($accounts as $SEPAAccount){
            $allIbans[] = $SEPAAccount->getIban();
        }

        $_SESSION['fints'][$this->credentialId]['ibans'] = $allIbans;
        $_SESSION['fints'][$this->credentialId]['bic'] = $accounts[0]->getBic();
        //session_write_close();
        //session_start(); // TODO: FIXME?

        return $allIbans;
    }

    private function renderSepaAccountsList()
    {
        $allIbans = $this->getIbans($this->credentialId);
        if($allIbans === false) {
            return;
        }

        $dbAccounts = DBConnector::getInstance()->dbFetchAll(
            'konto_type',
            [DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY],
            ['iban', '*'],
            ['iban' => ['in', $allIbans]]
        );
        $tableRows = [];
        foreach ($allIbans as $iban){
            $tableRow = [
                'iban' => $iban,
                'bic' => $_SESSION['fints'][$this->credentialId]['bic'], // TODO: fetch from db
            ];
            if(isset($dbAccounts[$iban])){
                $matchingDbRow = $dbAccounts[$iban];
                if(is_null($matchingDbRow['sync_until'])){
                    $syncActive = true;
                }else{
                    $syncActive = date_create()->diff(date_create($matchingDbRow['sync_until']))->invert === 0;
                }
                $lastSyncString = !empty($matchingDbRow['last_sync']) ? $matchingDbRow['last_sync'] : "nie";
                $syncActiveString = $syncActive ? "letzer sync: " . $lastSyncString  : "Sync gestoppt";
                $tableRow['info'] = $matchingDbRow['short'] . $matchingDbRow['id'] . " " . $syncActiveString;
                $tableRow['action'] = "update";
            }else{
                $tableRow['info'] = "bisher nicht importiert";
                $tableRow['action'] = "import";
            }
            $tableRows[] = $tableRow;
        }

        $this->renderTable(
            ['IBAN', 'BIC', 'Info', 'Action'],
            [$tableRows],
            ['iban', 'bic', 'info', 'action', 'iban'],
            [
                null,
                null,
                null,
                static function ($actionName, $iban) : string
                {
                    $shortIban = substr($iban,0,4) . substr($iban, -4,4);
                    switch ($actionName){
                        case 'update':
                            return "<a href='./$shortIban'><span class='fa fa-fw fa-refresh' title='Kontostand aktualisieren'></span></a>";
                        case 'import':
                            return "<a href='./$shortIban/import'><span class='fa fa-fw fa-upload' title='Konto neu importieren'></span></a>";
                    }
                    return "error";
                }
            ]
        );

    }



    private function renderSepaDetails()
    {
        $action = GetSEPAAccounts::create();
        $action = $this->prepareAction($action);

        if($action === false){
            return false;
        }

        $iban = $this->getIbanFromShort($this->credentialId, $this->shortIBAN);
        if($iban === false) {
            return false;
        }

        /** @var SEPAAccount[] $accounts */
        $accounts = $action->getAccounts();
        $account = array_values(array_filter($accounts, static function (SEPAAccount $el) use ($iban){
            return $el->getIban() === $iban;
        }))[0];

        $dbKontos = DBConnector::getInstance()->dbFetchAll('konto_type',
            [DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY],
            ['iban', '*']
        );
        $dbKonto = $dbKontos[$iban];
        $syncFrom = new DateTime($dbKonto['sync_from']);
        $lastSync = $dbKonto['last_sync'];
        $syncUntil = $dbKonto['sync_until'];

        if($lastSync === "null" || $lastSync === null){
            $lastSync = clone $syncFrom;
        }else{
            $lastSync = date_create($lastSync);
        }

        if($syncUntil === "null" || $syncUntil === null){
            $syncUntil = date_create();
        }else{
            $syncUntil = date_create($syncUntil);
            if($syncUntil->diff(date_create())->invert === -1){
                $syncUntil = date_create();
            }else{
                $syncUntil = $syncUntil->add(new DateInterval('PT23H59M59S'));
            }
        }
        // add 23h 59m and 59s

        //find earliest
        if($syncFrom->diff($lastSync)->invert === 0){ //is last sync älter
            $startDate = $lastSync;
        }else{
            $startDate = $syncFrom;
        }

        $action = GetStatementOfAccount::create($account,$startDate, $syncUntil);

        $_SESSION['fints'][$this->credentialId]['param']['konto-id'] = $dbKonto['id'];

        $action = $this->prepareAction($action);
        if($action === false){
            return false;
        }
        var_dump($this->evaluateAction($action, $this->credentialId));
    }

    private function renderSepaActionPick()
    {
        $this->renderList([
            $this->internalHyperLinkEscapeFunction('Wähle TAN Modus', 'konto/credentials/' . $this->credentialId . "/tan-mode"),
            $this->internalHyperLinkEscapeFunction('Kontoauswahl', 'konto/credentials/' . $this->credentialId . "/sepa"),
        ], false, false );
    }

    private function renderCredentialPick()
    {
        $myCredentials = DBConnector::getInstance()->dbFetchAll(
            'konto_credentials',
            [DBConnector::FETCH_ASSOC],
            [
                'konto_credentials.id',
                'konto_credentials.name',
                'bank_name' => 'konto_bank.name',
                'default_tan_mode',
                'default_tan_mode_name'
            ],
            ['owner_id' => DBConnector::getInstance()->getUser()['id']],
            [['type' => 'inner', 'table' => 'konto_bank', 'on' => ['konto_bank.id', 'konto_credentials.bank_id']]]
        );
        echo "<a href='".URIBASE."konto/credentials/new' class='btn btn-primary'><span class='fa fa-fw fa-plus'></span> Neue Zugangsdaten anlegen</a>";
        $obj = $this;
        if(count($myCredentials) > 0) {
            $this->renderTable(
                ['ID', 'Name', 'Bank', 'Tanmodus', 'Action'],
                [$myCredentials],
                ['id', 'name', 'bank_name','default_tan_mode','default_tan_mode_name', 'id','id'],
                [
                    null,
                    null,
                    null,
                    static function($tanMode, $tanModeName, $id) use ($obj) {
                        $tanString = $obj->defaultEscapeFunction('[' . $tanMode . '] ' . $tanModeName);
                        return $tanString . " <a href='" . URIBASE . "konto/credentials/$id/tan-mode'><span class='fa fa-fw fa-pencil' title='Tan Modus auswählen'></span></a>";
                    },
                    static function($id){ // action
                        if(isset($_SESSION['fints'][$id]['key-password'])){
                            return
                                "<a href='" . URIBASE . "konto/credentials/$id/sepa'><span class='fa fa-fw fa-bank' title='Kontenübersicht'></span></a> " .
                                "<a href='" . URIBASE . "konto/credentials/$id/change-password'><span class='fa fa-fw fa-key' title='Passwort ändern'></span></a> " .
                                "<a href='" . URIBASE . "konto/credentials/$id/delete'><span class='fa fa-fw fa-trash' title='Zugangsdaten löschen'></span></a>";
                        }
                        return "<a href='" . URIBASE . "konto/credentials/$id/'><span class='fa fa-fw fa-unlock-alt' title='Entsperren'></span></a>";

                    }
                ]
            );
        }

        ?>
        <form action="<?= URIBASE ?>rest/clear-session" method="post" class="ajax-form">
            <?php $this->renderNonce() ?>
            <?php $this->renderHiddenInput('credential-id', $this->credentialId) ?>
            <button class="btn btn-warning" type="submit"><span class="fa fa-refresh"> Setze FINTS zurück</span></button>
        </form>
        <?php
    }

    private function saveNewSepaKontoImport() : bool
    {
        DBConnector::getInstance()->dbInsert(
            'konto_type',
            [
                ""
            ]
        );
        return false;
    }

    private function getIbanFromShort(int $credentialId, string $shortIban){
        $allIbans = $this->getIbans($credentialId);
        if($allIbans === false) {
            return false;
        }
        $ibanStart = substr($shortIban,0,4);
        $ibanEnd = substr($shortIban,-4,4);
        // return only first element -> very high possibility all have the same iban
        return array_values(array_filter($allIbans, static function(string $el) use ($ibanStart, $ibanEnd){
            return (strpos($el, $ibanStart) === 0) && (substr($el,-4,4) === $ibanEnd);
        }))[0];
    }

    private function renderNewSepaKontoImport()
    {
        $iban = $this->getIbanFromShort($this->credentialId, $this->shortIBAN);
        if($iban === false) {
            return;
        }

        $this->renderHeadline('Neues Konto Importieren');
        ?>
        <form action="<?= URIBASE ?>rest/konto/credentials/import-konto" method="POST" role="form" class="ajax-form">

            <div class="form-group">
                <label for="konto-iban">IBAN</label>
                <input id="konto-iban" name="konto-iban" type="text" class="form-control" value="<?= $iban ?>" disabled>
            </div>
            <div class="form-group">
                <label for="konto-name">Konto Name (intern)</label>
                <input id="konto-name" name="konto-name" type="text" maxlength="32" class="form-control">
            </div>
            <div class="form-group">
                <label for="sync-from">Startdatum der Syncronisation</label>
                <input id="sync-from" name="sync-from" type="date" class="form-control">
            </div>

            <div class="form-group">
                <label for="konto-short">Eindeutiges Buchstabenkürzel für das Konto (intern)</label>
                <input id="konto-short" name="konto-short" type="text" maxlength="2" class="form-control">
            </div>
            <?php $this->renderNonce() ?>
            <?php $this->renderHiddenInput('credential-id', $this->credentialId) ?>
            <button type="submit"
                    class="btn btn-primary  <?= (AUTH_HANDLER)::getInstance()->hasGroup(
                        "ref-finanzen-kv"
                    ) ? "" : "user-is-not-kv" ?>"
                <?= (AUTH_HANDLER)::getInstance()->hasGroup("ref-finanzen-kv") ? "" : "disabled" ?>>
                Speichern
            </button>
        </form>
        <?php
    }

    /**
     * @param $action BaseAction
     * @return void
     */
    private function renderTanInput(BaseAction $action)
    {
        $tanRequest = $action->getTanRequest();
        if(is_null($tanRequest)){
            ErrorHandler::_errorExit('Tan Request kann nicht ausgelesen werden' . var_export([$action, $_SESSION['fints']],true));
        }
        $mediumName = $tanRequest->getTanMediumName();
        $challengeText = $tanRequest->getChallenge();

        $this->renderHeadline("TAN benötigt");

        echo $mediumName . PHP_EOL . PHP_EOL;
        echo $challengeText;

        $challengePhotoBin = false;
        if (!is_null($tanRequest->getChallengeHhdUc())) {
            $challengeImage = new TanRequestChallengeImage(
                $tanRequest->getChallengeHhdUc()
            );
            $challengePhotoBinBase64 = base64_encode($challengeImage->getData());
            echo "<img src='data:image/jpg;base64,$challengePhotoBinBase64'>";
        }

        ?>
        <form method="post" action="<?= URIBASE ?>rest/konto/credentials/submit-tan" class="ajax-form">
            <?php $this->renderHiddenInput('credential-id', $this->credentialId) ?>
            <?php $this->renderNonce() ?>
            <div class="form-group">
                <label for="tan-input">TAN</label>
                <input id="tan-input" name="tan" type="text" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Absenden</button>
        </form>
        <?php
        // save persisant stuff for later
        $_SESSION['fints'][$this->credentialId]['fints-persist'] = $this->fints->persist();
        $_SESSION['fints'][$this->credentialId]['action-serialized'] = $action->serialize();
        $_SESSION['fints'][$this->credentialId]['action-classname'] = get_class($action);
        echo "<pre>" . var_export($_SESSION['fints'][$this->credentialId]['action-classname'],true) . "</pre>";
        //session_write_close();
    }

    private function renderPasswordForm(int $credentialId)
    {
        $this->renderHeadline('Bitte internes Passwort eingeben');

        ?>
        <form method="post" action="<?= URIBASE?>rest/konto/credentials/unlock" class="ajax-form">
            <div class="form-group">
                <label for="credential-key">Passwort für Zugang Nr <?= $credentialId ?>:</label>
                <input name="credential-key" id="credential-key" type="password" class="form-control">
            </div>
            <?php $this->renderHiddenInput('credential-id', $credentialId);
            $this->renderNonce();
            ?>
            <button class="btn btn-primary" type="submit">Absenden</button>
        </form>
        <?php

    }

    private function close() : bool {
        try {
            $this->fints->close();
            return true;
        } catch (ServerException $e) {
            return false;
        }
    }

    /**
     * @param BaseAction $action
     * @param int $credentialId
     * @return array [bool $success, string $msg]
     */
    private function evaluateAction(BaseAction $action, int $credentialId) : array
    {
        $param = $_SESSION['fints'][$credentialId]['param'];

        if(!$action->isDone()){
            return [false, "Aktion ". get_class($action) . " benötigt immer noch eine TAN - ausführen nicht möglich"];
        }

        switch (get_class($action)){

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
                $dateString = null;

                if(!empty($lastKontoRow)){
                    $lastKontoRow = $lastKontoRow[0];
                    $lastKontoId = $lastKontoRow['id'];
                    $lastKontoSaldo = $lastKontoRow['saldo'];
                    $oldSaldoCent = $this->convertToCent($lastKontoSaldo, 'credit');
                }

                $db->dbBegin();

                foreach ($statements->getStatements() as $statement) {
                    $dateString = $statement->getDate()->format('Y-m-d');
                    $saldoCent = $this->convertToCent($statement->getStartBalance(), $statement->getCreditDebit());
                    if($oldSaldoCent !== null && $oldSaldoCent !== $saldoCent){
                        $db->dbRollBack();
                        return [false, "$oldSaldoCent !== $saldoCent at statement from $dateString"];
                    }
                    //echo "Statement $dateString Saldo: $saldoCent";
                    foreach ($statement->getTransactions() as $transaction) {
                        $valCent = $this->convertToCent($transaction->getAmount(), $transaction->getCreditDebit());
                        $saldoCent += $valCent;
                        $transactionData = [
                            'id' => ++$lastKontoId,
                            'konto_id' => $param['konto-id'],
                            // TODO: PHP 8.0: nullsafe operator ?-> for format()
                            'date' => $transaction->getBookingDate()->format('Y-m-d'),
                            'valuta' => $transaction->getValutaDate()->format('Y-m-d'),
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
                        $db->dbInsert('konto', $transactionData);

                    }
                    $oldSaldoCent = $saldoCent;
                }
                // note timestamp for next sync
                $kontoRow = $db->dbFetchAll('konto_type', [DBConnector::FETCH_ASSOC], ['*'],['id' => $kontoId])[0];
                $syncUntil = $kontoRow['sync_until'];
                $date = date_create();
                if($syncUntil !== 'null' && $syncUntil !== null){
                    $syncUntil = date_create($syncUntil . " +1 day");
                    if($date->diff($syncUntil)->invert === 1){
                        $date = $syncUntil;
                    }
                }
                $db->dbUpdate('konto_type', ['id' => $kontoId], ['last_sync' => $date->format('Y-m-d')]);
                $ret = $db->dbCommitRollbackOnFailure();

                if($ret === true){
                    $msg = $db->dbGetWriteCounter()-1 . " Einträge importiert";
                }else{
                    $msg = "Ein Fehler ist aufgetreten - DBRollback - Import von " .
                        ($db->dbGetWriteCounter()-1). " Einträgen ausstehend";
                }
                return[$ret, $msg];
                break;

            case BaseAction::class: // login Action
                return [true, "Login erfolgreich"];
                break;
            default:
                return [false, "Aktion " . get_class($action) . " nicht bekannt, kann nicht ausgeführt werden"];
                break;
        }
    }

    private function convertToCent($amount, $creditDebit){
        $float = (float) $amount;
        $int = (int) round($float * 100);
        return ($creditDebit === Statement::CD_DEBIT ? -1 : 1) * $int;
    }

    private function convertCentForDB(int $amount) : string
    {
        // rounds implicit
        return number_format($amount / 100.0,2, '.', '');
    }
}