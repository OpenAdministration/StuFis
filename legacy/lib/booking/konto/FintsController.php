<?php

namespace booking\konto;

use App\Exceptions\LegacyRedirectException;
use App\Models\FintsInstitute;
use booking\konto\tan\FlickerGenerator;
use Fhp\Model\StatementOfAccount\Statement;
use Fhp\Model\StatementOfAccount\StatementOfAccount;
use Fhp\Model\TanRequest;
use Fhp\Model\TanRequestChallengeImage;
use forms\projekte\auslagen\AuslagenHandler2;
use framework\ArrayHelper;
use framework\DateHelper;
use framework\DBConnector;
use framework\render\html\BT;
use framework\render\html\FA;
use framework\render\html\Html;
use framework\render\html\HtmlButton;
use framework\render\html\HtmlCard;
use framework\render\html\HtmlDropdown;
use framework\render\html\HtmlForm;
use framework\render\html\HtmlImage;
use framework\render\html\HtmlInput;
use framework\render\HTMLPageRenderer;
use framework\render\Renderer;
use InvalidArgumentException;

class FintsController extends Renderer
{
    private ?FintsConnectionHandler $fintsHandler = null;

    private ?int $credentialId;

    public function __construct(array $routeInfo = [])
    {
        $this->credentialId = $routeInfo['credential-id'] ?? null;
        if ($this->credentialId !== null && FintsConnectionHandler::hasPassword($this->credentialId)) {
            $this->fintsHandler = FintsConnectionHandler::load($this->credentialId);
        }
        parent::__construct($routeInfo);
    }

    public function render(): void
    {
        $this->requireValidNonce();
        $post = $this->request->request;
        if ($post->has('tan')) {
            // Banks print TANs in groups ("123 456"), so drop whitespace - but nothing
            // else: some TAN schemes are alphanumeric, and silently dropping characters
            // would burn one of the three attempts the bank grants.
            $this->requireFintsHandler()->submitTan(preg_replace('/\s+/', '', $post->get('tan', '')));
        }
        try {
            parent::render();
        } catch (NeedsTanException $e) {
            $this->requireFintsHandler()->logger->info('Tan needed', ['exception' => $e]);
            $this->renderTanInput($e->getMessage(), $e->getTanRequest());
        }
    }

    /**
     * The legacy route group runs without Laravel's CSRF middleware (see bootstrap/app.php),
     * and although every form here ships a `nonce` field holding csrf_token(), only
     * RestHandler ever checked it - the actions in this controller did not. That left the
     * bank access open to cross-site requests: forced login attempts (three failures lock
     * the online banking access at the bank), creating credentials, and registering an
     * arbitrary account for synchronisation.
     *
     * Verifying it here keeps the fix to the FinTS pages instead of switching the middleware
     * for the whole legacy group, which is not a patch-release-sized change.
     */
    private function requireValidNonce(): void
    {
        if ($this->request->getMethod() !== 'POST') {
            return;
        }

        $nonce = (string) $this->request->request->get('nonce', '');
        if ($nonce !== '' && hash_equals((string) csrf_token(), $nonce)) {
            return;
        }

        // LegacyDieException would surface as a bare 500 page (LegacyController rethrows it
        // and it carries no HTTP status of its own), so the request is refused with an
        // explanation instead. Either way the action does not run.
        HTMLPageRenderer::addFlash(
            BT::TYPE_DANGER,
            'Die Anfrage wurde abgelehnt',
            'Das Formular war nicht mehr gültig - vermutlich ist die Sitzung abgelaufen. '.
            'Bitte lade die Seite neu und versuche es erneut.'
        );

        throw new LegacyRedirectException(redirect()->route('legacy.konto.credentials'));
    }

    /**
     * The bank password is only ever held in the session, so it is gone as soon as the
     * session expires - and every action below needs it. Dereferencing the handler
     * regardless used to raise "Typed property must not be accessed before
     * initialization", i.e. an error page. Send the user back to the login instead.
     */
    private function requireFintsHandler(): FintsConnectionHandler
    {
        if ($this->fintsHandler instanceof FintsConnectionHandler) {
            return $this->fintsHandler;
        }

        HTMLPageRenderer::addFlash(
            BT::TYPE_INFO,
            'Die Verbindung zur Bank ist nicht mehr aktiv - vermutlich ist die Sitzung abgelaufen. Bitte melde dich erneut an.'
        );

        throw new LegacyRedirectException($this->credentialId === null
            ? redirect()->route('legacy.konto.credentials')
            : redirect()->route('legacy.konto.credentials.login', $this->credentialId));
    }

    private function renderTanInput(string $msg, TanRequest $tanRequest): void
    {
        $mediumName = $tanRequest->getTanMediumName() ?? '';
        $challengeText = $tanRequest->getChallenge();

        echo Html::headline(1)->body($msg);

        echo Html::headline(3)->body($mediumName);
        echo Html::p()->body($challengeText, false);
        $challengeBinary = $tanRequest->getChallengeHhdUc();
        if (! is_null($challengeBinary)) {
            try {
                $p = new FlickerGenerator($challengeBinary->getData());
                echo $p->getSVG(10, 300);
            } catch (InvalidArgumentException $e1) {
                try {
                    $challengeImage = new TanRequestChallengeImage($challengeBinary);
                    $challengePhotoBinBase64 = base64_encode($challengeImage->getData());
                    echo HtmlImage::make('TAN Challenge Bild')
                        ->srcBase64Encoded($challengePhotoBinBase64, $challengeImage->getMimeType());
                } catch (InvalidArgumentException $e2) {
                    echo 'Tan Format unbekannt'.PHP_EOL;
                    if (DEV) {
                        echo 'Challenge Binary: '.$challengeBinary.PHP_EOL;
                    }
                }
            }
        }
        echo HtmlForm::make('POST', false)
            ->urlTarget(request()?->url())
            ->addHtmlEntity(HtmlInput::make('text')->label('TAN')->name('tan'))
            ->addSubmitButton();
    }

    /**
     * Action to render fints home screen
     */
    protected function actionViewCredentials(): void
    {
        $myCredentials = DBConnector::getInstance()->dbFetchAll(
            'konto_credentials',
            [DBConnector::FETCH_ASSOC],
            [
                'konto_credentials.id',
                'konto_credentials.name',
                'bank_name' => 'fints_institutes.name',
                'tan_mode',
                'tan_mode_name',
                'tan_medium_name',
            ],
            ['owner_id' => \Auth::user()->id],
            [[
                'type' => 'inner',
                'table' => 'fints_institutes',
                'on' => ['fints_institutes.blz', 'konto_credentials.blz'],
            ]]
        );
        echo HtmlButton::make()
            ->asLink(URIBASE.'konto/credentials/new')
            ->style('primary')
            ->icon('plus')
            ->body('Neue Zugangsdaten anlegen');
        $obj = $this;
        if (count($myCredentials) > 0) {
            $this->renderTable(
                ['ID', 'Name', 'Bank', 'Tanmodus', 'Action'],
                [$myCredentials],
                ['id', 'name', 'bank_name', 'tan_mode', 'tan_mode_name', 'tan_medium_name', 'id', 'id'],
                [
                    null,
                    null,
                    null,
                    static function ($tanMode, $tanModeName, $tanMediumName, $id) use ($obj) {
                        $tanString = '['.$obj->defaultEscapeFunction($tanMode).'] '.$obj->defaultEscapeFunction($tanModeName);
                        if (isset($tanMediumName)) {
                            $tanString .= ': '.$obj->defaultEscapeFunction($tanMediumName);
                        }
                        if (FintsConnectionHandler::hasActiveSession($id)) {
                            $tanString .= ' '.FA::make('fa-pencil')->href(URIBASE."konto/credentials/$id/tan-mode")->title('TAN Modus auswählen');
                        }

                        return $tanString;
                    },
                    static function ($id) { // action
                        if (FintsConnectionHandler::hasActiveSession($id)) {
                            return
                                "<a href='".URIBASE."konto/credentials/$id/sepa'><span class='fa fa-fw fa-bank' title='Kontenübersicht'></span></a> ".
                                "<a href='".URIBASE."konto/credentials/$id/delete'><span class='fa fa-fw fa-trash' title='Zugangsdaten löschen'></span></a>".
                                "<a href='".URIBASE."konto/credentials/$id/logout'><span class='fa fa-fw fa-sign-out' title='Ausloggen'></span></a>";
                        }

                        return "<a href='".URIBASE."konto/credentials/$id/login'><span class='fa fa-fw fa-unlock-alt' title='Einloggen'></span></a>";
                    },
                ]
            );
        } else {
            $this->renderAlert('Hinweis', 'Keine Zugangsdaten angelegt', BT::TYPE_INFO);
        }
        echo HtmlForm::make()
            ->urlTarget(URIBASE.'rest/clear-session')
            ->body(
                HtmlButton::make()
                    ->style('warning')
                    ->icon('refresh')
                    ->body('Setze FINTS zurück'),
                false);
    }

    protected function actionNewCredentials()
    {
        $post = $this->request->request;
        if (ArrayHelper::allIn($post->keys(), ['name', 'blz', 'bank-username'])) {
            $blz = FintsInstitute::normaliseBlz((string) $post->get('blz'));

            // The foreign key guarantees the BLZ exists; it cannot guarantee the institute
            // actually offers PIN/TAN, which is the only thing we can talk to.
            if (FintsInstitute::query()->pinTanCapable()->whereKey($blz)->doesntExist()) {
                HTMLPageRenderer::addFlash(BT::TYPE_DANGER, "Für die BLZ $blz ist kein FinTS-Zugang bekannt.");
                HTMLPageRenderer::redirect(URIBASE.'konto/credentials/new');
            }

            DBConnector::getInstance()->dbInsert('konto_credentials', [
                // konto_credentials.name is varchar(63), so cut rather than let the insert fail.
                'name' => mb_substr(trim(strip_tags((string) $post->get('name'))), 0, 63),
                'blz' => $blz,
                'bank_username' => trim(strip_tags($post->get('bank-username'))),
                'owner_id' => DBConnector::getInstance()->getUser()['id'],
            ]
            );
            HTMLPageRenderer::redirect(URIBASE.'konto/credentials');
        }

        // Straight from the synced bank list, so there is no bank to maintain by hand.
        $banks = FintsInstitute::query()->pinTanCapable()->orderBy('name')->get(['blz', 'name', 'location']);

        $this->renderHeadline('Lege neue Zugangsdaten an');

        if ($banks->isEmpty()) {
            $this->renderAlert(
                'Keine Bankenliste vorhanden',
                'Die Liste der FinTS-fähigen Banken ist leer. Die Administration muss sie einmalig einlesen '.
                '(<code>php artisan stufis:fints-institutes-update</code>), danach kann hier eine Bank gewählt werden.',
                'danger'
            );

            return;
        }

        $this->renderAlert('Hinweis',
            'Die hier geforderten Daten werden (bis zur manuellen Löschung) gespeichert. Das Online-Banking Passwort wird immer nur zur Laufzeit verwendet und nicht permanent gespeichert', 'info');

        echo HtmlForm::make('POST', false)
            ->urlTarget(URIBASE.'konto/credentials/new')
            ->addHtmlEntity(HtmlInput::make('text')->label('Name des Zugangs')->name('name'))
            ->addHtmlEntity(HtmlDropdown::make()
                ->label('Bank')
                ->liveSearch(true)
                ->name('blz')
                ->setItems($banks->mapWithKeys(static fn (FintsInstitute $bank): array => [
                    $bank->blz => [$bank->name, "BLZ: $bank->blz".($bank->location ? ", $bank->location" : '')],
                ])->all())
            )
            ->addHtmlEntity(HtmlInput::make('text')->label('Bank Username')->name('bank-username'))
            ->addSubmitButton();
    }

    protected function actionPickTanMode(): void
    {
        if (isset($_POST['tan-mode-id'])) {
            $tanModeId = (int) $_POST['tan-mode-id'];
            try {
                $success = $this->requireFintsHandler()->setTanMode($tanModeId);
                if ($success) {
                    HTMLPageRenderer::addFlash(BT::TYPE_SUCCESS, 'TAN Modus gespeichert');
                    HTMLPageRenderer::redirect(URIBASE.'konto/credentials');
                } else {
                    HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'TAN Modus nicht gespeichert');
                }
            } catch (InvalidArgumentException $e) {
                HTMLPageRenderer::addFlash(BT::TYPE_INFO, $e->getMessage());
                HTMLPageRenderer::redirect(URIBASE."konto/credentials/$this->credentialId/tan-mode/$tanModeId/medium");
            }
        }
        $tanModes = $this->requireFintsHandler()->getUserTanModes();
        $form = HtmlForm::make('POST', false)->urlTarget(URIBASE."konto/credentials/$this->credentialId/tan-mode");
        echo $form->begin();
        $this->renderHeadline('Bitte TAN-Modus auswählen');
        $this->renderRadioButtons($tanModes, 'tan-mode-id');
        $this->renderNonce();
        echo HtmlButton::make('submit')
            ->body('Speichern')
            ->style('primary');
        echo $form->end();
    }

    protected function actionPickTanMedium(): void
    {
        $post = $this->request->request;
        $tanModeInt = (int) $this->routeInfo['tan-mode-id'];
        if ($post->has('tan-medium-name')) {
            $success = $this->requireFintsHandler()->setTanMode($tanModeInt, $post->get('tan-medium-name'));
            if ($success) {
                HTMLPageRenderer::addFlash(BT::TYPE_SUCCESS, 'TAN Medium gespeichert');
                HTMLPageRenderer::redirect(URIBASE.'konto/credentials');
            } else {
                HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'TAN Modus nicht gespeichert');
            }
        }

        $tanMedien = $this->requireFintsHandler()->getTanMedias($tanModeInt);

        echo "<form method='post' action=''>";
        $this->renderHeadline('Bitte TAN-Medium auswählen');
        $this->renderNonce();
        $this->renderRadioButtons($tanMedien, 'tan-medium-name');
        echo "<button class='btn btn-primary' type='submit'>Speichern</button>";
        echo '</form>';
    }

    /**
     * @throws NeedsTanException
     */
    protected function actionLogin(): void
    {
        $credentialId = $this->credentialId;

        $credentials = DBConnector::getInstance()->dbFetchAll(
            tables: 'konto_credentials',
            where: [
                'owner_id' => (int) DBConnector::getInstance()->getUser()['id'],
                'id' => $credentialId,
            ],
        )[0];
        $post = $this->request->request;
        if ($post->has('bank-password')) {
            // a PW was sent. Take it verbatim: do not strip every special
            // character and umlaut, banks do allow those in a PIN (see the docs on
            // Fhp\Options\Credentials::create). A mangled PIN is indistinguishable from
            // a wrong one, and three wrong ones lock the online-banking access.
            $pw = (string) $post->get('bank-password');
            FintsConnectionHandler::setLoginPassword($credentialId, $pw);
            $this->fintsHandler = FintsConnectionHandler::load($credentialId);
        }
        if (FintsConnectionHandler::hasPassword($credentialId)) {
            // pw set
            $success = $this->requireFintsHandler()->login();  // throws if Tan needed
            if ($success) {
                throw new LegacyRedirectException(redirect()->route('legacy.konto.credentials'));
            }
        }
        // if no pw or wrong one
        if (! FintsConnectionHandler::hasPassword($credentialId)) {
            $form = HtmlForm::make('POST', false)
                ->urlTarget(URIBASE.'konto/credentials/'.$credentialId.'/login')
                ->addHtmlEntity(
                    HtmlInput::make(HtmlInput::TYPE_PASSWORD)
                        ->name('bank-password')
                        ->label('Onlinebanking Passwort')
                )
                ->hiddenInput('credential-id', $credentialId)
                ->addSubmitButton();
            // PW unknown
            echo HtmlCard::make()
                ->cardHeadline('Login Zugang - '.$credentials['name'])
                ->appendBody(
                    HtmlInput::make('text')
                        ->label('Username')
                        ->value($credentials['bank_username'])
                        ->disable(),
                    false
                )
                ->appendBody($form, false);
        }
    }

    protected function actionViewSepa()
    {
        $accounts = $this->requireFintsHandler()->getSepaAccounts();
        $ibans = $this->requireFintsHandler()->getIbans();

        $dbAccounts = DBConnector::getInstance()->dbFetchAll(
            'konto_type',
            [DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY],
            ['iban', '*'],
            ['iban' => ['in', $ibans]]
        );
        $tableRows = [];
        foreach ($accounts as $account) {
            $tableRow = [
                'iban' => $account->getIban(),
                'bic' => $account->getBic(),
            ];
            if (isset($dbAccounts[$account->getIban()])) {
                $matchingDbRow = $dbAccounts[$account->getIban()];
                if (is_null($matchingDbRow['sync_until'])) {
                    $syncActive = true;
                } else {
                    $syncActive = date_create()->diff(date_create($matchingDbRow['sync_until']))->invert === 0;
                }
                $lastSyncString = ! empty($matchingDbRow['last_sync']) ? $matchingDbRow['last_sync'] : 'nie';
                $syncActiveString = $syncActive ? 'letzer sync: '.$lastSyncString : 'Sync gestoppt';
                $tableRow['info'] = $matchingDbRow['short'].$matchingDbRow['id'].' '.$syncActiveString;
                $tableRow['action'] = 'update';
            } else {
                $tableRow['info'] = 'bisher nicht importiert';
                $tableRow['action'] = 'import';
            }
            $tableRows[] = $tableRow;
        }
        $credId = $this->credentialId;
        $this->renderHeadline('Kontoauswahl');
        $this->renderTable(
            ['IBAN', 'BIC', 'Info', 'Action'],
            [$tableRows],
            ['iban', 'bic', 'info', 'action', 'iban'],
            [
                null,
                null,
                null,
                function ($actionName, $iban) use ($credId): string {
                    $shortIban = FintsConnectionHandler::shortenIban($iban);

                    return match ($actionName) {
                        'update' => "<a href='".URIBASE."konto/credentials/$credId/$shortIban'><span class='fa fa-fw fa-refresh' title='Kontostand aktualisieren'></span></a>",
                        'import' => "<a href='".URIBASE."konto/credentials/$credId/$shortIban/import'><span class='fa fa-fw fa-upload' title='Konto neu importieren'></span></a>",
                        default => 'error',
                    };
                },
            ]
        );

        echo HtmlButton::make()
            ->style('primary')
            ->body('zurück')
            ->icon('chevron-left')
            ->asLink(URIBASE.'konto/credentials');
    }

    /**
     * Registering an account is the Livewire page's job (pages::new-banking-account), which
     * validates with Laravel rules, knows about sync_until and manually_enterable, and is
     * the same form used everywhere else. This hands the account over to it with the IBAN
     * prefilled instead of keeping a second, hand-written create form here.
     */
    protected function actionNewSepaKonto(): void
    {
        $shortIban = $this->routeInfo['short-iban'];
        // Resolved from the account list of this bank access, not from user input, so the
        // prefilled value is one of the accounts the credential actually holds.
        $iban = $this->requireFintsHandler()->lengthenIban($shortIban);

        if ($iban === null) {
            HTMLPageRenderer::addFlash(
                BT::TYPE_DANGER,
                'Zu diesem Kürzel gehört kein Konto dieses Bankzugangs.'
            );
            HTMLPageRenderer::redirect(URIBASE."konto/credentials/$this->credentialId/sepa");
        }

        throw new LegacyRedirectException(redirect()->route('bank-account.new', [
            'iban' => $iban,
            // Marks this as a synced account: the page locks the IBAN and the manual-entry
            // switch for it.
            'bankSynced' => 1,
            // Built as a relative path from a named route, so what the page gets handed can
            // only ever point back into this application.
            'returnTo' => route('legacy.konto.credentials.sepa', ['credential_id' => $this->credentialId], false),
        ]));
    }

    protected function actionImportNewSepaStatements()
    {
        $shortIban = $this->routeInfo['short-iban'];
        $iban = $this->requireFintsHandler()->lengthenIban($shortIban);

        if ($iban === null) {
            HTMLPageRenderer::addFlash(
                BT::TYPE_DANGER,
                'Zu diesem Kürzel gehört kein Konto dieses Bankzugangs.'
            );
            HTMLPageRenderer::redirect(URIBASE."konto/credentials/$this->credentialId/sepa");
        }

        $dbKontos = DBConnector::getInstance()->dbFetchAll(
            'konto_type',
            [DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY],
            ['iban', '*']
        );

        // Reaching this URL for an account that was never registered used to be an
        // undefined-array-key error page.
        if (! isset($dbKontos[$iban])) {
            HTMLPageRenderer::addFlash(
                BT::TYPE_WARNING,
                'Dieses Konto ist noch nicht für den Import eingerichtet. Bitte lege es zuerst an.'
            );
            HTMLPageRenderer::redirect(URIBASE."konto/credentials/$this->credentialId/$shortIban/import");
        }
        $dbKonto = $dbKontos[$iban];

        [$startDate, $syncUntil] = DateHelper::fromUntilLast($dbKonto['sync_from'], $dbKonto['sync_until'], $dbKonto['last_sync']);

        try {
            $statements = $this->requireFintsHandler()->getStatements($iban, $startDate, $syncUntil);
        } catch (InvalidArgumentException $e) {
            // getSepaAccount() throws when the bank access does not hold this IBAN, which is
            // reachable because an account may also be registered by hand (or for a cash box)
            // on the Livewire page. That is a wrong-page situation, not a server error.
            $this->requireFintsHandler()->logger->warning('Statement request for an IBAN this credential does not hold', ['exception' => $e]);
            HTMLPageRenderer::addFlash(
                BT::TYPE_DANGER,
                'Dieses Konto gehört nicht zu diesem Bankzugang',
                'Bitte rufe die Umsätze über den Bankzugang ab, dem das Konto gehört.'
            );
            HTMLPageRenderer::redirect(URIBASE."konto/credentials/$this->credentialId/sepa");
        }

        [$success, $msg] = $this->saveStatements($statements, $dbKonto['id']);

        HTMLPageRenderer::addFlash($success ? BT::TYPE_SUCCESS : BT::TYPE_WARNING, $msg);
        HTMLPageRenderer::redirect(URIBASE."konto/credentials/$this->credentialId/sepa");
    }

    protected function saveStatements(StatementOfAccount $statements, int $kontoId): array
    {
        $db = DBConnector::getInstance();
        $logger = $this->requireFintsHandler()->getLogger();
        $lastKontoRow = $db->dbFetchAll(
            tables: 'konto',
            where: ['konto_id' => $kontoId],
            sort: ['id' => false],
            limit: 1
        );
        $lastKontoId = 0;
        $oldSaldoCent = null;
        $tryRewind = false;
        $rewindDiff = 0;
        $skipped = false;
        // Was the resume point in the already-stored data established? Without stored rows
        // there is nothing to resume from, so everything the bank sent is new.
        $anchorFound = true;
        $lastStoredSaldoCent = null;
        $stoppedAtSyncUntil = false;

        $kontoRow = $db->dbFetchAll(tables: 'konto_type', where: ['id' => $kontoId])[0];
        $syncUntil = DateHelper::fromDb($kontoRow['sync_until']);

        if (! empty($lastKontoRow)) {
            $lastKontoRow = $lastKontoRow[0];
            $lastKontoId = $lastKontoRow['id'];
            $lastKontoSaldo = $lastKontoRow['saldo'];
            $oldSaldoCent = $this->convertToCent($lastKontoSaldo);
            // Kept separately: $oldSaldoCent is reused below as the running statement-to-
            // statement saldo, so it no longer holds the stored value once the loop starts.
            $lastStoredSaldoCent = $oldSaldoCent;
            $tryRewind = true;
            $anchorFound = false;
            $logger->debug('Found last entry', $lastKontoRow);
        }

        $db->dbBegin();
        $transactionData = [];

        $dateString = date_create()->format(DBConnector::SQL_DATE_FORMAT);
        foreach ($statements->getStatements() as $statement) {
            $dateString = $statement->getDate()->format(DBConnector::SQL_DATE_FORMAT);
            $saldoCent = $this->convertToCent($statement->getStartBalance(), $statement->getCreditDebit());
            $logger->debug('Statement', ['date' => $dateString, 'saldo' => $saldoCent]);
            // Continuity between two consecutive statements: the closing saldo of the
            // previous one has to be the opening balance of this one.
            if ($tryRewind === false && $oldSaldoCent !== null && $oldSaldoCent !== $saldoCent) {
                $db->dbRollBack();
                $logger->error("Wrong saldo $oldSaldoCent !== $saldoCent at statement from $dateString");
                $msg = 'Die Kontoauszüge der Bank sind nicht lückenlos: Der Auszug vom '.$dateString.
                    ' beginnt mit '.$this->convertCentForDB($saldoCent).' €, der vorherige endete mit '.
                    $this->convertCentForDB($oldSaldoCent).' €. Es wurde nichts importiert.';

                return [false, $msg];
            }
            // echo "Statement $dateString Saldo: $saldoCent";
            foreach ($statement->getTransactions() as $transaction) {
                $valCent = $this->convertToCent($transaction->getAmount(), $transaction->getCreditDebit());
                $saldoCent += $valCent;
                $logger->debug('Transaktion', [
                    'value' => $valCent,
                    'saldo-calc' => $saldoCent,
                    'date' => $transaction->getBookingDate()?->format('Y-m-d'),
                ]);
                if ($tryRewind === true) {
                    // Do rewind if necessary. customer_ref is deliberately NOT part of the
                    // criteria: it holds the SEPA end-to-end id, which MT940 usually leaves
                    // empty or reports as NOTPROVIDED, so matching on it made the anchor
                    // unfindable - and an unfound anchor used to mean a silent re-import of
                    // the whole range. The running saldo is a far stronger key anyway.
                    $rewindRow = $db->dbFetchAll(
                        tables: 'konto',
                        showColumns: ['id'],
                        where: [
                            'konto_id' => $kontoId,
                            'value' => $this->convertCentForDB($valCent),
                            'saldo' => $this->convertCentForDB($saldoCent),
                            'date' => $transaction->getBookingDate()?->format('Y-m-d'),
                            'valuta' => $transaction->getValutaDate()?->format('Y-m-d'),
                        ],
                        sort: ['id' => false],
                        limit: 1
                    );
                    if (count($rewindRow) === 1) {
                        $rewindId = $rewindRow[0]['id'];
                        $rewindDiff = $lastKontoId - $rewindId + 1;
                        $tryRewind = false;
                    }
                }

                if ($rewindDiff > 0) {
                    $rewindDiff--;
                    $skipped = $skipped === false ? 1 : $skipped + 1;
                    $logger->debug('SKIP TRANSACTION - found in DB');

                    if ($rewindDiff === 0) {
                        // Last already-stored transaction consumed: this is the one place
                        // where the freshly computed saldo can be held against the stored
                        // one. Previously this comparison never ran - it was guarded by
                        // $tryRewind === false, and by the time that was true the stored
                        // value had already been overwritten by the running saldo.
                        if ($saldoCent !== $lastStoredSaldoCent) {
                            $db->dbRollBack();
                            $msg = 'Der Kontostand der Bank passt nicht zum gespeicherten Stand ('.
                                $this->convertCentForDB($saldoCent).' € statt '.
                                $this->convertCentForDB($lastStoredSaldoCent).' € nach dem letzten bekannten Umsatz vom '.
                                $dateString.'). Es wurde nichts importiert.';
                            $logger->error($msg, ['konto_id' => $kontoId]);

                            return [false, $msg];
                        }
                        $anchorFound = true;
                    }

                    continue; // skip this entry, it was in the db before
                }

                // are we exceeding sync_until?
                if ($syncUntil && $transaction->getValutaDate()?->diff($syncUntil)->invert === 1) {
                    $stoppedAtSyncUntil = true;

                    break 2;
                }

                $transactionData[] = [
                    'id' => ++$lastKontoId,
                    'konto_id' => $kontoId,
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
                    // 'gvcode' => $transaction->getBookingCode(), // deprecated since csv import
                    'customer_ref' => $transaction->getEndToEndID(),
                ];
                AuslagenHandler2::hookZahlung($transaction->getMainDescription());
            }
            $oldSaldoCent = $saldoCent;
        }

        // The bank sends a range that overlaps what is already stored, so the already-known
        // transactions have to be identified and skipped. If that resume point was never
        // reached, every transaction of the range looks new - which is how a re-import used
        // to duplicate months of bookings while reporting success. Refuse instead.
        if ($anchorFound === false && $stoppedAtSyncUntil === false) {
            $db->dbRollBack();
            $msg = 'Der letzte bereits importierte Umsatz wurde in den Daten der Bank nicht wiedergefunden. '.
                'Es wurde nichts importiert, um doppelte Buchungen zu vermeiden. '.
                'Bitte prüfe, ob Umsätze nachträglich verändert wurden, und wende dich an die Administration.';
            $logger->error($msg, ['konto_id' => $kontoId, 'last_stored_saldo_cent' => $lastStoredSaldoCent]);

            return [false, $msg];
        }

        if (count($transactionData) > 0) {
            $db->dbInsertMultiple('konto', array_keys($transactionData[0]), ...$transactionData);
            $db->dbUpdate('konto_type', ['id' => $kontoId], ['last_sync' => $dateString]);
        }
        $ret = $db->dbCommitRollbackOnFailure();

        if ($ret === true) {
            $msg = count($transactionData).' Einträge importiert.';
        } else {
            $msg = 'Ein Fehler ist aufgetreten - DBRollback - Import von '.
                count($transactionData).' Einträgen ausstehend.';
        }
        if (DEV && $skipped !== false) {
            $msg .= " $skipped Einträge waren bereits bekannt";
        }
        $logger->debug($msg, ['success' => $ret]);

        return [$ret, $msg];
    }

    protected function actionLogout(): void
    {
        // Logging out of a connection that is already gone is not an error worth a
        // redirect to the login, so this keeps its own handling instead of using
        // requireFintsHandler().
        if ($this->fintsHandler instanceof FintsConnectionHandler) {
            $this->fintsHandler->logout();
        } else {
            HTMLPageRenderer::addFlash(BT::TYPE_WARNING, 'FINTS war nicht verbunden.');
        }
        HTMLPageRenderer::redirect(URIBASE.'konto/credentials');
    }

    /**
     * @param  string|null  $creditDebit  either @see Statement::CD_DEBIT or @see Statement::CD_CREDIT, if null its
     *                                    assumed by sign of $amount
     */
    private function convertToCent(string|float $amount, ?string $creditDebit = null): int
    {
        $cents = (int) round(((float) $amount) * 100);

        if (is_null($creditDebit)) {
            // $cents already carries the sign of $amount. Multiplying by sign($amount)
            // on top of that flipped every negative value to positive.
            return $cents;
        }

        // Bank statements carry an unsigned magnitude plus a separate credit/debit mark
        // (MT940 fields 60F/61), so abs() is a no-op on real bank data. It only guards
        // against a caller handing in an already-signed amount together with a mark.
        return ($creditDebit === Statement::CD_DEBIT ? -1 : 1) * abs($cents);
    }

    private function convertCentForDB(int $amount): string
    {
        // rounds implicit
        return number_format($amount / 100.0, 2, '.', '');
    }
}
