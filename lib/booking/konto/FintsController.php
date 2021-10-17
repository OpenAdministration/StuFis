<?php

namespace booking\konto;

use Exception;
use Fhp\BaseAction;
use Fhp\FinTs;
use Fhp\Model\SEPAAccount;
use Fhp\Model\StatementOfAccount\Statement;
use Fhp\Model\TanMedium;
use Fhp\Model\TanMode;
use Fhp\Model\TanRequestChallengeImage;
use Fhp\Segment\TAB\TanMediumListe;
use Fhp\Segment\TAB\TanMediumListeV4;
use framework\auth\AuthHandler;
use framework\DBConnector;
use framework\render\ErrorHandler;
use framework\render\html\Html;
use framework\render\html\HtmlButton;
use framework\render\html\HtmlForm;
use framework\render\html\HtmlImage;
use framework\render\html\HtmlInput;
use framework\render\HTMLPageRenderer;
use framework\render\Renderer;

class FintsController extends Renderer
{
    private $credentialId;

    protected $routeInfo;

    private $shortIBAN;

    /** @var FintsConnectionHandler $fintsConnection */
    private $fintsConnection;

    public function __construct(array $routeInfo)
    {
        $this->routeInfo = $routeInfo;

        if(isset($this->routeInfo['credential-id'])){
            $this->credentialId = (int) $this->routeInfo['credential-id'];
        }

        if(isset($this->routeInfo['short-iban'])){
            $this->shortIBAN = $this->routeInfo['short-iban'];
        }
    }

    public function render() : void
    {
        $pageAction = $this->routeInfo['action'];

        // accessible without password
        switch ($pageAction){
            case 'pick-my-credentials':
                $this->renderCredentialPick();
                return;
            case 'new-credentials':
                $this->renderNewCredentials();
                return;
        }

        if(!FintsConnectionHandler::loadable($this->credentialId)){
            $this->renderPasswordForm($this->credentialId);
            return;
        }
        $this->fintsConnection = FintsConnectionHandler::load($this->credentialId);
        try {
            //only accessible with password
            switch ($pageAction){
                case 'pick-sepa-konto':
                    $this->renderSepaAccountsList();
                    break;
                case 'pick-tan-mode':
                    $this->renderTanModePicker();
                    break;
                case 'pick-tan-medium':
                    $this->renderTanMediumPicker();
                    break;
                case 'import-new-sepa-statements':
                    $this->actionImportNewSepaStatements();
                    break;
                case 'new-sepa-konto-import':
                    $this->renderNewSepaKontoImport();
                    break;
                case 'change-password':
                    $this->renderChangePassword();
                    break;
                default:
                    ErrorHandler::handleError(404,"Action $pageAction nicht bekannt");
                    break;
            }
        } catch (NeedsTanException $exception) {
            $this->renderTanInput($exception->getAction());
        } catch (Exception $exception){
            ErrorHandler::handleException($exception, 'something went wrong in FINTS controller');
        }
    }

    private function renderTanModePicker(): void
    {
        $tanModes = $this->fintsConnection->getTanModes();

        $form = HtmlForm::make()->urlTarget(URIBASE . 'rest/konto/tan-mode/save');
        echo $form->begin();
        $this->renderHeadline("Bitte TAN-Modus auswählen");
        $this->renderHiddenInput('credential-id', $this->credentialId);
        $this->renderRadioButtons($tanModes, 'tan-mode-id');
        $this->renderNonce();
        echo HtmlButton::make('submit')
            ->body('Speichern')
            ->style('primary');
        echo $form->end();
    }

    private function renderTanMediumPicker(): void
    {
        $tanModeInt = (int) $this->routeInfo['tan-mode-id'];

        $tanMedien = $this->fintsConnection->getTanMedia($tanModeInt);

        if (empty($tanMedien)) {
            HTMLPageRenderer::redirect(URIBASE . "konto/credentials/");
            return;
        }
        $tanMediumNames = [];

        foreach ($tanMedien as $tanMedium){
            /** @var TanMedium $tanMedium */
            $name = $tanMedium->getName();
            $tanModeNames[$name] = "[$name] {$tanMedium->getPhoneNumber()}";
        }

        echo "<form method='post' action=" . URIBASE . "rest/konto/tan-mode/save class='ajax-form'>";
        $this->renderHeadline("Bitte TAN-Modus auswählen");
        $this->renderHiddenInput('tan-mode-id', $this->credentialId);
        $this->renderHiddenInput('credential-id', $this->credentialId);
        $this->renderNonce();
        $this->renderRadioButtons($tanMediumNames, 'tan-medium-name');
        echo "<button class='btn btn-primary' type='submit'>Speichern</button>";
        echo "</form>";
    }

    private function renderNewCredentials(): void
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
                    class="btn btn-primary  <?= AuthHandler::getInstance()->hasGroup(
                        "ref-finanzen-kv"
                    ) ? "" : "user-is-not-kv" ?>"
                <?= AuthHandler::getInstance()->hasGroup("ref-finanzen-kv") ? "" : "disabled" ?>>
                Speichern
            </button>
        </form>
        <?php
    }



    /**
     * @throws NeedsTanException
     */
    private function renderSepaAccountsList(): void
    {
        $allIbans = $this->fintsConnection->getIbans();

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
        $credId = $this->credentialId;
        $this->renderTable(
            ['IBAN', 'BIC', 'Info', 'Action'],
            [$tableRows],
            ['iban', 'bic', 'info', 'action', 'iban'],
            [
                null,
                null,
                null,
                function ($actionName, $iban) use ($credId) : string
                {
                    $shortIban = substr($iban,0,4) . substr($iban, -4,4);
                    switch ($actionName){
                        case 'update':
                            return "<a href='" . URIBASE . "konto/credentials/$credId/$shortIban'><span class='fa fa-fw fa-refresh' title='Kontostand aktualisieren'></span></a>";
                        case 'import':
                            return "<a href='" . URIBASE . "konto/credentials/$credId/$shortIban/import'><span class='fa fa-fw fa-upload' title='Konto neu importieren'></span></a>";
                    }
                    return "error";
                }
            ]
        );

        echo HtmlButton::make()
            ->style('primary')
            ->body('zurück')
            ->icon('chevron-left')
            ->asLink(URIBASE . 'konto/credentials');
    }


    /**
     * @throws NeedsTanException
     */
    private function actionImportNewSepaStatements(): void
    {
        [$success, $msg] = $this->fintsConnection->saveNewSepaStatements($this->shortIBAN);

        echo HtmlButton::make()
            ->style('primary')
            ->body('zurück')
            ->icon('chevron-left')
            ->asLink(URIBASE . 'konto/credentials/' . $this->credentialId);

        if($success){
            $this->renderAlert('Erfolg', $msg);
        } else {
            $this->renderAlert('Fehler', $msg, 'danger');
        }
    }

    private function renderCredentialPick(): void
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
        echo HtmlButton::make()
            ->asLink(URIBASE.'konto/credentials/new')
            ->style('primary')
            ->icon('plus')
            ->body('Neue Zugangsdaten anlegen');
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
        echo HtmlForm::make()
            ->urlTarget( URIBASE . "rest/clear-session")
            ->body(
                HtmlButton::make()
                    ->style('warning')
                    ->icon('refresh')
                    ->body('Setze FINTS zurück')
            ,false);
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

    /**
     * @throws NeedsTanException
     */
    private function renderNewSepaKontoImport(): void
    {
        $iban = $this->fintsConnection->getIbanFromShort($this->shortIBAN);

        $this->renderHeadline('Neues Konto Importieren');
        $auth = AuthHandler::getInstance();
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
                <label for="sync-from">Startdatum der Synchronisation</label>
                <input id="sync-from" name="sync-from" type="date" class="form-control">
            </div>

            <div class="form-group">
                <label for="konto-short">Eindeutiges Buchstabenkürzel für das Konto (intern)</label>
                <input id="konto-short" name="konto-short" type="text" maxlength="2" class="form-control">
            </div>
            <?php $this->renderNonce() ?>
            <?php $this->renderHiddenInput('credential-id', $this->credentialId) ?>
            <button type="submit"
                    class="btn btn-primary  <?= $auth->hasGroup(
                        "ref-finanzen-kv"
                    ) ? "" : "user-is-not-kv" ?>"
                <?= $auth->hasGroup("ref-finanzen-kv") ? "" : "disabled" ?>>
                Speichern
            </button>
        </form>
        <?php
    }

    /**
     * @param $action BaseAction
     * @return void
     */
    private function renderTanInput(BaseAction $action): void
    {
        $tanRequest = $action->getTanRequest();
        if(is_null($tanRequest)){
            ErrorHandler::handleError(500,'Tan Request kann nicht ausgelesen werden', var_export([$action, $_SESSION['fints']],true));
        }
        $mediumName = $tanRequest->getTanMediumName();
        $challengeText = $tanRequest->getChallenge();

        echo Html::headline(1)->body('TAN benötigt');

        echo Html::headline(3)->body($mediumName);
        echo Html::p()->body($challengeText, false);
        $challengeBinary = $tanRequest->getChallengeHhdUc();
        if (!is_null($challengeBinary)) {
            if(strlen($challengeBinary->getData()) < 40){
                $flicker = new TanRequestChallengeFlicker($challengeBinary);
            }else{
                $challengeImage = new TanRequestChallengeImage($challengeBinary);
                $challengePhotoBinBase64 = base64_encode($challengeImage->getData());
                echo HtmlImage::make('TAN Challenge Bild')->srcBase64Encoded($challengePhotoBinBase64, $challengeImage->getMimeType());
            }
        }
        HtmlForm::make()
            ->urlTarget(URIBASE . 'rest/konto/credentials/submit-tan')
            ->hiddenInput('credential-id', $this->credentialId)
            ->addHtmlEntity(
                HtmlInput::make('text')
            )
        ?>
        <form method="post" action="<?= URIBASE ?>rest/konto/credentials/submit-tan" class="ajax-form">
            <?php $this->renderHiddenInput('credential-id', $this->credentialId) ?>
            <?php $this->renderNonce() ?>
            <div class="form-group">
                <label for="tan-input">TAN</label>
                <input id="tan-input" name="tan" type="text" class="form-control">
            </div>
            <button type="submit" class="btn btn-success">Absenden</button>
        </form>
        <?php
        echo HtmlForm::make()
            ->urlTarget(URIBASE . 'rest/konto/credentials/abort-tan')
            ->hiddenInput('credential-id', $this->credentialId)
            ->addHtmlEntity(
                HtmlButton::make()
                    ->style('secondary')
                    ->body('TAN Verfahren abbrechen')
            );
        //session_write_close();
    }

    private function renderPasswordForm(int $credentialId): void
    {
        $this->renderHeadline('Bitte internes Passwort eingeben');
        HtmlForm::make()
            ->urlTarget(URIBASE . 'rest/konto/credentials/unlock')
            ->addHtmlEntity(
                HtmlInput::make('password')
                        ->name('credential-key')
            )->addHtmlEntity(
                HtmlButton::make()
            );
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

    private function renderChangePassword(): void
    {
        $this->renderHeadline('Internes Passwort wechseln');
        echo HtmlForm::make()
            ->urlTarget(URIBASE . 'rest/konto/credentials/change-password')
            ->addHtmlEntity(
                HtmlInput::make('password')->name('new-password')->label('Neues Passwort')
            )->addHtmlEntity(
                HtmlInput::make('password')->name('new-password-repeat')->label('Passwort wiederholen')
            )->addHtmlEntity(
                HtmlButton::make()->body('Test')->style('primary')
            );
    }
}