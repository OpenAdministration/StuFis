<?php

declare(strict_types=1);

namespace App\Support\Import;

use Genkgo\Camt\Config;
use Genkgo\Camt\DTO\Balance;
use Genkgo\Camt\DTO\BankTransactionCode;
use Genkgo\Camt\DTO\Creditor;
use Genkgo\Camt\DTO\CreditorAgent;
use Genkgo\Camt\DTO\Debtor;
use Genkgo\Camt\DTO\DebtorAgent;
use Genkgo\Camt\DTO\Entry;
use Genkgo\Camt\DTO\EntryTransactionDetail;
use Genkgo\Camt\DTO\IbanAccount;
use Genkgo\Camt\DTO\Message;
use Genkgo\Camt\DTO\RecordWithBalances;
use Genkgo\Camt\DTO\RelatedAgent;
use Genkgo\Camt\DTO\RelatedPartyTypeInterface;
use Genkgo\Camt\DTO\UltimateCreditor;
use Genkgo\Camt\DTO\UltimateDebtor;
use Genkgo\Camt\Reader;
use Illuminate\Support\Collection;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;

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
    private readonly DecimalMoneyFormatter $moneyFormatter;

    public function __construct()
    {
        $this->moneyFormatter = new DecimalMoneyFormatter(new ISOCurrencies);
    }

    /**
     * @return array{rows: Collection<int, array<string, string>>, accountIban: string|null, openingBalance: string|null, closingBalance: string|null}
     */
    public function parse(string $path): array
    {
        return $this->fromMessage(new Reader(Config::getDefault())->readFile($path));
    }

    /**
     * Same as parse(), for a document that is already in memory: the FinTS HKCAZ response
     * carries the camt XML as a string, not as an uploaded file.
     *
     * @return array{rows: Collection<int, array<string, string>>, accountIban: string|null, openingBalance: string|null, closingBalance: string|null}
     */
    public function parseString(string $xml): array
    {
        return $this->fromMessage(new Reader(Config::getDefault())->readString($xml));
    }

    /**
     * @return array{rows: Collection<int, array<string, string>>, accountIban: string|null, openingBalance: string|null, closingBalance: string|null}
     */
    private function fromMessage(Message $message): array
    {
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
     * A batched booking - one Ntry carrying several TxDtls, e.g. a bulk transfer of
     * reimbursements - is deliberately still mapped to a single row from its first detail, so
     * the entry's full amount sits on the first payee and the other references are not
     * imported. Splitting it into a row per payment was tried and reverted: it changes what a
     * statement line means, and it breaks the FinTS resume point for accounts that already hold
     * a batch as one row. See OP#655 for the open question.
     *
     * @return array<string, string>
     */
    private function mapEntry(Entry $entry): array
    {
        $detail = $entry->getTransactionDetail();
        $bookingDate = $entry->getBookingDate() ?? $entry->getValueDate();
        $valueDate = $entry->getValueDate() ?? $entry->getBookingDate();
        $isCredit = $entry->getCreditDebitIndicator() !== 'DBIT';
        $party = $this->counterparty($detail, $isCredit);

        return [
            'date' => $bookingDate?->format('Y-m-d') ?? '',
            'valuta' => $valueDate?->format('Y-m-d') ?? '',
            'type' => $this->bookingCode($entry),
            // Entry::getAmount() is already signed by genkgo (DBIT -> negative).
            'value' => $this->moneyFormatter->format($entry->getAmount()),
            'empf_name' => $party?->getName() ?? '',
            'empf_iban' => $party instanceof RelatedPartyTypeInterface ? $this->ibanOf($detail, $isCredit) : '',
            'empf_bic' => $this->bicOf($detail, $isCredit),
            // primanota is a numeric column in the DB; the CAMT account-servicer reference is
            // alphanumeric, so it has no home here and is intentionally left empty.
            'primanota' => '',
            'zweck' => $detail?->getRemittanceInformation()?->getMessage() ?? '',
            'customer_ref' => $this->endToEndId($entry, $detail),
            'saldo' => '', // left empty: computed row-by-row in the component's save()
            'comment' => '',
        ];
    }

    private function counterparty(?EntryTransactionDetail $detail, bool $isCredit): ?RelatedPartyTypeInterface
    {
        if (! $detail instanceof EntryTransactionDetail) {
            return null;
        }

        // For money coming in (credit) the counterparty is the debtor (payer); for money
        // going out (debit) it is the creditor (payee).
        $primary = $isCredit ? Debtor::class : Creditor::class;
        $ultimate = $isCredit ? UltimateDebtor::class : UltimateCreditor::class;

        $types = collect($detail->getRelatedParties())
            ->map(fn ($related) => $related->getRelatedPartyType());

        // Same side only. Falling back to any related party meant that an entry naming only
        // the account holder - a bank fee, where there is no counterparty at all - was stored
        // with our own organisation as the recipient of its own fee. No name is honest; the
        // Verwendungszweck still says what the booking was.
        return $types->first(fn ($type) => $type instanceof $primary)
            ?? $types->first(fn ($type) => $type instanceof $ultimate);
    }

    private function ibanOf(?EntryTransactionDetail $detail, bool $isCredit): string
    {
        if (! $detail instanceof EntryTransactionDetail) {
            return '';
        }

        $primary = $isCredit ? Debtor::class : Creditor::class;
        $ultimate = $isCredit ? UltimateDebtor::class : UltimateCreditor::class;

        $parties = collect($detail->getRelatedParties());
        // Same side only. The previous fallback took the account of *any* related party, which
        // on a transfer whose counterparty carries no account meant storing our own IBAN as the
        // recipient's.
        $account = $parties->first(fn ($related) => $related->getRelatedPartyType() instanceof $primary)?->getAccount()
            ?? $parties->first(fn ($related) => $related->getRelatedPartyType() instanceof $ultimate)?->getAccount();

        // Only an actual IBAN belongs in empf_iban. ISO 20022 identifies a cash account by a
        // choice of IBAN *or* Othr/GenericAccountIdentification, and German banks use the latter
        // (a card or terminal number) for cash withdrawals, card payments and fees, where there
        // is no counterparty IBAN at all. Those identifiers used to be stored as if they were
        // IBANs, and IbanColumnRule then rejected the entire upload with "Enthält ungültige
        // IBANs" - one ATM withdrawal was enough to block a whole statement.
        return $account instanceof IbanAccount ? $account->getIdentification() : '';
    }

    private function bicOf(?EntryTransactionDetail $detail, bool $isCredit): string
    {
        if (! $detail instanceof EntryTransactionDetail) {
            return '';
        }

        // getRelatedAgent() returns whichever agent genkgo stored first, and its decoder always
        // appends the creditor agent before the debtor one, whatever the document order. That is
        // the counterparty only for outgoing money; on an incoming payment it is our own bank,
        // which is what used to be stored as the payer's BIC.
        $wanted = $isCredit ? DebtorAgent::class : CreditorAgent::class;

        $agent = collect($detail->getRelatedAgents())
            ->first(fn (RelatedAgent $related): bool => $related->getRelatedAgentType() instanceof $wanted);

        return $agent instanceof RelatedAgent ? $agent->getRelatedAgentType()->getBIC() : '';
    }

    private function bookingCode(Entry $entry): string
    {
        $code = $entry->getBankTransactionCode();
        if (! $code instanceof BankTransactionCode) {
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
