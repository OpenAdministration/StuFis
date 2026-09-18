<?php

use App\Support\Fints\CamtStatementConverter;
use App\Support\Fints\CamtStatementException;
use Fhp\Model\StatementOfAccount\Statement;

/**
 * A camt.052 account report as a bank returns it from HKCAZ: one report, one pair of
 * balances, and entries spread over several booking days. The daily opening balances are
 * nowhere in the document - they only follow from carrying the report's opening balance
 * forward, which is exactly what php-fints' own CAMT parser fails to do.
 *
 * 100.00 -> day 1: -70.00, +20.00 -> 50.00 -> day 2: -25.00 -> 25.00
 */
function camtReportOverTwoDays(string $opening = '100.00', string $closing = '25.00'): string
{
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.052.001.02">
  <BkToCstmrAcctRpt>
    <GrpHdr><MsgId>M1</MsgId><CreDtTm>2024-06-07T03:00:00</CreDtTm></GrpHdr>
    <Rpt>
      <Id>R1</Id><CreDtTm>2024-06-07T03:00:00</CreDtTm>
      <Acct><Id><IBAN>DE12429644757213399722</IBAN></Id></Acct>
      <Bal><Tp><CdOrPrtry><Cd>PRCD</Cd></CdOrPrtry></Tp><Amt Ccy="EUR">{$opening}</Amt><CdtDbtInd>CRDT</CdtDbtInd><Dt><Dt>2024-06-04</Dt></Dt></Bal>
      <Bal><Tp><CdOrPrtry><Cd>CLBD</Cd></CdOrPrtry></Tp><Amt Ccy="EUR">{$closing}</Amt><CdtDbtInd>CRDT</CdtDbtInd><Dt><Dt>2024-06-06</Dt></Dt></Bal>
      <Ntry>
        <Amt Ccy="EUR">70.00</Amt><CdtDbtInd>DBIT</CdtDbtInd><Sts>BOOK</Sts>
        <BookgDt><Dt>2024-06-05</Dt></BookgDt><ValDt><Dt>2024-06-05</Dt></ValDt>
        <BkTxCd><Prtry><Cd>NTRF+177</Cd><Issr>DK</Issr></Prtry></BkTxCd>
        <NtryDtls><TxDtls>
          <Refs><EndToEndId>AUSLAGE-1</EndToEndId></Refs>
          <RltdPties><Cdtr><Nm>ACME GmbH</Nm></Cdtr><CdtrAcct><Id><IBAN>DE02500105170137075030</IBAN></Id></CdtrAcct></RltdPties>
          <RltdAgts><CdtrAgt><FinInstnId><BIC>INGDDEFFXXX</BIC></FinInstnId></CdtrAgt></RltdAgts>
          <RmtInf><Ustrd>AUSLAGE-1 Erstattung</Ustrd></RmtInf>
        </TxDtls></NtryDtls>
      </Ntry>
      <Ntry>
        <Amt Ccy="EUR">20.00</Amt><CdtDbtInd>CRDT</CdtDbtInd><Sts>BOOK</Sts>
        <BookgDt><Dt>2024-06-05</Dt></BookgDt><ValDt><Dt>2024-06-05</Dt></ValDt>
        <BkTxCd><Prtry><Cd>NTRF+166</Cd><Issr>DK</Issr></Prtry></BkTxCd>
        <NtryDtls><TxDtls>
          <RltdPties><Dbtr><Nm>Spendende Person</Nm></Dbtr></RltdPties>
          <RmtInf><Ustrd>Spende</Ustrd></RmtInf>
        </TxDtls></NtryDtls>
      </Ntry>
      <Ntry>
        <Amt Ccy="EUR">25.00</Amt><CdtDbtInd>DBIT</CdtDbtInd><Sts>BOOK</Sts>
        <BookgDt><Dt>2024-06-06</Dt></BookgDt><ValDt><Dt>2024-06-06</Dt></ValDt>
        <BkTxCd><Prtry><Cd>NDDT+105</Cd><Issr>DK</Issr></Prtry></BkTxCd>
        <NtryDtls><TxDtls>
          <RmtInf><Ustrd>Kontofuehrung</Ustrd></RmtInf>
        </TxDtls></NtryDtls>
      </Ntry>
    </Rpt>
  </BkToCstmrAcctRpt>
</Document>
XML;
}

test('it carries the opening balance across booking days', function (): void {
    // The regression this guards: php-fints' CAMT parser hands every day the report's single
    // opening balance, so day 2 opened at 100.00 instead of 50.00. saveStatements() checks
    // each statement against the previous one's close and rolled the whole import back with
    // "Die Kontoauszüge der Bank sind nicht lückenlos" as soon as a fetch spanned two days.
    $statements = new CamtStatementConverter()->convert([camtReportOverTwoDays()])->getStatements();

    expect($statements)->toHaveCount(2);

    [$first, $second] = $statements;

    expect($first->getDate()->format('Y-m-d'))->toBe('2024-06-05')
        ->and($first->getStartBalance())->toBe(100.0)
        ->and($first->getCreditDebit())->toBe(Statement::CD_CREDIT)
        ->and($first->getEndBalance())->toBe(50.0)
        ->and($second->getDate()->format('Y-m-d'))->toBe('2024-06-06')
        ->and($second->getStartBalance())->toBe(50.0)
        ->and($second->getEndBalance())->toBe(25.0);
});

test('each day opens exactly where the previous day closed', function (): void {
    // Stated as the invariant saveStatements() actually enforces, rather than as fixed
    // numbers, so the test keeps meaning if the fixture is extended.
    $statements = new CamtStatementConverter()->convert([camtReportOverTwoDays()])->getStatements();

    $previousEnd = null;
    foreach ($statements as $statement) {
        $signedStart = $statement->getStartBalance() * ($statement->getCreditDebit() === Statement::CD_DEBIT ? -1 : 1);
        if ($previousEnd !== null) {
            expect($signedStart)->toBe($previousEnd);
        }
        $previousEnd = $statement->getEndBalance();
    }
});

test('it splits amounts into an unsigned magnitude and a credit/debit mark', function (): void {
    // FintsController::convertToCent() takes the sign from the mark and abs() the amount, the
    // same shape the MT940 parser produces. A signed magnitude would come out positive.
    $statements = new CamtStatementConverter()->convert([camtReportOverTwoDays()])->getStatements();
    [$debit, $credit] = $statements[0]->getTransactions();

    expect($debit->getAmount())->toBe(70.0)
        ->and($debit->getCreditDebit())->toBe(Statement::CD_DEBIT)
        ->and($credit->getAmount())->toBe(20.0)
        ->and($credit->getCreditDebit())->toBe(Statement::CD_CREDIT);
});

test('it maps the fields that saveStatements writes to the konto row', function (): void {
    $statements = new CamtStatementConverter()->convert([camtReportOverTwoDays()])->getStatements();
    $transaction = $statements[0]->getTransactions()[0];

    expect($transaction->getMainDescription())->toBe('AUSLAGE-1 Erstattung')
        ->and($transaction->getEndToEndID())->toBe('AUSLAGE-1')
        ->and($transaction->getName())->toBe('ACME GmbH')
        ->and($transaction->getAccountNumber())->toBe('DE02500105170137075030')
        ->and($transaction->getBankCode())->toBe('INGDDEFFXXX')
        ->and($transaction->getBookingDate()->format('Y-m-d'))->toBe('2024-06-05')
        ->and($transaction->getValutaDate()->format('Y-m-d'))->toBe('2024-06-05');
});

test('a balance that goes negative is reported as a debit mark', function (): void {
    // The account runs into the red on day one: the magnitude has to stay unsigned and the
    // sign move into the mark, or convertToCent() reads the saldo back as positive.
    // The closing balance in the document is irrelevant here - the daily balances are derived
    // from the opening one plus the entries, which is the whole point of the conversion.
    $statements = new CamtStatementConverter()->convert([camtReportOverTwoDays('10.00', '65.00')])->getStatements();

    expect($statements[0]->getStartBalance())->toBe(10.0)
        ->and($statements[0]->getCreditDebit())->toBe(Statement::CD_CREDIT)
        ->and($statements[1]->getStartBalance())->toBe(40.0)
        ->and($statements[1]->getCreditDebit())->toBe(Statement::CD_DEBIT)
        ->and($statements[1]->getEndBalance())->toBe(-65.0);
});

test('it continues one running balance across the pages of a paginated response', function (): void {
    // A paginated HKCAZ response arrives as several whole documents. Only the first one opens
    // the range; taking each document's own opening balance would restart the chain per page.
    $second = str_replace(
        ['<Dt>2024-06-05</Dt>', '<Dt>2024-06-06</Dt>', '<Amt Ccy="EUR">100.00</Amt>'],
        ['<Dt>2024-06-07</Dt>', '<Dt>2024-06-08</Dt>', '<Amt Ccy="EUR">999.00</Amt>'],
        camtReportOverTwoDays()
    );

    $statements = new CamtStatementConverter()->convert([camtReportOverTwoDays(), $second])->getStatements();

    expect($statements)->toHaveCount(4)
        ->and($statements[2]->getDate()->format('Y-m-d'))->toBe('2024-06-07')
        // 25.00 left by page one, not the 999.00 page two reports as its own opening.
        ->and($statements[2]->getStartBalance())->toBe(25.0)
        ->and($statements[3]->getEndBalance())->toBe(-50.0);
});

test('an empty response is no statement rather than a failure', function (): void {
    // The bank has nothing in the range. Treating that as a failure would make every
    // up-to-date account fall back to MT940 and ask for a second TAN for nothing.
    expect(new CamtStatementConverter()->convert([])->getStatements())->toBe([])
        ->and(new CamtStatementConverter()->convert([''])->getStatements())->toBe([]);
});

test('an unreadable document is reported as recoverable so the MT940 fallback can run', function (): void {
    expect(fn () => new CamtStatementConverter()->convert(['<nonsense/>']))
        ->toThrow(CamtStatementException::class);
});

test('entries without an opening balance are rejected rather than counted from zero', function (): void {
    // Without the opening balance every stored saldo would be off by the account's real
    // balance, which silently breaks the resume point of every later sync.
    $withoutBalances = preg_replace('#<Bal>.*?</Bal>#s', '', camtReportOverTwoDays());

    expect(fn () => new CamtStatementConverter()->convert([$withoutBalances]))
        ->toThrow(CamtStatementException::class);
});
