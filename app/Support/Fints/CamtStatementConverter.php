<?php

declare(strict_types=1);

namespace App\Support\Fints;

use App\Support\Import\CamtImportParser;
use Fhp\Model\StatementOfAccount\StatementOfAccount;
use Fhp\MT940\MT940;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Turns the camt.052 documents of a FinTS HKCAZ response into the StatementOfAccount model
 * that FintsController::saveStatements() consumes.
 *
 * php-fints can do this itself (Fhp\CAMT\CAMT, used by its MT940-to-XML fallback), but its
 * parser hands every booking day the *same* opening balance - the one balance of the report -
 * instead of carrying it forward. saveStatements() checks that each statement opens where the
 * previous one closed, so from the second day on the check fails and the whole import is
 * rolled back with "Die Kontoauszüge der Bank sind nicht lückenlos". Even suppressing that,
 * the per-row saldo would restart from the opening balance every day.
 *
 * So the documents are parsed with genkgo/camt instead - the same parser the manual CAMT
 * upload uses - and the daily statements are built here, with the balance carried across days.
 *
 * What this file is: an adapter, not a home for logic. saveStatements() consumes the php-fints
 * statement model (days, each with its own opening balance, holding Transaction objects with an
 * unsigned amount plus a credit/debit mark), while CamtImportParser produces flat rows keyed by
 * konto column with a signed value. Everything here is shape-shifting between those two, apart
 * from the daily balance carry, which is the actual fix.
 *
 * Slated for removal in 4.5.0: that release converges both import paths on the flat-row
 * representation and moves the rewind/anchor, the sync_until cutoff and last_sync into a single
 * importer. At that point the camt path feeds CamtImportParser straight into it, the MT940 path
 * gets a much smaller flattener, and this class goes away. See OP#627, which carries the order
 * of that work - the characterisation tests around saveStatements() come first.
 */
class CamtStatementConverter
{
    public function __construct(private readonly CamtImportParser $parser = new CamtImportParser) {}

    /**
     * @param  string[]  $xmlDocuments  the documents of one HKCAZ response, in the order the
     *                                  bank sent them (a paginated response yields several)
     *
     * @throws CamtStatementException when the documents cannot be turned into a statement
     *                                whose saldo chain is sound
     */
    public function convert(array $xmlDocuments): StatementOfAccount
    {
        $rows = collect();
        $openingBalance = null;

        foreach ($xmlDocuments as $xml) {
            if (trim($xml) === '') {
                continue;
            }
            try {
                $parsed = $this->parser->parseString($xml);
            } catch (Throwable $e) {
                throw new CamtStatementException('camt document could not be parsed', $e->getCode(), previous: $e);
            }
            // First page wins: every document of a paginated response reports its own
            // balances, and only the earliest one opens the range.
            $openingBalance ??= $parsed['openingBalance'];
            $rows = $rows->concat($parsed['rows']);
        }

        // An empty response is a legitimate answer - the bank has nothing in the range. It
        // must not look like a failure here, or every already-up-to-date account would fall
        // back to MT940 and ask for a second TAN for nothing.
        if ($rows->isEmpty()) {
            return new StatementOfAccount;
        }

        if ($openingBalance === null) {
            throw new CamtStatementException('camt document carries no opening balance');
        }

        return StatementOfAccount::fromMT940Array($this->dailyStatements($rows, $openingBalance));
    }

    /**
     * Groups the entries into one statement per booking day, carrying the balance forward, so
     * each day opens exactly where the previous one closed.
     *
     * @param  Collection<int, array<string, string>>  $rows
     * @return array<string, array<string, mixed>> in the shape StatementOfAccount::fromMT940Array() expects
     */
    private function dailyStatements(Collection $rows, string $openingBalance): array
    {
        $statements = [];
        $balance = $openingBalance;

        foreach ($rows->sortBy('date')->groupBy('date') as $date => $entries) {
            $startBalance = $balance;
            $transactions = [];
            foreach ($entries as $entry) {
                $transactions[] = $this->transaction($entry);
                // bcadd, not float arithmetic: the running balance is compared against the
                // stored saldo down to the cent to find the resume point of the import.
                $balance = bcadd($balance, $entry['value'], 2);
            }

            $statements[$date] = [
                'start_balance' => $this->balance($startBalance),
                'end_balance' => $this->balance($balance),
                'transactions' => $transactions,
            ];
        }

        return $statements;
    }

    /**
     * @param  array<string, string>  $entry
     * @return array<string, mixed>
     */
    private function transaction(array $entry): array
    {
        return [
            'booking_date' => $entry['date'],
            'valuta_date' => $entry['valuta'] !== '' ? $entry['valuta'] : $entry['date'],
            // Unsigned magnitude plus a separate mark: that is what the MT940 parser produces
            // and what FintsController::convertToCent() expects on the other side.
            'amount' => abs((float) $entry['value']),
            'credit_debit' => $this->creditDebit($entry['value']),
            'is_storno' => false,
            // CamtImportParser has already dropped everything that is not booked.
            'booked' => true,
            'description' => [
                'booking_code' => '',
                'booking_text' => $entry['type'],
                'description_1' => $entry['zweck'],
                'description_2' => '',
                // SVWZ and EREF are what Transaction::getMainDescription() and
                // getEndToEndID() read, and those two end up in konto.zweck and
                // konto.customer_ref.
                'description' => array_filter([
                    'SVWZ' => $entry['zweck'],
                    'EREF' => $entry['customer_ref'],
                ], static fn (string $value): bool => $value !== ''),
                'bank_code' => $entry['empf_bic'],
                'account_number' => $entry['empf_iban'],
                'name' => $entry['empf_name'],
                // Both are parsed to int by the model. CamtImportParser leaves primanota
                // empty on purpose - the camt account-servicer reference is alphanumeric and
                // has no home in the numeric column.
                'primanoten_nr' => $entry['primanota'],
                'text_key_addition' => $entry['comment'],
            ],
        ];
    }

    /**
     * @return array{amount: float, credit_debit: string}
     */
    private function balance(string $amount): array
    {
        return ['amount' => abs((float) $amount), 'credit_debit' => $this->creditDebit($amount)];
    }

    private function creditDebit(string $amount): string
    {
        return bccomp($amount, '0', 2) < 0 ? MT940::CD_DEBIT : MT940::CD_CREDIT;
    }
}
