<?php

use App\Exports\Datev\DatevExport;
use App\Models\BudgetItem;
use App\Models\Enums\BudgetType;
use App\Models\Legacy\Booking;
use App\Models\Legacy\Expense;
use App\Models\Legacy\ExpenseReceipt;
use Illuminate\Support\Facades\Date;

// DatevExport's accounting logic is private and wrapped around a deep legacy graph.
// These tests pin the pieces that decide the numbers/dates in the export, driving them
// with in-memory model graphs (no DB) via reflection.

function datevInvoke(DatevExport $export, string $method, array $args): mixed
{
    $ref = new ReflectionMethod($export, $method);

    return $ref->invoke($export, ...$args);
}

function datevExport(): DatevExport
{
    return new DatevExport(1, Date::parse('2024-01-01'), Date::parse('2024-12-31'));
}

function datevBooking(int $groupType, float $value): Booking
{
    // legacy group type 0 = income, 1 = expense → new BudgetType on the item itself
    $item = new BudgetItem;
    $item->budget_type = $groupType === 0 ? BudgetType::INCOME : BudgetType::EXPENSE;

    $booking = new Booking;
    $booking->value = $value;
    $booking->setRelation('budgetItem', $item);

    return $booking;
}

it('amount() flips the sign for income groups on payables and expense groups on receivables', function (): void {
    $export = datevExport();
    $expense = new Expense; // unused by amount(), but the signature requires it

    // payable (isReceivable = false): income group (type 0) is inverted, expense group (type 1) kept
    expect(datevInvoke($export, 'amount', [datevBooking(0, 100.0), false]))->toBe(-100.0)
        ->and(datevInvoke($export, 'amount', [datevBooking(1, 100.0),  false]))->toBe(100.0)
        // receivable (isReceivable = true): the inversion mirrors
        ->and(datevInvoke($export, 'amount', [datevBooking(0, 100.0), true]))->toBe(100.0)
        ->and(datevInvoke($export, 'amount', [datevBooking(1, 100.0), true]))->toBe(-100.0);
});

it('belegDate() uses the latest receipt date', function (): void {
    $expense = new Expense;
    $expense->setRelation('receipts', collect([
        new ExpenseReceipt(['datum' => '2024-01-05']),
        new ExpenseReceipt(['datum' => '2024-03-10']),
        new ExpenseReceipt(['datum' => '2024-02-01']),
    ]));

    expect(datevInvoke(datevExport(), 'belegDate', [$expense])->toDateString())->toBe('2024-03-10');
});

it('belegDate() falls back to the created audit-string date when no receipt has a date', function (): void {
    $expense = new Expense;
    $expense->setRelation('receipts', collect());
    $expense->created = '2024-06-01 12:30:00;42;Some User';

    expect(datevInvoke(datevExport(), 'belegDate', [$expense])->toDateString())->toBe('2024-06-01');
});

it('ledgerIban() returns a space-stripped valid IBAN, or null for invalid/empty', function (): void {
    $export = datevExport();

    $valid = new Expense;
    $valid->zahlung_iban = 'DE89 3704 0044 0532 0130 00';
    $invalid = new Expense;
    $invalid->zahlung_iban = 'DE00 1234';
    $empty = new Expense;
    $empty->zahlung_iban = '';

    expect(datevInvoke($export, 'ledgerIban', [$valid]))->toBe('DE89370400440532013000')
        ->and(datevInvoke($export, 'ledgerIban', [$invalid]))->toBeNull()
        ->and(datevInvoke($export, 'ledgerIban', [$empty]))->toBeNull();
});
