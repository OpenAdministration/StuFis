<?php

namespace App\Exports\Datev;

use Ameax\Datev\DataObjects\DatevAccountLedgerData;
use Ameax\Datev\DataObjects\DatevDocumentData;
use App\Models\Legacy\BankTransaction;
use App\Models\Legacy\Booking;
use App\Models\Legacy\Expense;
use App\Models\Legacy\FileInfo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatevExport
{
    public function __construct(
        private int $hhpId,
        private Carbon $dateRangeStart,
        private Carbon $dateRangeEnd,
        private bool $exportPdfs = false,
        private DatevExportDateField $dateField = DatevExportDateField::BookingDate,
    ) {}

    /**
     * Expenses that have at least one booking in the selected budget plan, narrowed
     * to the chosen export period. Returns Expenses (not Bookings) so the export can
     * walk the full receipt/post/booking graph per expense easily
     */
    public function query(): Builder
    {
        $query = Expense::query()
            ->whereHas(
                'receipts.posts.bookings.budgetItem.budgetGroup',
                fn (Builder $query) => $query->where('hhp_id', $this->hhpId),
            );

        $start = $this->dateRangeStart->copy()->startOfDay();
        $end = $this->dateRangeEnd->copy()->endOfDay();

        // Selects bookings whose payment (the konto row joined on the composite key
        // (zahlung_id, zahlung_type) = (konto.id, konto.konto_id)) has a valuta date
        // matching $operator/$date. Raw whereExists because that composite relation
        // can't be traversed via whereHas. Unpaid bookings have no konto match.
        $bookingPaidOn = fn (string $operator, Carbon $date) => fn (Builder $query) => $query
            ->whereExists(fn ($sub) => $sub->from('konto')
                ->whereColumn('konto.id', 'booking.zahlung_id')
                ->whereColumn('konto.konto_id', 'booking.zahlung_type')
                ->whereDate('konto.valuta', $operator, $date));

        return match ($this->dateField) {
            DatevExportDateField::ExpenseCreatedDate => $query
                ->whereBetween('created', [$start, $end]),

            DatevExportDateField::BookingDate => $query
                ->whereHas(
                    'receipts.posts.bookings',
                    fn (Builder $query) => $query->whereBetween('timestamp', [$start, $end]),
                ),

            // The earliest receipt date lies in [start, end] iff the expense has a receipt
            // on/before end (min <= end) and none before start (min >= start).
            DatevExportDateField::EarliestReceiptDate => $query
                ->whereHas('receipts', fn (Builder $query) => $query->whereDate('datum', '<=', $end))
                ->whereDoesntHave('receipts', fn (Builder $query) => $query->whereDate('datum', '<', $start)),

            // The earliest payment date lies in [start, end] iff some booking is paid
            // on/before end (min <= end) and none is paid before start (min >= start).
            DatevExportDateField::EarliestPaymentDate => $query
                ->whereHas('receipts.posts.bookings', $bookingPaidOn('<=', $end))
                ->whereDoesntHave('receipts.posts.bookings', $bookingPaidOn('<', $start)),
        };
    }

    public function count(): int
    {
        return $this->query()->count();
    }

    /**
     * Per-expense rows mirroring exactly what export() would write: same query, same
     * Belegdatum and payment lookup. Expenses whose ledger has no bookings are skipped,
     * just as export() skips them.
     *
     * @return Collection<int, DatevExportPreviewRow>
     */
    public function preview(): Collection
    {
        $expenses = $this->query()
            ->with(['receipts.posts.bookings'])
            ->get();

        $payments = $this->paymentLookup($expenses);

        return $expenses
            ->map(function (Expense $expense) use ($payments) {
                $bookings = $this->ledgerBookings($expense);
                if ($bookings->isEmpty()) {
                    return null;
                }

                return new DatevExportPreviewRow(
                    expenseId: $expense->id,
                    name: $expense->zahlung_name,
                    projectId: $expense->projekt_id,
                    belegDate: $this->belegDate($expense),
                    bookingCount: $bookings->count(),
                    paidAt: $this->earliestPayment($bookings, $payments),
                );
            })
            ->filter()
            ->values();
    }

    /**
     * Build the DATEV export and persist it to the local disk. Returns the disk-relative
     * path to the generated zip, or false when nothing could be written.
     */
    public function export(): string|false
    {
        $expenses = $this->query()
            ->with(['receipts.posts.bookings.budgetItem.budgetGroup'])
            ->get();

        $payments = $this->paymentLookup($expenses);
        $document = new DatevDocumentData;

        foreach ($expenses as $expense) {
            $bookings = $this->ledgerBookings($expense);
            if ($bookings->isEmpty()) {
                continue;
            }

            $isReceivable = $expense->totalIn()->greaterThan($expense->totalOut());
            $belegDate = $this->belegDate($expense);
            $filePaths = $this->exportPdfs ? $this->receiptFilePaths($expense) : [];

            if ($isReceivable) {
                // receivable
                $ledger = new DatevAccountLedgerData(
                    consolidatedDate: $belegDate,
                    consolidatedDeliveryDate: $belegDate,
                    consolidatedInvoiceId: DatevExportPreviewRow::invoiceIdFor($expense->id),
                    customerName: $expense->zahlung_name,
                    paidAt: $this->earliestPayment($bookings, $payments),
                    iban: $this->ledgerIban($expense),
                );
                foreach ($bookings as $booking) {
                    $ledger->addAccountsReceivableLedger(
                        amount: $this->amount($booking, $expense, $isReceivable),
                        date: $belegDate,
                        bookingText: mb_substr($booking->comment ?? '', 0, 60) ?: null,
                        // tax (in percent)
                    );
                }
                $document->buildAccountsReceivableLedger($ledger, $filePaths);
            } else {
                // payable
                $ledger = new DatevAccountLedgerData(
                    consolidatedDate: $belegDate,
                    consolidatedDeliveryDate: $belegDate,
                    consolidatedInvoiceId: DatevExportPreviewRow::invoiceIdFor($expense->id),
                    supplierName: $expense->zahlung_name,
                    paidAt: $this->earliestPayment($bookings, $payments),
                    iban: $this->ledgerIban($expense),
                );
                foreach ($bookings as $booking) {
                    $ledger->addAccountsPayableLedger(
                        amount: $this->amount($booking, $expense, $isReceivable),
                        date: $belegDate,
                        bookingText: mb_substr($booking->comment ?? '', 0, 60) ?: null,
                        // tax (in percent)
                    );
                }
                $document->buildAccountsPayableLedger($ledger, $filePaths);
            }
        }

        // generateZip() writes to a TemporaryDirectory that DatevDocumentData's Zip
        // deletes the moment $document is garbage-collected (i.e. when this method
        // returns). Stream it onto the local disk so the download route can serve it
        // afterwards. writeStream copies at the OS level, so the (PDF-laden) zip is
        // never held in memory the way reading it into a string would.
        $path = 'datev-exports/'.Str::uuid().'.zip';
        $source = fopen($document->generateZip(), 'rb');

        $stored = Storage::disk('local')->writeStream($path, $source);

        if (is_resource($source)) {
            fclose($source);
        }

        return $stored ? $path : false;
    }

    /**
     * The expense IBAN for the DATEV ledger, or null when absent/invalid. The DATEV XSD
     * rejects an empty <iban> element (pattern [A-Z]{2}\d\d…), and the library only drops
     * null values, never empty strings — so an unvalidated '' would fail validation.
     */
    private function ledgerIban(Expense $expense): ?string
    {
        $iban = str_replace(' ', '', (string) $expense->zahlung_iban);

        return verify_iban($iban) ? $iban : null;
    }

    private function amount(Booking $booking, Expense $expense, bool $isReceivable): float
    {
        $invers = ($isReceivable === false && $booking->budgetItem->budgetGroup->type === 0) ||
        ($isReceivable === true && $booking->budgetItem->budgetGroup->type === 1);

        return $invers ? -$booking->value : $booking->value;
    }

    /** Bookings of an expense, flattened across all its receipts and posts. */
    private function ledgerBookings(Expense $expense): Collection
    {
        return $expense->receipts
            ->flatMap->posts
            ->flatMap->bookings
            ->values();
    }

    /** Belegdatum: the latest receipt date, falling back to the expense creation date. */
    private function belegDate(Expense $expense): Carbon
    {
        // belege.datum is mandatory for new/edited expenses; for old expenses that predate
        // that rule it may be unset, so fall back to the creation date. auslagen.created is
        // a "datetime;user;name" audit string — take the datetime prefix.
        $latestReceiptDate = $expense->receipts->pluck('datum')->filter()->max();

        return Carbon::parse($latestReceiptDate ?? explode(';', (string) $expense->created)[0]);
    }

    /** Composite-keyed (id-konto_id) bank transactions for every booking's payment. */
    private function paymentLookup(Collection $expenses): Collection
    {
        $zahlungIds = $expenses
            ->flatMap(fn (Expense $expense) => $this->ledgerBookings($expense))
            ->pluck('zahlung_id')
            ->filter()
            ->unique();

        return BankTransaction::whereIn('id', $zahlungIds)->get()
            ->keyBy(fn (BankTransaction $transaction) => $transaction->id.'-'.$transaction->konto_id);
    }

    /** Earliest payment value-date across the given bookings (informational paidAt). */
    private function earliestPayment(Collection $bookings, Collection $payments): ?Carbon
    {
        return $bookings->map(
            fn (Booking $booking) => $payments->get($booking->zahlung_id.'-'.$booking->zahlung_type)?->valuta)
            ->filter()
            ->min();
    }

    /** Absolute disk paths of every receipt PDF attached to the expense. */
    private function receiptFilePaths(Expense $expense): array
    {
        $paths = [];
        foreach ($expense->receipts as $receipt) {
            if (! $receipt->file_id) {
                continue;
            }
            $diskPath = FileInfo::find($receipt->file_id)?->fileData?->diskpath;
            if ($diskPath && Storage::exists($diskPath)) {
                $paths[] = Storage::path($diskPath);
            }
        }

        return $paths;
    }
}
