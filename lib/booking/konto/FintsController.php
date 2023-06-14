<?php

namespace booking\konto;

use booking\konto\tan\FlickerGenerator;
use Fhp\Model\StatementOfAccount\Statement;
use Fhp\Model\StatementOfAccount\StatementOfAccount;
use Fhp\Model\TanRequest;
use Fhp\Model\TanRequestChallengeImage;
use forms\projekte\auslagen\AuslagenHandler2;
use framework\ArrayHelper;
use framework\DateHelper;
use framework\DBConnector;
use framework\NewValidator;
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
    private ?FintsConnectionHandler $fintsHandler;

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
        $post = $this->request->request;
        if ($post->has('tan')) {
            $this->fintsHandler->submitTan($post->getAlnum('tan'));
        }
        try {
            parent::render();
        } catch (NeedsTanException $e) {
            $this->fintsHandler->logger->info('Tan needed', ['exception' => $e]);
            $this->renderTanInput($e->getMessage(), $e->getTanRequest());
        }
    }

    private function renderTanInput(string $msg, TanRequest $tanRequest): void
    {
        $mediumName = $tanRequest->getTanMediumName() ?? '';
        $challengeText = $tanRequest->getChallenge();

        echo Html::headline(1)->body($msg);

        echo Html::headline(3)->body($mediumName);
        echo Html::p()->body($challengeText, false);
        $challengeBinary = $tanRequest->getChallengeHhdUc();
        if (!is_null($challengeBinary)) {
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
                    echo 'Tan Format unbekannt' . PHP_EOL;
                    if (DEV) {
                        echo 'Challenge Binary: ' . $challengeBinary . PHP_EOL;
                    }
                }
            }
        }
        echo HtmlForm::make('POST', false)
            ->urlTarget('')
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
                'bank_name' => 'konto_bank.name',
                'tan_mode',
                'tan_mode_name',
                'tan_medium_name',
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
                        $tanString = '[' . $obj->defaultEscapeFunction($tanMode) . '] ' . $obj->defaultEscapeFunction($tanModeName);
                        if (isset($tanMediumName)) {
                            $tanString .= ': ' . $obj->defaultEscapeFunction($tanMediumName);
                        }
                        if (FintsConnectionHandler::hasActiveSession($id)) {
                            $tanString .= ' ' . FA::make('fa-pencil')->href(URIBASE . "konto/credentials/$id/tan-mode")->title('TAN Modus auswählen');
                        }
                        return $tanString;
                    },
                    static function ($id) { // action
                        if (FintsConnectionHandler::hasActiveSession($id)) {
                            return
                                "<a href='" . URIBASE . "konto/credentials/$id/sepa'><span class='fa fa-fw fa-bank' title='Kontenübersicht'></span></a> " .
                                "<a href='" . URIBASE . "konto/credentials/$id/delete'><span class='fa fa-fw fa-trash' title='Zugangsdaten löschen'></span></a>" .
                                "<a href='" . URIBASE . "konto/credentials/$id/logout'><span class='fa fa-fw fa-sign-out' title='Ausloggen'></span></a>";
                        }
                        return "<a href='" . URIBASE . "konto/credentials/$id/login'><span class='fa fa-fw fa-unlock-alt' title='Einloggen'></span></a>";
                    },
                ]
            );
        } else {
            $this->renderAlert('Hinweis', 'Keine Zugangsdaten angelegt', BT::TYPE_INFO);
        }
        echo HtmlForm::make()
            ->urlTarget(URIBASE . 'rest/clear-session')
            ->body(
                HtmlButton::make()
                    ->style('warning')
                    ->icon('refresh')
                    ->body('Setze FINTS zurück'), false);
    }

    protected function actionNewCredentials()
    {
        $post = $this->request->request;
        if (ArrayHelper::allIn($post->keys(), ['name', 'bank-id', 'bank-username'])) {
            DBConnector::getInstance()->dbInsert('konto_credentials', [
                    'name' => $post->getAlpha('name'),
                    'bank_id' => $post->getInt('bank-id'),
                    'bank_username' => trim(strip_tags($post->get('bank-username'))),
                    'owner_id' => DBConnector::getInstance()->getUser()['id'],
                ]
            );
            HTMLPageRenderer::redirect(URIBASE . 'konto/credentials');
        }
        $banks = DBConnector::getInstance()->dbFetchAll('konto_bank');
        $this->renderHeadline('Lege neue Zugangsdaten an');
        $this->renderAlert('Hinweis',
            'Die hier geforderten Daten werden (bis zur manuellen Löschung) gespeichert. Das Online-Banking Passwort wird immer nur zur Laufzeit verwendet und nicht permanent gespeichert', 'info');
        $liveSearch = count($banks) > 5;

        echo HtmlForm::make('POST', false)
            ->urlTarget('')
            ->addHtmlEntity(HtmlInput::make('text')->label('Name des Zugangs')->name('name'))
            ->addHtmlEntity(HtmlDropdown::make()
                ->label('Bank')
                ->liveSearch($liveSearch)
                ->name('bank-id')
                ->setItems(array_combine(array_column($banks, 'id'), array_map(static function ($el) {
                    return [$el['name'], "BLZ: {$el['blz']}"];
                }, $banks)))
            )
            ->addHtmlEntity(HtmlInput::make('text')->label('Bank Username')->name('bank-username'))
            ->addSubmitButton();
    }

    protected function actionPickTanMode(): void
    {
        if (isset($_POST['tan-mode-id'])) {
            $tanModeId = (int) $_POST['tan-mode-id'];
            try {
                $success = $this->fintsHandler->setTanMode($tanModeId);
                if ($success) {
                    HTMLPageRenderer::addFlash(BT::TYPE_SUCCESS, 'TAN Modus gespeichert');
                    HTMLPageRenderer::redirect(URIBASE . 'konto/credentials');
                } else {
                    HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'TAN Modus nicht gespeichert');
                }
            } catch (InvalidArgumentException $e) {
                HTMLPageRenderer::addFlash(BT::TYPE_INFO, $e->getMessage());
                HTMLPageRenderer::redirect(URIBASE . "konto/credentials/$this->credentialId/tan-mode/$tanModeId/medium");
            }
        }
        $tanModes = $this->fintsHandler->getUserTanModes();
        $form = HtmlForm::make('POST', false)->urlTarget('');
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
            $success = $this->fintsHandler->setTanMode($tanModeInt, $post->get('tan-medium-name'));
            if ($success) {
                HTMLPageRenderer::addFlash(BT::TYPE_SUCCESS, 'TAN Medium gespeichert');
                HTMLPageRenderer::redirect(URIBASE . 'konto/credentials');
            } else {
                HTMLPageRenderer::addFlash(BT::TYPE_DANGER, 'TAN Modus nicht gespeichert');
            }
        }

        $tanMedien = $this->fintsHandler->getTanMedias($tanModeInt);

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
            // a PW was sent
            $pw = $post->getAlnum('bank-password');
            FintsConnectionHandler::setLoginPassword($credentialId, $pw);
            $this->fintsHandler = FintsConnectionHandler::load($credentialId);
        }
        if (FintsConnectionHandler::hasPassword($credentialId)) {
            // pw set
            $success = $this->fintsHandler->login();  // throws if Tan needed
            if ($success) {
                HTMLPageRenderer::redirect(URIBASE . 'konto/credentials');
            }
        }
        // if no pw or wrong one
        if (!FintsConnectionHandler::hasPassword($credentialId)) {
            $form = HtmlForm::make('POST', false)
                ->urlTarget('')
                ->addHtmlEntity(
                    HtmlInput::make(HtmlInput::TYPE_PASSWORD)
                        ->name('bank-password')
                        ->label('Onlinebanking Passwort')
                )
                ->hiddenInput('credential-id', $credentialId)
                ->addSubmitButton();
            // PW unknown
            echo HtmlCard::make()
                ->cardHeadline('Login Zugang - ' . $credentials['name'])
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
        $accounts = $this->fintsHandler->getSepaAccounts();
        $ibans = $this->fintsHandler->getIbans();

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
                $lastSyncString = !empty($matchingDbRow['last_sync']) ? $matchingDbRow['last_sync'] : 'nie';
                $syncActiveString = $syncActive ? 'letzer sync: ' . $lastSyncString : 'Sync gestoppt';
                $tableRow['info'] = $matchingDbRow['short'] . $matchingDbRow['id'] . ' ' . $syncActiveString;
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
                        'update' => "<a href='" . URIBASE . "konto/credentials/$credId/$shortIban'><span class='fa fa-fw fa-refresh' title='Kontostand aktualisieren'></span></a>",
                        'import' => "<a href='" . URIBASE . "konto/credentials/$credId/$shortIban/import'><span class='fa fa-fw fa-upload' title='Konto neu importieren'></span></a>",
                        default => 'error',
                    };
                },
            ]
        );

        echo HtmlButton::make()
            ->style('primary')
            ->body('zurück')
            ->icon('chevron-left')
            ->asLink(URIBASE . 'konto/credentials');
    }

    protected function actionNewSepaKonto(): void
    {
        if ($this->request->request->count() > 0) {
            $post = $this->request->request;
            $syncFrom = date_create($post->get('sync_from'))->format('Y-m-d');
            $kontoIban = $post->getAlnum('iban');
            [, $iban] = (new NewValidator())->validate($kontoIban, 'iban');
            $kontoName = substr(htmlspecialchars(strip_tags(trim($post->getAlpha('konto-name')))), 0, 32);
            $kontoShort = strtoupper(substr($post->getAlpha('konto-short'), 0, 2));
            $ret = DBConnector::getInstance()->dbInsert('konto_type', [
                'name' => $kontoName,
                'short' => $kontoShort,
                'sync_from' => $syncFrom,
                'iban' => $iban,
            ]);
            // TODO: use $ret
            HTMLPageRenderer::addFlash(BT::TYPE_SUCCESS, 'Erfolgreich gespeichert');
            HTMLPageRenderer::redirect(URIBASE . "konto/credentials/$this->credentialId/sepa");
        }

        $shortIban = $this->routeInfo['short-iban'];
        $iban = $this->fintsHandler->lengthenIban($shortIban);

        $this->renderHeadline('Neues Konto Importieren');
        echo HtmlForm::make('POST', false)
            ->urlTarget('')
            ->addHtmlEntity(HtmlInput::make()->name('iban')->label('IBAN')->value($iban)->readOnly())
            ->addHtmlEntity(HtmlInput::make()->name('konto-name')->label('Bezeichnung Konto'))
            ->addHtmlEntity(HtmlInput::make()->name('konto-short')->label('Eindeutiges Buchstabenkürzel für das Konto (intern)'))
            ->addHtmlEntity(HtmlInput::make('date')->name('sync-from')->label('Startdatum der Synchronisation'))
            ->addSubmitButton('Speichern')
        ;
    }

    protected function actionImportNewSepaStatements()
    {
        $shortIban = $this->routeInfo['short-iban'];
        $iban = $this->fintsHandler->lengthenIban($shortIban);

        $dbKonto = DBConnector::getInstance()->dbFetchAll(
            'konto_type',
            [DBConnector::FETCH_UNIQUE_FIRST_COL_AS_KEY],
            ['iban', '*']
        )[$iban];

        [$startDate, $syncUntil] = DateHelper::fromUntilLast($dbKonto['sync_from'], $dbKonto['sync_until'], $dbKonto['last_sync']);

        $statements = $this->fintsHandler->getStatements($iban, $startDate, $syncUntil);

        [$success, $msg] = $this->saveStatements($statements, $dbKonto['id']);

        HTMLPageRenderer::addFlash($success ? BT::TYPE_SUCCESS : BT::TYPE_WARNING, $msg);
        HTMLPageRenderer::redirect(URIBASE . "konto/credentials/$this->credentialId/sepa");
    }

    protected function saveStatements(StatementOfAccount $statements, int $kontoId): array
    {
        $db = DBConnector::getInstance();
        $logger = $this->fintsHandler->getLogger();
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

        $kontoRow = $db->dbFetchAll(tables: 'konto_type', where: ['id' => $kontoId])[0];
        $syncUntil = DateHelper::fromDb($kontoRow['sync_until']);

        if (!empty($lastKontoRow)) {
            $lastKontoRow = $lastKontoRow[0];
            $lastKontoId = $lastKontoRow['id'];
            $lastKontoSaldo = $lastKontoRow['saldo'];
            $oldSaldoCent = $this->convertToCent($lastKontoSaldo);
            $tryRewind = true;
            $logger->debug('Found last entry', $lastKontoRow);
        }

        $db->dbBegin();
        $transactionData = [];

        $dateString = date_create()->format(DBConnector::SQL_DATE_FORMAT);
        foreach ($statements->getStatements() as $statement) {
            $dateString = $statement->getDate()->format(DBConnector::SQL_DATE_FORMAT);
            $saldoCent = $this->convertToCent($statement->getStartBalance(), $statement->getCreditDebit());
            $logger->debug('Statement', ['date' => $dateString, 'saldo' => $saldoCent]);
            if ($tryRewind === false && $oldSaldoCent !== null && $oldSaldoCent !== $saldoCent) {
                $db->dbRollBack();
                $logger->debug("Wrong saldo $oldSaldoCent !== $saldoCent at statement from $dateString", [var_export($statements, true)]);
                return [false, "$oldSaldoCent !== $saldoCent at statement from $dateString"];
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
                    // do rewind if necessary
                    $rewindRow = $db->dbFetchAll(
                        tables: 'konto',
                        showColumns: ['id'],
                        where: [
                            'konto_id' => $kontoId,
                            'value' => $this->convertCentForDB($valCent),
                            'saldo' => $this->convertCentForDB($saldoCent),
                            'date' => $transaction->getBookingDate()?->format('Y-m-d'),
                            'valuta' => $transaction->getValutaDate()?->format('Y-m-d'),
                            'customer_ref' => $transaction->getEndToEndID(),
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
                    --$rewindDiff;
                    $skipped = $skipped === false ? 1 : $skipped + 1;
                    $logger->debug('SKIP TRANSACTION - found in DB');
                    continue; // skip this entry, it was in the db before
                }

                // are we exceeding sync_until?
                if ($syncUntil && $transaction->getValutaDate()?->diff($syncUntil)->invert === 1) {
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
                    'gvcode' => $transaction->getBookingCode(),
                    'customer_ref' => $transaction->getEndToEndID(),
                ];
                AuslagenHandler2::hookZahlung($transaction->getMainDescription());
            }
            $oldSaldoCent = $saldoCent;
        }

        if (count($transactionData) > 0) {
            $db->dbInsertMultiple('konto', array_keys($transactionData[0]), ...$transactionData);
            $db->dbUpdate('konto_type', ['id' => $kontoId], ['last_sync' => $dateString]);
        }
        $ret = $db->dbCommitRollbackOnFailure();

        if ($ret === true) {
            $msg = count($transactionData) . ' Einträge importiert.';
        } else {
            $msg = 'Ein Fehler ist aufgetreten - DBRollback - Import von ' .
                count($transactionData) . ' Einträgen ausstehend.';
        }
        if (DEV && $skipped !== false) {
            $msg .= " $skipped Einträge waren bereits bekannt";
        }
        $logger->debug($msg, ['success' => $ret]);
        return [$ret, $msg];
    }

    protected function actionLogout(): void
    {
        if (isset($this->fintsHandler)) {
            $this->fintsHandler->logout();
        } else {
            HTMLPageRenderer::addFlash(BT::TYPE_WARNING, 'FINTS war nicht verbunden.');
        }
        HTMLPageRenderer::redirect(URIBASE . 'konto/credentials');
    }

    /**
     * @param string|null $creditDebit either @see Statement::CD_DEBIT or @see Statement::CD_CREDIT, if null its
     *                    assumed by sign of $amount
     */
    private function convertToCent(string|float $amount, string $creditDebit = null): float|int
    {
        $float = (float) $amount;
        $cents = (int) round($float * 100);

        if (is_null($creditDebit)) {
            $sign = ($float > 0) - ($float < 0);
            return $sign * $cents;
        }
        return ($creditDebit === Statement::CD_DEBIT ? -1 : 1) * $cents;
    }

    private function convertCentForDB(int $amount): string
    {
        // rounds implicit
        return number_format($amount / 100.0, 2, '.', '');
    }
}
