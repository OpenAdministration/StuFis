<?php

namespace App\Exports\Datev;

/**
 * Selects which date a DATEV export filters its period on.
 *
 * Backed by a string so it can be wired straight to a form/select later.
 */
enum DatevExportDateField: string
{
    /** Expense creation date (auslagen.created) — filters on the queried Expense itself. */
    case ExpenseCreatedDate = 'expense_created_date';

    /** Earliest receipt date (min belege.datum) across an expense's receipts. */
    case EarliestReceiptDate = 'earliest_receipt_date';

    /** Booking entry date (booking.timestamp) of an expense's bookings. */
    case BookingDate = 'booking_date';

    /** Earliest payment date (min konto.valuta) reached through an expense's bookings. */
    case EarliestPaymentDate = 'earliest_payment_date';
}
