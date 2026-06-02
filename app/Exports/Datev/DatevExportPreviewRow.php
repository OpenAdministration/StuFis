<?php

namespace App\Exports\Datev;

use Carbon\Carbon;

/**
 * A single expense row for the DATEV export preview table — one expense = one ledger entry.
 * Built by {@see DatevExport::preview()} using the same query and helpers as the actual
 * export, so the preview reflects what the export will write.
 */
final readonly class DatevExportPreviewRow
{
    public function __construct(
        public int $expenseId,
        public ?string $name,
        public ?int $projectId,
        public ?Carbon $belegDate,
        public int $bookingCount,
        public ?Carbon $paidAt,
    ) {}

    /** Consolidated invoice id as written to the DATEV ledger ("A" + expense id). */
    public function invoiceId(): string
    {
        return self::invoiceIdFor($this->expenseId);
    }

    /** Single home for the invoice-id convention, shared with {@see DatevExport::export()}. */
    public static function invoiceIdFor(int $expenseId): string
    {
        return 'A'.$expenseId;
    }
}
