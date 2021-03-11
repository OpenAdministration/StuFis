<?php


use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Fhp\Action\GetSEPAAccounts;
use Fhp\Action\GetStatementOfAccount;
use Fhp\BaseAction;
use Fhp\CurlException;
use Fhp\FinTs;
use Fhp\Model\SEPAAccount;
use Fhp\Options\Credentials;
use Fhp\Options\FinTsOptions;
use Fhp\Protocol\ServerException;

require_once(FRAMEWORK_PATH.'/external_libraries/crypto/defuse-crypto.phar');

class FinTSHandler extends Renderer
{

    const ACTION_GET_TRANSACTIONS = 'get-transactions';
    const ACTION_GET_ACCOUNTS = 'get-accounts';

    const STATE_CREATED = 'created';
    const STATE_WAIT_FOR_TAN = 'wait-for-tan';
    const STATE_READY_TO_EXECUTE = 'execute';
    const STATE_DONE = 'done';

    /** @var FinTs */
    private $fints;
    /** @var \Fhp\Options\Credentials */
    private $credentials;
    /** @var Fhp\Options\FinTsOptions */
    private $options;

    private $credentialId;

    private $routeInfo;
    private $kontoId;
    private $keyPhrase;


    public function __construct(array $routeInfo)
    {
        $this->routeInfo = $routeInfo;
        $this->keyPhrase = 'test';
        $this->kontoId = '999';

        if(isset($this->routeInfo['credential-id'])){
            $this->credentialId =  $this->routeInfo['credential-id'];
            $this->loadOptions($this->credentialId);
            $this->loadCredentials($this->credentialId);
            $this->loadFinTS();
        }
    }

    public function render() : void
    {
        $pageAction = $this->routeInfo['action'];

        switch ($pageAction){
            case 'new-credentials':
                $this->renderNewCredentials();
                break;
            case 'pick-sepa-konto':
                $this->renderSepaAccountPicker();
                break;
            case 'pick-tan-mode':
                $this->renderTanModePicker();
                break;
        }
    }

    public static function getCredentialIDsByUser($userId){
        return DBConnector::getInstance()->dbFetchAll(
            'konto_credentials',
            [DBConnector::FETCH_ONLY_FIRST_COLUMN],
            ['id'],
            ['owner_id' => $userId]
        );
    }

    private function loadOptions($credentialId){
        $db = DBConnector::getInstance();
        $res = $db->dbFetchAll(
            'konto_bank',
            [DBConnector::FETCH_ASSOC],
            ['konto_bank.*'],
            ['konto_credentials.id' => $credentialId],
            [['type' => 'inner', 'table' => 'konto_credentials', 'on' => ['konto_credentials.bank_id', 'konto_bank.id']]]
        );

        if(count($res) === 1){
            $res = $res[0];
        }else{
            ErrorHandler::_renderError("This should not happen ...");
        }

        $options = new FinTsOptions();
        $options->url = $res['url'];
        $options->bankCode = $res['blz'];
        $options->productName = FINTS_REGNR;
        $options->productVersion = FINTS_SOFTWARE_VERSION;
        $this->options = $options;
    }

    public function saveCredentials($bankId, $username, $password, $keyPhrase, $name = ''){
        try {
            $encKey = \Defuse\Crypto\KeyProtectedByPassword::createRandomPasswordProtectedKey($keyPhrase);
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

    private function loadCredentials($credentialId){
        $db = DBConnector::getInstance();
        $res = $db->dbFetchAll('konto_credentials',
            [DBConnector::FETCH_ASSOC],
            [
                '*'
            ],[
                'owner_id' => $db->getUser()["id"],
                'id' => $credentialId,
            ]
        );

        if(count($res) === 1){
            $res = $res[0];
        }else{
            ErrorHandler::_renderError("This should also not happen ...");
        }

        $encryptedKeyString = $res['crypto_key'];
        $encryptedCredentials = $res['encrypted_credentials'];

        $credentialsJson = Crypto::decrypt_by_key_pw($encryptedCredentials, $encryptedKeyString, $this->keyPhrase);
        $credentialArray = json_decode($credentialsJson, true);
        $this->credentials =  Credentials::create($credentialArray['username'], $credentialArray['password']);
    }

    private function loadFinTS(): void
    {
        $fints = FinTs::new($this->options, $this->credentials);
        $this->fints = $fints;
    }

    public function createTan(BaseAction $action){

        $tanRequest = $action->getTanRequest();

        if(is_null($tanRequest)) {
            return;
        }

        $challengeText = $tanRequest->getChallenge();

        $challengePhotoBin = false;

        if (is_null($tanRequest->getChallengeHhdUc())) {
            $challengeImage = new \Fhp\Model\TanRequestChallengeImage(
                $tanRequest->getChallengeHhdUc()
            );

            $challengePhotoBin = $challengeImage->getData();
        }

        $persistedAction = serialize($action);
        $persistedInstance = $this->fints->persist();
        // TODO: save serialized Tan and FINTS
    }

    public function saveAction(BaseAction $action, ?int $actionId, ?string $state) : int
    {
        switch (true){
            case $action instanceof GetSEPAAccounts:
                $actionName = self::ACTION_GET_ACCOUNTS;
                break;

        }
        if(is_null($actionId)){ // if action is new
            return DBConnector::getInstance()->dbInsert(
                'konto_action_log',
                [
                    'konto_id' => $this->kontoId,
                    'konto_credential_id' => $this->credentialId,
                    'state' => $state,
                    'action_name' => $actionName,
                    'action_serialized' => $action->serialize(),
                    'last_persist_fints' => $this->fints->persist(),
                    'timestamp' => date_create('now'),
                ]
            );
        }

        $res = DBConnector::getInstance()->dbUpdate(
           'konto_action_log',
           ['id' => $actionId],
           [
               'state' => $state,
               'action_serialized' => $action->serialize(),
               'last_persist_fints' => $this->fints->persist(),
               'timestamp' => date_create('now'),
           ]
       );
        return  $actionId; // Fixme ?

    }

    private function renderTanModePicker() : void{

        $bpd = $this->fints->getBpd();
        $tanModes = $this->fints->getTanModes();
        if (empty($tanModes)) {
            echo 'Your bank does not support any TAN modes!';
            return;
        }

        echo "Here are the available TAN modes:\n";
        echo "<pre>" .var_export($bpd,true) . "</pre>";
        echo "<pre>" .var_export($tanModes,true) . "</pre>";
        /*
        $tanModeNames = array_map(static function (\Fhp\Model\TanMode $tanMode) {
            return $tanMode->getName();
        }, $tanModes);
        print_r($tanModeNames);
        */
    }

    public function saveDefaultTanMode(int $credentialId, int $tanMode): bool
    {
        $ret = DBConnector::getInstance()->dbUpdate(
                'konto_credentials',
                ['id' => $credentialId],
                ['default_tan_mode' => $tanMode]
        );
        return $ret !== false;
    }

    /**
     * @param BaseAction $action if provided will take this action instead of reload from db
     * @param int|null $actionId will recieve Action from DB
     */
    public function processAction(BaseAction $action, ?int $actionId = null) : void
    {

        if(isset($actionId)){
            $row = DBConnector::getInstance()->dbFetchAll(
                'konto_action_log',
                [DBConnector::FETCH_ASSOC],
                ['*'],
                ['id' => $actionId]
            );
            switch ($row['action_name']){
                case self::ACTION_GET_ACCOUNTS:
                    $action = new GetSEPAAccounts();
                    break;
                case self::ACTION_GET_TRANSACTIONS:
                    $action = new GetStatementOfAccount();
                    break;
                default:
                    ErrorHandler::_renderError('');
                    break;
            }

            $action->unserialize($row['action_serialized']);
            $this->fints->loadPersistedInstance($row['last_fints_persist']);
            $state = $row['state'];
            $tanmode = $row['tanmode'];
        }else{
            $state = self::STATE_CREATED;
        }

        switch ($state){
            case self::STATE_CREATED:
                $this->fints->login();
                $this->fints->execute($action);
                if ($action->needsTan()) {
                    $this->renderTanInput($action);
                    break;
                }
            //fall-trough if no tan is needed
            case self::STATE_READY_TO_EXECUTE:
                switch (get_class($action)){
                    case \Fhp\Action\GetSEPAAccounts::class:
                        $this->getAccounts($action);
                        break;
                    case \Fhp\Action\GetStatementOfAccount::class:
                        $this->getAccountTransactions($action, null); //FIXME
                        break;
                }
                break;

            case self::STATE_WAIT_FOR_TAN:
                break;
        }
    }

    private function getAccounts(GetSEPAAccounts $action)
    {
        return $action->getAccounts();
    }


    private function getAccountTransactions(\Fhp\Action\GetStatementOfAccount $action, SEPAAccount $account)
    {

    }

    private function renderNewCredentials() : void
    {
        $banks = DBConnector::getInstance()->dbFetchAll('konto_bank');
        $this->renderHeadline('Lege neue Zugangsdaten an');
        $this->renderAlert('Hinweis', [
                'Datenschutz, PW usw Hinweise hier'
        ], 'info');
        //
        ?>
        <form action="<?= URIBASE ?>rest/konto/credentials/save" method="POST" role="form" class="ajax-form">

            <div class="form-group">
                <label for="name">Name des Zugangs</label>
                <input id="name" name="name" type="text" class="form-control">
            </div>

            <div class="form-group">
                <label for="bank-id">Bank</label>
                <select id="bank-id" name='bank-id' class="selectpicker form-control">
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
            <?= $this->renderNonce() ?>
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

    private function renderSepaAccountPicker()
    {
        $action = GetSEPAAccounts::create();
        $this->processAction($action);
    }

    private function renderTanInput(BaseAction $action)
    {
    }
}