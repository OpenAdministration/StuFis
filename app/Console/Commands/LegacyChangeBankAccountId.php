<?php

namespace App\Console\Commands;

use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\Legacy\Booking;
use App\Models\Legacy\BookingInstruction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyChangeBankAccountId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:change-bank-account-id {old-id} {new-id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shifts konto_ids from one to another';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return DB::transaction(function (): int {
            $oldId = $this->argument('old-id');
            $newId = $this->argument('new-id');
            $account = BankAccount::where('id', '=', $oldId);

            if ($account->exists()) {
                $this->warn("The bank account id $oldId does exist");
                $this->comment($account->first());
            } else {
                $this->info("The bank account id $oldId does NOT exist");
            }

            $t_count = BankTransaction::where('konto_id', $oldId)->count();
            $b_count = Booking::where('zahlung_type', $oldId)->count();
            $bi_change = BookingInstruction::where('zahlung_type', $oldId)->count();

            $this->table(['Table', 'found Entries'], [
                ['konto', $t_count],
                ['booking', $b_count],
                ['booking_instruction', $bi_change],

            ]);

            $continue = $this->ask('Do you want to continue? y/N', 'n');
            if ($continue !== 'y') {
                return self::SUCCESS;
            }

            Schema::disableForeignKeyConstraints();

            // update the other foreign keys to the new id
            $t_change = BankTransaction::where('konto_id', $oldId)->update(['konto_id' => $newId]);
            $b_change = Booking::where('zahlung_type', $oldId)->update(['zahlung_type' => $newId]);
            $bi_change = BookingInstruction::where('zahlung_type', $oldId)->update(['zahlung_type' => $newId]);

            $this->table(['Table', 'changed Entries'], [
                ['konto', $t_change],
                ['booking', $b_change],
                ['booking_instruction', $bi_change],
            ]);

            Schema::enableForeignKeyConstraints();

            return self::SUCCESS;
        });
    }
}
