<?php

namespace App\Console\Commands;

use App\Models\Legacy\Booking;
use App\Models\Legacy\Expense;
use App\Models\Legacy\ExpenseReceipt;
use Illuminate\Console\Command;

class LegacyUnbookExpensesCommand extends Command
{
    protected $signature = 'legacy:unbook-expenses';

    protected $description = 'Reset state of expenses that are not in the booking list to ok';

    public function handle(): void
    {
        $this->info('Finding booked expenses not in booking list...');

        $bookedRecieptIds = Booking::query()
            ->where('beleg_type', 'belegposten')
            ->distinct()
            ->pluck('beleg_id');

        $unbookedReciepts = ExpenseReceipt::query()
            ->whereNotIn('id', $bookedRecieptIds)
            ->whereHas('expense', function ($query) {
                $query->where('state', 'like', 'booked%');
            })
            ->get();

        $count = $unbookedReciepts->count();

        if ($count === 0) {
            $this->info('No missing booked receipts found.');
            return;
        }

        $this->info("Found {$count} booked reciept(s) which are not in the booking list. Resetting state to instructed...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($unbookedReciepts as $reciept) {
            $expense = $reciept->expense;
            $oldState = $expense->state;
            $expense->state = str_replace('booked', 'instructed', $oldState);
            $expense->save();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully reset {$count} reciept(s).");
    }
}
