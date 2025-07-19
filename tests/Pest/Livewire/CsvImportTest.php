<?php

use App\Livewire\TransactionImportWire;
use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;

$acc = null;

test('csv import is accessible as cash officer', function (): void {
    Livewire::actingAs(cashOfficer())->test(TransactionImportWire::class)
        ->assertSuccessful();
});

test('csv import is not accessible as budget officer', function (): void {
    Livewire::actingAs(budgetManager())->test(TransactionImportWire::class)
        ->assertForbidden();
});

test('csv import is not accessible as normal user', function (): void {
    Livewire::actingAs(user())->test(TransactionImportWire::class)
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
    $wire = Livewire::actingAs(cashOfficer())->test(TransactionImportWire::class);
    foreach ($lastTransactions as $transaction) {
        Log::debug($transaction->date);
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
    $wire = Livewire::actingAs(cashOfficer())->test(TransactionImportWire::class);
    foreach ($noTransactions as $id) {
        $wire->set('account_id', $id)
            ->assertSee(__('konto.csv-no-transaction'));
    }
});

test('csv upload visibility', function (): void {
    $wire = Livewire::actingAs(cashOfficer())->test(TransactionImportWire::class);
    $accountIds = BankAccount::all()->pluck('id')->toArray();
    foreach ($accountIds as $accountId) {
        $wire->set('account_id', $accountId)
            ->assertSee(__('konto.csv-upload-headline'));
    }
});

test('php.ini has utf8 as default_charset', function (): void {
    expect(strtolower(ini_get('default_charset')))->toEqual('utf-8');
});

test('parse csv utf8 encoding', function ($csvHeader, $csvData): void {
    $acc = BankAccount::factory()->create();

    $csvFile = testFile('csv-import/test-correct-semicolon.csv');
    Livewire::actingAs(cashOfficer())
        ->test(TransactionImportWire::class)
        ->set('account_id', $acc->id) // an account without transactions
        ->set('csv', $csvFile)
        // check if file is correctly parsed
        ->assertSet('header', $csvHeader)
        ->assertSet('data', collect($csvData));
})->with('csv imports');

test('parse csv win encoding', function ($csvHeader, $csvData): void {
    $acc = BankAccount::orderBy('id', 'desc')->first();
    $csvFile = testFile('csv-import/test-correct-semicolon-win-enc.csv');

    Livewire::actingAs(cashOfficer())
        ->test(TransactionImportWire::class)
        ->set('account_id', $acc->id) // an account without transactions
        ->set('csv', $csvFile)
        ->assertSet('header', $csvHeader)
        ->assertSet('data', collect($csvData));
})->with('csv imports');

test('views showing properly', function (): void {
    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    $lw = Livewire::actingAs(cashOfficer())
        ->test(TransactionImportWire::class)
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
        ->test(TransactionImportWire::class)
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
        ->test(TransactionImportWire::class)
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
        ->test(TransactionImportWire::class)
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
        ->test(TransactionImportWire::class)
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
        ->test(TransactionImportWire::class)
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
        ->test(TransactionImportWire::class)
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
        ->test(TransactionImportWire::class)
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
        ->test(TransactionImportWire::class)
        ->set('account_id', $acc->id) // an account with the saved transactions from above
        ->set('csv', $csvFile)
        ->call('save')
        ->assertHasErrors(['mapping.saldo']);
    $transactionAmount = BankTransaction::where('konto_id', '=', $acc->id)->count();
    expect($transactionAmount)->toBe(5);
});

test('csv import account loads with the correct order', function () {
    $csvFile = testFile('csv-import/test-correct-semicolon.csv');

    Livewire::actingAs(cashOfficer())->test(TransactionImportWire::class)
        ->set('csv', $csvFile)
        ->assertSet('data.0', 1);

})->todo();
