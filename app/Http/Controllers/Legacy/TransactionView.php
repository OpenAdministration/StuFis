<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;

class TransactionView extends Controller
{
    public function view(int $account_id, int $transaction_id)
    {
        $transaction = BankTransaction::where(['id' => $transaction_id, 'konto_id' => $account_id])->firstOrFail();
        $account = BankAccount::where(['id' => $account_id])->firstOrFail();
        $bookings = $transaction->bookings()->with('expensesReceiptPost.expensesReceipt')->get();
        $receiptsFromBookings = $bookings->keyBy('id')->map(fn ($booking) => $booking->expensesReceiptPost->expensesReceipt);

        return view('legacy.transaction.view', [
            'transaction' => $transaction,
            'account' => $account,
            'bookings' => $bookings,
            'receiptsFromBookings' => $receiptsFromBookings,
        ]);
    }
}
