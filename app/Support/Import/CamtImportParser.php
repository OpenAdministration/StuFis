<?php

declare(strict_types=1);

namespace App\Support\Import;

use Genkgo\Camt\Config;
use Genkgo\Camt\DTO\Balance;
use Genkgo\Camt\DTO\Creditor;
use Genkgo\Camt\DTO\Debtor;
use Genkgo\Camt\DTO\Entry;
use Genkgo\Camt\DTO\EntryTransactionDetail;
use Genkgo\Camt\DTO\IbanAccount;
use Genkgo\Camt\DTO\RecordWithBalances;
use Genkgo\Camt\DTO\RelatedPartyTypeInterface;
use Genkgo\Camt\DTO\UltimateCreditor;
use Genkgo\Camt\DTO\UltimateDebtor;
use Genkgo\Camt\Reader;
use Illuminate\Support\Collection;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Money;

/**
 * Parses an uploaded ISO 20022 CAMT statement (camt.052/053/054) into transaction rows
 * shaped like the CSV importer output, so the manual-import component can feed them through
 * the exact same save() pipeline.
 *
 * Each row is keyed by BankTransaction column name (identity mapping); the component pairs
 * it with a name-based mapping so save() can read $row[$mapping[$col]] === $row[$col].
 */
class CamtImportParser
{
    private DecimalMoneyFormatter $moneyFormatter;

    public function __construct()
    {
        $this->moneyFormatter = new DecimalMoneyFormatter(new ISOCurrencies);
    }

    /**
     * @return array{rows: Collection<int, array<string, string>>, accountIban: string|null, openingBalance: string|null, closingBalance: string|null}
     */
    public function parse(string $path): array
    {
        $message = (new Reader(Config::getDefault()))->readFile($path);

        $rows = collect();
        $accountIban = null;
        $openingBalance = null;
        $closingBalance = null;

        foreach ($message->getRecords() as $record) {
            // The IBAN of the account this statement belongs to (for the sanity check against
            // the selected BankAccount). Only IBAN accounts are used — a proprietary/non-IBAN
            // identifier must not be compared against the account's IBAN (false mismatch).
            $account = $record->getAccount();
            if ($accountIban === null && $account instanceof IbanAccount) {
                $accountIban = $account->getIdentification() ?: null;
            }

            foreach ($record->getEntries() as $entry) {
                // Only import booked entries. camt.052 (intraday report) can carry provisional
                // PDNG/INFO entries that are not yet final; camt.053 entries are always BOOK.
                if (! $this->isBooked($entry)) {
                    continue;
                }
                $rows->push($this->mapEntry($entry));
            }

            if ($record instanceof RecordWithBalances) {
                // OPBD on a camt.053 statement; PRCD ("previously closed booked") on a camt.052
                // intraday report — genkgo maps both to TYPE_OPENING.
                $openingBalance = $this->balance($record, Balance::TYPE_OPENING) ?? $openingBalance;
                // CLBD on a statement; a camt.052 report usually only carries the running interim
                // booked balance (ITBD -> TYPE_INTERIM), so fall back to that for the closing figure.
                $closingBalance = $this->balance($record, Balance::TYPE_CLOSING)
                    ?? $this->balance($record, Balance::TYPE_INTERIM)
                    ?? $closingBalance;
            }
        }

        // CAMT statement order is not guaranteed; the DB and saldo calculation expect oldest first.
        $rows = $rows->sortBy('date')->values();

        return ['rows' => $rows, 'accountIban' => $accountIban, 'openingBalance' => $openingBalance, 'closingBalance' => $closingBalance];
    }

    /**
     * Cheap content sniff: a CAMT upload is XML carrying an ISO 20022 camt.05x namespace.
     */
    public function isCamt(string $path): bool
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        $head = (string) fread($handle, 4096);
        fclose($handle);

        return str_contains($head, '<?xml') && preg_match('/xsd:camt\.05\d/', $head) === 1;
    }

    /**
     * Treat an entry as booked unless it explicitly reports a non-BOOK status. Some banks omit
     * the status on a booked statement, so a null/empty status is accepted.
     */
    private function isBooked(Entry $entry): bool
    {
        $status = $entry->getStatus();

        return $status === null || $status === '' || strtoupper($status) === 'BOOK';
    }

    /**
     * @return array<string, string>
     */
    private function mapEntry(Entry $entry): array
    {
        $detail = $entry->getTransactionDetail();
        $bookingDate = $entry->getBookingDate() ?? $entry->getValueDate();
        $valueDate = $entry->getValueDate() ?? $entry->getBookingDate();
        $isCredit = $entry->getCreditDebitIndicator() !== 'DBIT';
        $party = $this->counterparty($detail, $isCredit);

        $row = [
            'date' => $bookingDate?->format('Y-m-d') ?? '',
            'valuta' => $valueDate?->format('Y-m-d') ?? '',
            'type' => $this->bookingCode($entry),
            // Entry::getAmount() is already signed by genkgo (DBIT -> negative).
            'value' => $this->moneyFormatter->format($entry->getAmount()),
            'empf_name' => $party?->getName() ?? '',
            'empf_iban' => $party !== null ? $this->ibanOf($detail, $isCredit) : '',
            'empf_bic' => $this->bicOf($detail),
            // primanota is a numeric column in the DB; the CAMT account-servicer reference is
            // alphanumeric, so it has no home here and is intentionally left empty.
            'primanota' => '',
            'zweck' => $detail?->getRemittanceInformation()?->getMessage() ?? '',
            'customer_ref' => $this->endToEndId($entry, $detail),
            'saldo' => '', // left empty: computed row-by-row in the component's save()
            'comment' => '',
        ];

        return $row;
    }

    private function counterparty(?EntryTransactionDetail $detail, bool $isCredit): ?RelatedPartyTypeInterface
    {
        if ($detail === null) {
            return null;
        }

        // For money coming in (credit) the counterparty is the debtor (payer); for money
        // going out (debit) it is the creditor (payee).
        $primary = $isCredit ? Debtor::class : Creditor::class;
        $ultimate = $isCredit ? UltimateDebtor::class : UltimateCreditor::class;

        $types = collect($detail->getRelatedParties())
            ->map(fn ($related) => $related->getRelatedPartyType());

        return $types->first(fn ($type) => $type instanceof $primary)
            ?? $types->first(fn ($type) => $type instanceof $ultimate)
            ?? $types->first();
    }

    private function ibanOf(?EntryTransactionDetail $detail, bool $isCredit): string
    {
        if ($detail === null) {
            return '';
        }

        $primary = $isCredit ? Debtor::class : Creditor::class;

        $account = collect($detail->getRelatedParties())
            ->first(fn ($related) => $related->getRelatedPartyType() instanceof $primary)
            ?->getAccount()
            ?? collect($detail->getRelatedParties())->first()?->getAccount();

        return $account?->getIdentification() ?? '';
    }

    private function bicOf(?EntryTransactionDetail $detail): string
    {
        $agent = $detail?->getRelatedAgent();

        return $agent !== null ? $agent->getRelatedAgentType()->getBIC() : '';
    }

    private function bookingCode(Entry $entry): string
    {
        $code = $entry->getBankTransactionCode();
        if ($code === null) {
            return '';
        }

        return $code->getProprietary()?->getCode()
            ?? $code->getDomain()?->getCode()
            ?? '';
    }

    private function endToEndId(Entry $entry, ?EntryTransactionDetail $detail): string
    {
        $ref = $detail?->getReference()?->getEndToEndId() ?? $entry->getReference() ?? '';

        return in_array($ref, ['NOTPROVIDED', 'NONE', null], true) ? '' : $ref;
    }

    private function balance(RecordWithBalances $record, string $type): ?string
    {
        $balance = collect($record->getBalances())
            ->first(fn (Balance $balance) => $balance->getType() === $type);

        return $balance instanceof Balance ? $this->moneyFormatter->format($balance->getAmount()) : null;
    }
}
