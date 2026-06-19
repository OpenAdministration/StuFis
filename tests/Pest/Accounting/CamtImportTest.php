<?php

use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\Legacy\Expense;
use App\Models\Legacy\Project;
use App\Support\Import\CamtImportParser;
use Illuminate\Http\Testing\File;

/**
 * Build a minimal, XSD-valid camt.053.001.02 statement with a single booked debit entry
 * carrying the given EndToEndId — used to exercise the shared hookZahlung path with a
 * dynamically created Auslage reference.
 */
function camtWithSingleEntry(string $ref, string $amount = '70.00', string $opening = '100.00', string $closing = '30.00'): string
{
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.053.001.02">
  <BkToCstmrStmt>
    <GrpHdr><MsgId>M1</MsgId><CreDtTm>2024-06-06T03:00:00</CreDtTm></GrpHdr>
    <Stmt>
      <Id>S1</Id><CreDtTm>2024-06-06T03:00:00</CreDtTm>
      <Acct><Id><IBAN>DE12429644757213399722</IBAN></Id></Acct>
      <Bal><Tp><CdOrPrtry><Cd>OPBD</Cd></CdOrPrtry></Tp><Amt Ccy="EUR">{$opening}</Amt><CdtDbtInd>CRDT</CdtDbtInd><Dt><Dt>2024-06-05</Dt></Dt></Bal>
      <Bal><Tp><CdOrPrtry><Cd>CLBD</Cd></CdOrPrtry></Tp><Amt Ccy="EUR">{$closing}</Amt><CdtDbtInd>CRDT</CdtDbtInd><Dt><Dt>2024-06-05</Dt></Dt></Bal>
      <Ntry>
        <Amt Ccy="EUR">{$amount}</Amt><CdtDbtInd>DBIT</CdtDbtInd><Sts>BOOK</Sts>
        <BookgDt><Dt>2024-06-05</Dt></BookgDt><ValDt><Dt>2024-06-05</Dt></ValDt>
        <BkTxCd><Prtry><Cd>NTRF+177</Cd><Issr>DK</Issr></Prtry></BkTxCd>
        <NtryDtls><TxDtls>
          <Refs><EndToEndId>{$ref}</EndToEndId></Refs>
          <RltdPties><Cdtr><Nm>ACME GmbH</Nm></Cdtr><CdtrAcct><Id><IBAN>DE02500105170137075030</IBAN></Id></CdtrAcct></RltdPties>
          <RmtInf><Ustrd>{$ref} Erstattung</Ustrd></RmtInf>
        </TxDtls></NtryDtls>
      </Ntry>
    </Stmt>
  </BkToCstmrStmt>
</Document>
XML;
}

test('CamtImportParser maps entries, signs amounts and reads balances', function (): void {
    $result = (new CamtImportParser)->parse(
        Storage::disk('tests')->path('camt-import/camt053-example.xml')
    );

    expect($result['openingBalance'])->toBe('100.00')
        ->and($result['closingBalance'])->toBe('48.46')
        ->and($result['rows'])->toHaveCount(3);

    $rows = $result['rows'];

    // the fixture lists entries out of date order; the parser must sort them oldest-first
    expect($rows->pluck('date')->all())->toBe(['2024-06-03', '2024-06-04', '2024-06-05']);

    // sorted oldest first, DBIT negated, structured fields extracted
    expect($rows[0])->toMatchArray([
        'date' => '2024-06-03',
        'valuta' => '2024-06-03',
        'value' => '-13.14',
        'empf_name' => 'Person 1',
        'empf_iban' => 'DE73447318315829961821',
        'empf_bic' => 'GENODEF1S04',
        'zweck' => 'IP-24-1-A1 Erstattung Material',
        'customer_ref' => 'IP-24-1-A1',
        'saldo' => '',
    ]);

    // CRDT stays positive; NOTPROVIDED end-to-end id is dropped
    expect($rows[1]['value'])->toBe('5.00')
        ->and($rows[1]['empf_name'])->toBe('Person 2')
        ->and($rows[1]['customer_ref'])->toBe('');

    // a bank-fee entry legitimately has no counterparty IBAN
    expect($rows[2]['value'])->toBe('-43.40')
        ->and($rows[2]['empf_iban'])->toBe('')
        ->and($rows[2]['empf_name'])->toBe('');
});

test('only booked entries are imported (camt.052 intraday pending are skipped)', function (): void {
    $result = (new CamtImportParser)->parse(
        Storage::disk('tests')->path('camt-import/camt052-pending.xml')
    );

    expect($result['rows'])->toHaveCount(1)
        ->and($result['rows'][0]['value'])->toBe('10.00')
        ->and($result['rows'][0]['zweck'])->toBe('booked entry')
        ->and($result['rows'][0]['empf_name'])->toBe('Booked Payer')
        // PRCD is read as the opening balance; with no CLBD, the ITBD interim balance is the
        // closing figure (100.00 + 10.00 booked == 110.00, excluding the pending 99.00)
        ->and($result['openingBalance'])->toBe('100.00')
        ->and($result['closingBalance'])->toBe('110.00');
});

test('a camt.052 without balances imports and skips the balance checks', function (): void {
    // Bal is optional in camt.052; without OPBD/CLBD/ITBD the balance + continuity checks are
    // skipped and the saldo is computed from the last DB entry (here: empty account, so 0).
    $result = (new CamtImportParser)->parse(
        Storage::disk('tests')->path('camt-import/camt052-nobalance.xml')
    );
    expect($result['openingBalance'])->toBeNull()
        ->and($result['closingBalance'])->toBeNull()
        ->and($result['rows'])->toHaveCount(2);

    $acc = BankAccount::factory()->create(['iban' => 'DE12429644757213399722']);

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.manual-import')
        ->set('account_id', $acc->id)
        ->set('upload', testFile('camt-import/camt052-nobalance.xml'))
        ->call('save')
        ->assertHasNoErrors();

    $imported = BankTransaction::where('konto_id', $acc->id)->orderBy('id')->get();
    expect($imported)->toHaveCount(2)
        ->and($imported->pluck('value')->all())->toBe(['20.00', '-5.00'])
        ->and($imported->pluck('saldo')->all())->toBe(['20.00', '15.00']); // seeded from 0
});

test('an invalid camt xml surfaces a parse error', function (): void {
    $acc = BankAccount::factory()->create(['iban' => 'DE12429644757213399722']);

    // recognised as CAMT by the namespace sniff, but structurally invalid (no GrpHdr/Stmt)
    $invalid = '<?xml version="1.0" encoding="UTF-8"?>'
        .'<Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.053.001.02"><BkToCstmrStmt></BkToCstmrStmt></Document>';

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.manual-import')
        ->set('account_id', $acc->id)
        ->set('upload', File::createWithContent('invalid.xml', $invalid))
        ->assertHasErrors(['upload'])
        ->assertSet('format', 'camt');

    expect($wire->get('data'))->toHaveCount(0);
});

test('isCamt recognises a camt xml and rejects csv', function (): void {
    $parser = new CamtImportParser;

    expect($parser->isCamt(Storage::disk('tests')->path('camt-import/camt053-example.xml')))->toBeTrue()
        ->and($parser->isCamt(Storage::disk('tests')->path('csv-import/test-correct-semicolon.csv')))->toBeFalse();
});

test('manual import is accessible as finance member and forbidden for users', function (): void {
    Livewire::actingAs(cashOfficer())->test('pages::bank.manual-import')->assertSuccessful();
    Livewire::actingAs(budgetManager())->test('pages::bank.manual-import')->assertSuccessful();
    Livewire::actingAs(user())->test('pages::bank.manual-import')->assertForbidden();
});

test('uploading a camt file is detected and parsed into transactions', function (): void {
    $acc = BankAccount::factory()->create(['iban' => 'DE12429644757213399722']); // matches the fixture statement IBAN

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.manual-import')
        ->set('account_id', $acc->id)
        ->set('upload', testFile('camt-import/camt053-example.xml'))
        ->assertHasNoErrors(['upload'])
        ->assertSet('format', 'camt')
        // preview uses age-based labels for CAMT (oldest on top), not the CSV file-position labels
        ->assertSee(__('konto.camt-preview-oldest'))
        ->assertSee(__('konto.camt-preview-newest'))
        ->assertDontSee(__('konto.csv-preview-first'))
        ->assertSee('03.06.2024')  // oldest entry shown
        ->assertSee('05.06.2024'); // newest entry shown

    expect($wire->get('data'))->toHaveCount(3)
        ->and($wire->get('closingBalance'))->toBe('48.46');
});

test('a camt import anchors the saldo to the statement opening balance', function (): void {
    // empty account: the saldo must be seeded from the statement opening balance (100.00),
    // not 0, so the stored ladder matches the bank and ends on the closing balance (48.46).
    $acc = BankAccount::factory()->create(['iban' => 'DE12429644757213399722']); // matches the fixture statement IBAN

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.manual-import')
        ->set('account_id', $acc->id)
        ->set('upload', testFile('camt-import/camt053-example.xml'))
        ->call('save')
        ->assertHasNoErrors();

    $imported = BankTransaction::where('konto_id', $acc->id)->orderBy('id')->get();

    expect($imported)->toHaveCount(3)
        ->and($imported->pluck('saldo')->all())->toBe(['86.86', '91.86', '48.46'])
        ->and($imported->pluck('value')->all())->toBe(['-13.14', '5.00', '-43.40'])
        ->and($imported->last()->saldo)->toBe('48.46') // == statement closing balance
        // the fee row imported despite having no counterparty IBAN
        ->and($imported->last()->empf_iban)->toBe('');

    // CAMT must not write a CSV mapping template onto the account
    expect($acc->fresh()->csv_import_settings['csv_import_mapping'] ?? null)->toBeNull();
});

test('a camt import continues from the last stored saldo when balances line up', function (): void {
    $acc = BankAccount::factory()->create(['iban' => 'DE12429644757213399722']); // matches the fixture statement IBAN
    // prior saldo equals the statement opening balance (100.00) — a clean continuation
    BankTransaction::factory()->create(['konto_id' => $acc->id, 'id' => 1, 'value' => '0.00', 'saldo' => '100.00']);

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.manual-import')
        ->set('account_id', $acc->id)
        ->set('upload', testFile('camt-import/camt053-example.xml'))
        ->call('save')
        ->assertHasNoErrors();

    $imported = BankTransaction::where('konto_id', $acc->id)->where('id', '>', 1)->orderBy('id')->get();
    expect($imported)->toHaveCount(3)
        ->and($imported->pluck('saldo')->all())->toBe(['86.86', '91.86', '48.46']);
});

test('a camt statement that does not continue the stored saldo (gap) is rejected', function (): void {
    $acc = BankAccount::factory()->create(['iban' => 'DE12429644757213399722']); // matches the fixture statement IBAN
    // prior saldo (500.00) does not match the statement opening balance (100.00) → gap
    BankTransaction::factory()->create(['konto_id' => $acc->id, 'id' => 1, 'value' => '0.00', 'saldo' => '500.00']);

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.manual-import')
        ->set('account_id', $acc->id)
        ->set('upload', testFile('camt-import/camt053-example.xml'))
        ->call('save')
        ->assertHasErrors(['upload']);

    // nothing imported beyond the seeded row
    expect(BankTransaction::where('konto_id', $acc->id)->count())->toBe(1);
});

test('a camt statement with missing entries (balance mismatch) is rejected', function (): void {
    $acc = BankAccount::factory()->create(['iban' => 'DE12429644757213399722']); // matches the fixture statement IBAN

    // tamper with the closing balance so opening + entries no longer reconciles
    $broken = str_replace('<Amt Ccy="EUR">48.46</Amt>', '<Amt Ccy="EUR">99.99</Amt>',
        Storage::disk('tests')->get('camt-import/camt053-example.xml'));

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.manual-import')
        ->set('account_id', $acc->id)
        ->set('upload', File::createWithContent('broken.xml', $broken))
        ->call('save')
        ->assertHasErrors(['upload']);

    expect(BankTransaction::where('konto_id', $acc->id)->count())->toBe(0);
});

test('a camt statement uploaded to the wrong account (iban mismatch) is rejected', function (): void {
    // the statement belongs to DE12429644757213399722; this account has a different IBAN
    $acc = BankAccount::factory()->create(['iban' => 'DE02500105170137075030']);

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.manual-import')
        ->set('account_id', $acc->id)
        ->set('upload', testFile('camt-import/camt053-example.xml'))
        ->call('save')
        ->assertHasErrors(['upload']);

    expect(BankTransaction::where('konto_id', $acc->id)->count())->toBe(0);
});

test('a camt EndToEndId reference auto-marks an instructed auslage as paid', function (): void {
    // The shared save() pipeline runs hookZahlung on each row's zweck; here the structured
    // EndToEndId lands in the Verwendungszweck and promotes the matching Auslage.
    $project = Project::factory()->create();
    $expense = Expense::factory()->create([
        'projekt_id' => $project->id,
        'state' => 'instructed;2024-01-01 00:00:00;kv;Kassen Wart',
    ]);
    $ref = "IP-24-{$project->id}-A{$expense->id}";

    $acc = BankAccount::factory()->create(['iban' => 'DE12429644757213399722']); // matches the fixture statement IBAN

    Livewire::actingAs(cashOfficer())
        ->test('pages::bank.manual-import')
        ->set('account_id', $acc->id)
        ->set('upload', File::createWithContent('hook.xml', camtWithSingleEntry($ref)))
        ->call('save')
        ->assertHasNoErrors();

    expect(BankTransaction::where('konto_id', $acc->id)->count())->toBe(1)
        ->and(session('message.type'))->toBe('success')
        ->and($expense->fresh()->payed)->not->toBeEmpty();
});

test('switching account after a camt upload keeps the parsed rows', function (): void {
    $accA = BankAccount::factory()->create(['iban' => 'DE12429644757213399722']); // matches the fixture statement IBAN
    $accB = BankAccount::factory()->create(['iban' => 'DE12429644757213399722']); // matches the fixture statement IBAN

    $wire = Livewire::actingAs(cashOfficer())
        ->test('pages::bank.manual-import')
        ->set('account_id', $accA->id)
        ->set('upload', testFile('camt-import/camt053-example.xml'))
        ->assertSet('format', 'camt');

    $wire->set('account_id', $accB->id);

    expect($wire->get('data'))->toHaveCount(3)
        ->and($wire->get('format'))->toBe('camt');
});
