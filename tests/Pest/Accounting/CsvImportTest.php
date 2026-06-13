<?php

use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\Legacy\Expense;
use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\Legacy\Project;
use Illuminate\Http\Testing\File;

$acc = null;

/**
 * Apply the column mapping for the standard semicolon fixture (test-correct-semicolon.csv).
 * Column indices: 4=Buchungstag 5=Valutadatum 6=Name 7=IBAN 9=Buchungstext 10=Zweck 11=Betrag 13=Saldo.
 */
function mapSemicolonFixture($wire, bool $withSaldo = true)
{
    $wire->set('mapping.date', 4)
        ->set('mapping.valuta', 5)
        ->set('mapping.empf_name', 6)
        ->set('mapping.empf_iban', 7)
        ->set('mapping.type', 9)
        ->set('mapping.zweck', 10)
        ->set('mapping.value', 11);

    if ($withSaldo) {
        $wire->set('mapping.saldo', 13);
    }

    return $wire;
}

test('csv import is accessible as cash officer', function (): void {
    Livewire::actingAs(cashOfficer())->test('pages::bank.csv-import')
        ->assertSuccessful();
});

test('csv import is accessible as finance member', function (): void {
    // Access is granted to the whole ref-finanzen (finance) group, which
    // includes the budget officer (see da09b8dc).
    Livewire::actingAs(budgetManager())->test('pages::bank.csv-import')
        ->assertSuccessful();
});

test('csv import is not accessible as normal user', function (): void {
    Livewire::actingAs(user())->test('pages::bank.csv-import')
        ->assertForbidden();
});

test('show last transactions', function (): void {
    $lastTransactions = [];
    BankAccount::all()->each(function ($account) use (&$lastTransactions): void {
        $tmp = $account->bankTransactions()->orderBy('id', 'desc')->first();
        if ($tmp) {
            $lastTransactions[$account->id] = $tmp;
        }
    });
    $wire = Livewire::actingAs(cashOfficer())->test('pages::bank.csv-import');
    foreach ($lastTransactions as $transaction) {
        // Log::debug($transaction->date);
        $wire->set('account_id', $transaction->konto_id)
            ->assertSee(number_format($transaction->saldo, 2, ',', '.'))
            ->assertSee($transaction->date->format('d.m.Y'))
            ->assertSee($transaction->zweck);
    }
});

test('account has no transactions view', function (): void {
    $noTransactions = [];
    BankAccount::all()->each(function ($account) use (&$noTransactions): void {
        $count = $account->bankTransactions()->count();
        if ($count === 0) {
            $noTransactions[$account->id] = $account->id;
        }
    });
    $wire = Livewire::actingAs(cashOfficer())->test('pages::bank.csv-import');
    foreach ($noTransactions as $id) {
        $wire->set('account_id', $id)
            ->assertSee(__('konto.csv-no-transaction'));
    }
});

test('csv upload visibility', function (): void {
    $wire = Livewire::actingAs(cashOfficer())->test('pages::bank.csv-import');
    $accountIds = BankAccount::all()->pluck('id')->toArray();
    foreach ($accountIds as $accountId) {
        $wire->set('account_id', $accountId)
            ->assertSee(__('konto.csv-upload-headline'));
    }
});

test('php.ini has utf8 as default_charset', function (): void {
    expect(strtolower(ini_get('default_charset')))->toEqual('utf-8');
});

test('parse csv utf8 encoding', function ($header, $data): void {
    $acc = BankAccount::factory()->create();

    $csvFile = testFile('csv-import/test-correct-semicolon.csv');
    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id) // an account without transactions
        ->set('csv', $csvFile)
        // check if file is correctly parsed
        ->assertSet('header', $header)
        ->assertSet('data', collect($data));
})->with('csv imports');

test('parse csv win encoding', function ($header, $data): void {
    $acc = BankAccount::orderBy('id', 'desc')->first();
    $csvFile = testFile('csv-import/test-correct-semicolon-win-enc.csv');

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id) // an account without transactions
        ->set('csv', $csvFile)
        ->assertSet('header', $header)
        ->assertSet('data', collect($data));
})->with('csv imports');

test('views showing properly', function (): void {
    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    $lw = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->assertSee(__('konto.manual-headline'))
        ->assertSee(__('konto.manual-headline-sub'))
        ->assertSee(__('konto.csv-label-choose-konto'))
        ->assertSee(__('konto.csv-upload-headline'))
        ->assertSee(__('konto.csv-upload-headline-sub'))
        ->assertDontSee(__('konto.transaction.headline'))
        ->set('csv', $csvFile)
        ->assertSee(__('konto.manual-headline'))
        ->assertSee(__('konto.manual-headline-sub'))
        ->assertSee(__('konto.manual-button-reverse-csv-order'))
        ->assertSee(__('konto.manual-button-reverse-csv-order-sub'));
    foreach ([
        'konto.label.transaction.date', 'konto.hint.transaction.date', 'konto.label.transaction.valuta', 'konto.hint.transaction.valuta',
        'konto.label.transaction.type', 'konto.hint.transaction.type', 'konto.label.transaction.empf_iban', 'konto.hint.transaction.empf_iban',
        'konto.label.transaction.empf_bic', 'konto.hint.transaction.empf_bic', 'konto.label.transaction.empf_name', 'konto.hint.transaction.empf_name',
        'konto.label.transaction.primanota', 'konto.hint.transaction.primanota', 'konto.label.transaction.value', 'konto.hint.transaction.value',
        'konto.label.transaction.saldo', 'konto.hint.transaction.saldo', 'konto.label.transaction.zweck', 'konto.hint.transaction.zweck',
        'konto.label.transaction.comment', 'konto.hint.transaction.comment', 'konto.label.transaction.customer_ref', 'konto.hint.transaction.customer_ref',
    ] as $translationKey) {
        $lw->assertSee(__($translationKey));
    }
});

test('csv upload some fields are required', function (): void {
    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', 2) // an account with some transactions
        ->set('csv', $csvFile)
        ->call('save')
        ->assertHasErrors([
            'mapping.date',
            'mapping.valuta',
            'mapping.type',
            'mapping.value',
            'mapping.empf_name',
            'mapping.empf_iban',
            'mapping.zweck',
        ]);
});

test('csv upload with wrong date check (order and start)', function (): void {
    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    // input the column numbers to pick
    $lw = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', 2) // an account with some transactions
        ->set('csv', $csvFile)
        ->set('mapping.date', 4)
        ->set('mapping.valuta', 5);
    // dump("setter done");
    $lw->assertHasErrors(['mapping.date', 'mapping.valuta']);
    // dump('first test done');
    $lw->call('reverseCsvOrder')
        ->assertHasNoErrors(['mapping.date', 'mapping.valuta']);
    $lw->call('reverseCsvOrder')
        ->assertHasErrors(['mapping.date', 'mapping.valuta']);
});

test('csv upload with wrong saldo check (order and start)', function (): void {
    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', 2) // an account with some transactions
        ->set('csv', $csvFile)
        ->set('mapping.date', 4)
        ->set('mapping.valuta', 5)
        // ->set('mapping.empf_name', 6)
        // ->set('mapping.empf_iban', 7)
        // ->set('mapping.type', 9)
        // ->set('mapping.zweck', 10)
        // ->set('mapping.value', 12)
        // ->set('mapping.saldo', 13)
        // ->call('reverseCsvOrder')
        ->assertHasErrors(['mapping.date', 'mapping.valuta']);
});

test('wrong file extension is not accepted', function (): void {
    $image = testFile('test-image.png');
    $pdf = testFile('empty.pdf');

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', 2) // an account with some transactions
        ->set('csv', $image)
        ->assertHasErrors(['csv'])
        ->set('csv', $pdf)
        ->assertHasErrors(['csv']);
});

test('wrong mime type is not accepted', function (): void {
    $image_csv = testFile('test-image.png', 'test-image.csv');
    $pdf_csv = testFile('empty.pdf', 'empty-pdf.csv');

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', 2) // an account with some transactions
        ->set('csv', $image_csv)
        ->assertHasErrors(['csv'])
        ->set('csv', $pdf_csv)
        ->assertHasErrors(['csv']);
})->todo('works in web, but not in test');

test('if csv import is saved', function (): void {

    $acc = BankAccount::orderBy('id', 'desc')->first();
    $transactionAmount = BankTransaction::where('konto_id', '=', $acc->id)->count();
    expect($transactionAmount)->toBe(0);

    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id) // an account without transactions
        ->set('csv', $csvFile)
        ->set('mapping.date', 4)
        ->set('mapping.valuta', 5)
        ->set('mapping.empf_name', 6)
        ->set('mapping.empf_iban', 7)
        ->set('mapping.type', 9)
        ->set('mapping.zweck', 10)
        ->set('mapping.value', 11)
        ->set('mapping.saldo', 13)
        ->call('reverseCsvOrder')
        ->call('save')
        ->assertHasNoErrors();

    $transaction = BankTransaction::where('konto_id', '=', $acc->id)->first();
    expect([
        $transaction?->value,
        $transaction?->saldo,
        $transaction?->empf_name,
        $transaction?->empf_iban,
        $transaction?->zweck,
    ])->toBe([
        '-13.14', '18089.63', 'Person 1', 'DE73447318315829961821', 'Entry 1',
    ]);
    $transactionAmount = BankTransaction::where('konto_id', '=', $acc->id)->count();
    expect($transactionAmount)->toBe(5);
});

test('if mapping was saved and loaded', function (): void {

    $acc = BankAccount::orderBy('id', 'desc')->first();
    $transactionAmount = BankTransaction::where('konto_id', '=', $acc->id)->count();
    expect($transactionAmount)->toBe(5);

    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id) // the account with the saved transactions
        ->assertSetStrict('mapping.date', 4)
        ->assertSetStrict('mapping.valuta', 5)
        ->assertSetStrict('mapping.empf_name', 6)
        ->assertSetStrict('mapping.empf_iban', 7)
        ->assertSetStrict('mapping.type', 9)
        ->assertSetStrict('mapping.zweck', 10)
        ->assertSetStrict('mapping.value', 11)
        ->assertSetStrict('mapping.saldo', 13)
        ->assertSetStrict('csvOrderReversed', true);
});

test('csv upload with correct saldo check', function (): void {
    // same csv again has saldo errors
    $acc = BankAccount::orderBy('id', 'desc')->firstOrFail();
    $transactionAmount = BankTransaction::where('konto_id', '=', $acc->id)->count();
    expect($transactionAmount)->toBe(5);

    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id) // an account with the saved transactions from above
        ->set('csv', $csvFile)
        ->call('save')
        ->assertHasErrors(['mapping.saldo']);
    $transactionAmount = BankTransaction::where('konto_id', '=', $acc->id)->count();
    expect($transactionAmount)->toBe(5);
});

test('csv import account loads with the correct order', function (): void {
    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    Livewire::actingAs(cashOfficer())->test('pages::bank.csv-import')
        ->set('csv', $csvFile)
        ->assertSet('data.0', 1);

})->todo();

test('large csv with umlaut beyond the finfo sample window still parses', function (): void {
    $acc = BankAccount::factory()->create();

    // finfo (guessEncoding) only samples the first ~64 KB of a file to detect
    // the charset. A real yearly bank statement easily exceeds that. When the
    // only non-ASCII byte (an umlaut) sits *after* that window, finfo wrongly
    // reports "us-ascii" and utf8Content()'s strict iconv() throws on the byte,
    // which parseCSV() swallows into konto.csv-parse-error ("wrong file format").
    $header = "date;valuta;empf;zweck;value;saldo\n";
    $asciiRow = "01.01.2026;01.01.2026;ACME GMBH;RECHNUNG 12345;-10,00;100,00\n";
    $umlautRow = "02.01.2026;02.01.2026;M\xFCller GmbH;Geb\xFChr;-5,00;95,00\n"; // CP1252 ü, past 64 KB
    $content = $header.str_repeat($asciiRow, 2000).$umlautRow;

    $csvFile = File::createWithContent('statement.csv', $content);

    $lw = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', $csvFile)
        ->assertHasNoErrors(['csv'])
        ->assertSet('header', ['date', 'valuta', 'empf', 'zweck', 'value', 'saldo']);

    // the umlaut must be converted to UTF-8, not dropped or turned into mojibake
    expect($lw->get('data')->flatten()->contains('Müller GmbH'))->toBeTrue();
});

test('saldo is auto-calculated from 0 when not mapped on an empty account', function (): void {
    $acc = BankAccount::factory()->create();

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', testFile('csv-import/test-correct-semicolon.csv'));
    mapSemicolonFixture($wire, withSaldo: false)
        ->call('reverseCsvOrder') // bank exports newest-first; we want oldest-first
        ->call('save')
        ->assertHasNoErrors();

    $transactions = BankTransaction::where('konto_id', $acc->id)->orderBy('id')->get();
    // running total seeded from 0: -13.14, +5, +2, -43.40, +420.99
    expect($transactions->pluck('saldo')->all())
        ->toBe(['-13.14', '-8.14', '-6.14', '-49.54', '371.45']);
});

test('saldo auto-calculation is seeded from the last existing transaction', function (): void {
    $acc = BankAccount::factory()->create();
    BankTransaction::factory()->create(['konto_id' => $acc->id, 'id' => 1, 'value' => '0.00', 'saldo' => '100.00']);

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', testFile('csv-import/test-correct-semicolon.csv'));
    mapSemicolonFixture($wire, withSaldo: false)
        ->call('reverseCsvOrder')
        ->call('save')
        ->assertHasNoErrors();

    // existing saldo 100.00 → 86.86, 91.86, 93.86, 50.46, 471.45
    $imported = BankTransaction::where('konto_id', $acc->id)->where('id', '>', 1)->orderBy('id')->get();
    expect($imported->pluck('saldo')->all())
        ->toBe(['86.86', '91.86', '93.86', '50.46', '471.45'])
        ->and(BankTransaction::where('konto_id', $acc->id)->count())->toBe(6);
});

test('a continuation import with matching saldo succeeds and appends transactions', function (): void {
    $acc = BankAccount::factory()->create();
    // fixture's oldest row is value -13.14 / saldo 18089.63, so the prior balance is 18102.77
    BankTransaction::factory()->create(['konto_id' => $acc->id, 'id' => 1, 'value' => '0.00', 'saldo' => '18102.77']);

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', testFile('csv-import/test-correct-semicolon.csv'));
    mapSemicolonFixture($wire)
        ->call('reverseCsvOrder')
        ->call('save')
        ->assertHasNoErrors();

    expect(BankTransaction::where('konto_id', $acc->id)->count())->toBe(6)
        ->and(BankTransaction::where('konto_id', $acc->id)->orderBy('id', 'desc')->first()->saldo)->toBe('18474.22');
});

test('comma-separated csv is detected and parsed', function (): void {
    $acc = BankAccount::factory()->create();
    $content = "date,empf,iban,value,saldo\n"
        ."03.06.2024,Person 1,DE73447318315829961821,-13.14,18089.63\n"
        ."04.06.2024,Person 2,DE79181333728582849451,5.00,18094.63\n";

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', File::createWithContent('comma.csv', $content))
        ->assertHasNoErrors(['csv'])
        ->assertSet('separator', ',')
        ->assertSet('header', ['date', 'empf', 'iban', 'value', 'saldo']);
    expect($wire->get('data'))->toHaveCount(2);
});

test('invalid iban in the mapped column is rejected', function (): void {
    $acc = BankAccount::factory()->create();
    $content = "date;empf;iban;value\n"
        ."03.06.2024;Person 1;NOT-AN-IBAN;-13,14\n"
        ."04.06.2024;Person 2;DE79181333728582849451;5,00\n";

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', File::createWithContent('bad-iban.csv', $content))
        ->set('mapping.empf_iban', 2)
        ->assertHasErrors(['mapping.empf_iban']);
});

test('non-numeric value in the mapped value column is rejected', function (): void {
    $acc = BankAccount::factory()->create();
    $content = "date;empf;value\n"
        ."03.06.2024;Person 1;-13,14\n"
        ."04.06.2024;Person 2;not-a-number\n";

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', File::createWithContent('bad-value.csv', $content))
        ->set('mapping.value', 2)
        ->assertHasErrors(['mapping.value']);
});

test('umlauts are preserved into the database (utf-8 source)', function (): void {
    $acc = BankAccount::factory()->create();
    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', testFile('csv-import/test-correct-semicolon.csv'));
    mapSemicolonFixture($wire, withSaldo: false)
        ->call('reverseCsvOrder')
        ->call('save')
        ->assertHasNoErrors();

    expect(BankTransaction::where('konto_id', $acc->id)->orderBy('id')->first()->type)->toBe('Euro-Überweisung');
});

test('umlauts are preserved into the database (windows-1252 source)', function (): void {
    $acc = BankAccount::factory()->create();
    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', testFile('csv-import/test-correct-semicolon-win-enc.csv'));
    mapSemicolonFixture($wire, withSaldo: false)
        ->call('reverseCsvOrder')
        ->call('save')
        ->assertHasNoErrors();

    expect(BankTransaction::where('konto_id', $acc->id)->orderBy('id')->first()->type)->toBe('Euro-Überweisung');
});

test('blank and separator-only lines are ignored', function (): void {
    $acc = BankAccount::factory()->create();
    $content = "date;empf;value\n"
        ."03.06.2024;Person 1;-13,14\n"
        ."\n"
        .";;\n"
        ."04.06.2024;Person 2;5,00\n";

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', File::createWithContent('blanks.csv', $content))
        ->assertHasNoErrors(['csv']);
    expect($wire->get('data'))->toHaveCount(2);
});

test('a header-only csv parses without rows and without error', function (): void {
    $acc = BankAccount::factory()->create();

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', File::createWithContent('header-only.csv', "date;empf;value\n"))
        ->assertSuccessful()
        ->assertHasNoErrors(['csv'])
        ->assertSet('header', ['date', 'empf', 'value']);
    expect($wire->get('data'))->toHaveCount(0);
});

test('an empty csv does not crash the component', function (): void {
    $acc = BankAccount::factory()->create();

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', File::createWithContent('empty.csv', ''))
        ->assertSuccessful();
    expect($wire->get('data'))->toHaveCount(0);
});

test('a successful import redirects to the imported account view', function (): void {
    $acc = BankAccount::factory()->create();

    // The konto page selects an account via the path segments konto/{hhp_id}/{konto_id}.
    // hhp_id is the latest budget plan, the same id the component forwards to.
    $hhp = LegacyBudgetPlan::latest()?->id;

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', testFile('csv-import/test-correct-semicolon.csv'));
    mapSemicolonFixture($wire)
        ->call('reverseCsvOrder')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('legacy.konto', ['hhp_id' => $hhp, 'konto_id' => $acc->id]));
});

test('a successful import flashes a success message', function (): void {
    $acc = BankAccount::factory()->create();

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', testFile('csv-import/test-correct-semicolon.csv'));
    mapSemicolonFixture($wire)
        ->call('reverseCsvOrder')
        ->call('save')
        ->assertHasNoErrors();

    // The flashed message is what message.blade.php renders on the target page
    // (session('message.text') / session('message.type')).
    expect(session()->has('message'))->toBeTrue()
        ->and(session('message.type'))->toBe('success')
        ->and(session('message.text'))->toBe(
            __('konto.csv-import-success-msg', ['new-saldo' => '18474.22', 'transaction-amount' => 5])
        );
});

test('a row whose auslage cannot be auto-marked paid still imports and warns', function (): void {
    // A Verwendungszweck like "IP-24-<p>-A<e>" triggers AuslagenHandler2::hookZahlung. These two
    // auslagen are in state "wip" (submitted, not yet approved), which the hook cannot promote
    // to paid — only "ok"/"instructed" are eligible. hookZahlung(flash:false) returns the
    // reference instead of flashing; the import still commits (source of truth) and we surface
    // one warning message. Historically this rolled the WHOLE import back into a generic,
    // invisible csv error. The Saldo nach Buchung column (index 13) is empty here, so saldo is
    // left unmapped.
    $project = Project::factory()->create();
    $e1 = Expense::factory()->create(['projekt_id' => $project->id, 'state' => 'wip;2024-01-01 00:00:00;owner;Owner']);
    $e2 = Expense::factory()->create(['projekt_id' => $project->id, 'state' => 'wip;2024-01-01 00:00:00;owner;Owner']);
    $ref1 = "IP-24-{$project->id}-A{$e1->id}";
    $ref2 = "IP-24-{$project->id}-A{$e2->id}";

    $acc = BankAccount::factory()->create();

    $content = "Bezeichnung Auftragskonto;IBAN Auftragskonto;BIC Auftragskonto;Bankname Auftragskonto;Buchungstag;Valutadatum;Name Zahlungsbeteiligter;IBAN Zahlungsbeteiligter;BIC (SWIFT-Code) Zahlungsbeteiligter;Buchungstext;Verwendungszweck;Betrag;Waehrung;Saldo nach Buchung;Bemerkung;Kategorie;Steuerrelevant;Glaeubiger ID;Mandatsreferenz\n"
        ."AStA - Basiskonto;DE12429644757213399722;NKZUVJYQ0P5;Meine Bank;05.06.2024;05.06.2024;ING-DiBa;DE02500105170137075030;IHHVRZIL;GUTSCHR. UEBERWEISUNG;{$ref1} - Q4 - Kontoführung;70;EUR;;;Sonstiges;;;\n"
        ."AStA - Basiskonto;DE12429644757213399722;NKZUVJYQ0P5;Meine Bank;05.06.2024;05.06.2024;Hostsharing;DE02500105170137075030;MWFYLYEL;GUTSCHR. UEBERWEISUNG;{$ref2} - Dezember - Hosting;80;EUR;;;Sonstiges;;;\n";

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', File::createWithContent('empty-saldo.csv', $content));

    // columns: 4=date 5=valuta 6=name 7=iban 9=type 10=zweck 11=value; saldo (13) is empty → unmapped
    $wire->set('mapping.date', 4)
        ->set('mapping.valuta', 5)
        ->set('mapping.empf_name', 6)
        ->set('mapping.empf_iban', 7)
        ->set('mapping.type', 9)
        ->set('mapping.zweck', 10)
        ->set('mapping.value', 11)
        ->call('reverseCsvOrder')
        ->call('save')
        ->assertHasNoErrors();

    // The import is the source of truth: both rows are saved despite the failing hook.
    expect(BankTransaction::where('konto_id', $acc->id)->count())->toBe(2);

    // The user is warned (not silently dropped) about the references needing manual review.
    expect(session('message.type'))->toBe('warning')
        ->and(session('message.text'))->toContain($ref1)
        ->and(session('message.text'))->toContain($ref2);
});

test('a referenced auslage in "instructed" is auto-marked paid and the import succeeds', function (): void {
    // An "instructed" Auslage whose payment shows up in the statement: hookZahlung should
    // flip its "payed" sub-state and the import reports plain success (no warning).
    // cashOfficer() is the "kv" user (ref-finanzen-kv) — the group allowed to mark payed.
    $project = Project::factory()->create();
    $expense = Expense::factory()->create([
        'projekt_id' => $project->id,
        'state' => 'instructed;2024-01-01 00:00:00;kv;Kassen Wart',
    ]);
    expect($expense->fresh()->payed)->toBeEmpty();

    $acc = BankAccount::factory()->create();

    // Verwendungszweck references this project/auslage: IP-<hhp>-<project>-A<auslage>.
    // A leading "konto" column keeps the mapped fields off CSV index 0 (real bank exports
    // start with metadata columns; index 0 can't be a mapping target — see save()'s ! empty check).
    $content = "konto;date;valuta;name;iban;type;zweck;value\n"
        ."AStA;05.06.2024;05.06.2024;ACME GmbH;DE02500105170137075030;GUTSCHR;IP-24-{$project->id}-A{$expense->id} Erstattung;70\n";

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', File::createWithContent('payed.csv', $content))
        ->set('mapping.date', 1)
        ->set('mapping.valuta', 2)
        ->set('mapping.empf_name', 3)
        ->set('mapping.empf_iban', 4)
        ->set('mapping.type', 5)
        ->set('mapping.zweck', 6)
        ->set('mapping.value', 7)
        ->call('save')
        ->assertHasNoErrors();

    expect(BankTransaction::where('konto_id', $acc->id)->count())->toBe(1)
        ->and(session('message.type'))->toBe('success');

    // the hook actually transitioned the auslage: the "payed" audit column is now set
    expect($expense->fresh()->payed)->not->toBeEmpty();
});

test('a referenced auslage still in "ok" is auto-promoted to instructed and marked paid', function (): void {
    // An approved ("ok") Auslage that was never formally instructed but whose payment shows up
    // in the statement: hookZahlung auto-promotes ok -> instructed (same ref-finanzen-kv group
    // as payed) and then flips the "payed" sub-state. The import reports plain success.
    $project = Project::factory()->create();
    $expense = Expense::factory()->create([
        'projekt_id' => $project->id,
        'state' => 'ok;2024-01-01 00:00:00;hv;Haushalts Wart',
    ]);
    expect($expense->fresh()->payed)->toBeEmpty();

    $acc = BankAccount::factory()->create();

    $content = "konto;date;valuta;name;iban;type;zweck;value\n"
        ."AStA;05.06.2024;05.06.2024;ACME GmbH;DE02500105170137075030;GUTSCHR;IP-24-{$project->id}-A{$expense->id} Erstattung;70\n";

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', File::createWithContent('ok-payed.csv', $content))
        ->set('mapping.date', 1)
        ->set('mapping.valuta', 2)
        ->set('mapping.empf_name', 3)
        ->set('mapping.empf_iban', 4)
        ->set('mapping.type', 5)
        ->set('mapping.zweck', 6)
        ->set('mapping.value', 7)
        ->call('save')
        ->assertHasNoErrors();

    expect(BankTransaction::where('konto_id', $acc->id)->count())->toBe(1)
        ->and(session('message.type'))->toBe('success');

    // the hook walked ok -> instructed -> payed: main state is now instructed and payed is set
    $fresh = $expense->fresh();
    expect($fresh->payed)->not->toBeEmpty()
        ->and($fresh->state)->toStartWith('instructed');
});

test('a field mapped to CSV column index 0 is imported', function (): void {
    // Regression: save() used ! empty($mapping[col]); ! empty(0) is false, so a field
    // mapped to the first CSV column (here "date" at index 0) was silently dropped,
    // producing a NOT NULL insert error swallowed as a generic import failure.
    $acc = BankAccount::factory()->create();

    $content = "date;valuta;name;iban;type;zweck;value\n"
        ."05.06.2024;05.06.2024;ACME GmbH;DE02500105170137075030;GUTSCHR;Erstattung;70\n";

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', File::createWithContent('col-zero.csv', $content))
        ->set('mapping.date', 0)
        ->set('mapping.valuta', 1)
        ->set('mapping.empf_name', 2)
        ->set('mapping.empf_iban', 3)
        ->set('mapping.type', 4)
        ->set('mapping.zweck', 5)
        ->set('mapping.value', 6)
        ->call('save')
        ->assertHasNoErrors();

    $transaction = BankTransaction::where('konto_id', $acc->id)->firstOrFail();
    expect($transaction->date->format('Y-m-d'))->toBe('2024-06-05')
        ->and(BankTransaction::where('konto_id', $acc->id)->count())->toBe(1);
});

test('reverseCsvOrder flips the parsed data order', function (): void {
    $acc = BankAccount::factory()->create();

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', testFile('csv-import/test-correct-semicolon.csv'));

    // fresh upload keeps the file's own order: newest first (Entry 5, Zweck at index 10)
    expect($wire->get('data')->first()[10])->toBe('Entry 5');

    $wire->call('reverseCsvOrder');
    expect($wire->get('data')->first()[10])->toBe('Entry 1');
});

test('a utf-8 BOM does not break parsing', function (): void {
    $acc = BankAccount::factory()->create();
    $content = "\xEF\xBB\xBF"."date;empf;value\n"
        ."03.06.2024;Person 1;-13,14\n"
        ."04.06.2024;Person 2;5,00\n";

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', File::createWithContent('bom.csv', $content))
        ->assertHasNoErrors(['csv']);
    expect($wire->get('data'))->toHaveCount(2);
});

test('preview does not crash when a date field is mapped to a non-date column', function (): void {
    $acc = BankAccount::factory()->create();

    // columns: 0=date 1=empf 2=iban 3=value 4=saldo
    $content = "date;empf;iban;value;saldo\n"
        ."01.01.2026;ACME;DE12429644757213399722;-10,00;100,00\n"
        ."02.01.2026;BETA;DE12429644757213399722;-5,00;95,00\n";

    // Map the date field onto the IBAN column. guessDate() can't parse an IBAN and
    // throws InvalidFormatException; the preview must degrade to the raw value
    // instead of bubbling a 500 (regression: it used to hard-crash the render).
    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', File::createWithContent('statement.csv', $content))
        ->set('mapping.date', 2)
        ->assertSuccessful()
        ->assertSee('DE12429644757213399722');
});

// 11) clearCsv() must wipe the upload and all derived state so the user can re-upload.

test('clearCsv resets the uploaded file and parsed data', function (): void {
    $acc = BankAccount::factory()->create();

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', testFile('csv-import/test-correct-semicolon.csv'));
    expect($wire->get('data'))->toHaveCount(5);

    $wire->call('clearCsv')
        ->assertSet('csv', null)
        ->assertSet('header', null); // reset() restores the declared default (null)
    expect($wire->get('data'))->toHaveCount(0);
});

// 12) The preview must positively format mapped values (decimal + IBAN), not just
//     avoid crashing. Covers the happy path of formatDataView().

test('preview formats decimal and iban values', function (): void {
    $acc = BankAccount::factory()->create();

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $acc->id)
        ->set('csv', testFile('csv-import/test-correct-semicolon.csv'))
        ->set('mapping.value', 11)
        ->set('mapping.empf_iban', 7)
        ->assertSee('420,99 €')                       // first (newest) row value, decimal-formatted
        ->assertSee('-13,14 €')                       // last row value
        ->assertSee('DE63 3650 9085 1878 2541 00');   // IBAN in human-readable groups of four
});

// 13) Switching the account applies that account's saved order: when it differs from
//     the current one, the already-parsed data must be flipped (updatedAccountId()).

test('switching account reverses data when the saved order differs', function (): void {
    $accNormal = BankAccount::factory()->create();                  // no settings → order false
    $accReversed = BankAccount::factory()->create();
    $accReversed->csv_import_settings = ['csv_order_reversed' => true];
    $accReversed->save();

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.csv-import')
        ->set('account_id', $accNormal->id)
        ->set('csv', testFile('csv-import/test-correct-semicolon.csv'));
    expect($wire->get('data')->first()[10])->toBe('Entry 5');       // file order, newest first

    $wire->set('account_id', $accReversed->id);
    expect($wire->get('data')->first()[10])->toBe('Entry 1');       // flipped to match saved order
});
