<?php

namespace App\Console\Commands;

use App\Models\Legacy\Booking;
use App\Models\Legacy\LegacyBudgetItem;
use App\Models\Legacy\ProjectPost;
use Illuminate\Console\Command;

class LegacyBudgetItemShift extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:budget-id-shift
        {old_id : The old Budget ID}
        {new_id : The new Budget ID}
        {--non-interactive : if given there wont be asked for confirmations }
        {--bypass-validation : dont check if the titels exist }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shifts one Budget Item id to another in project and booking';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return \DB::transaction(function (): int {
            $old_id = $this->argument('old_id');
            $new_id = $this->argument('new_id');

            if (! $this->option('bypass-validation')) {
                $old = LegacyBudgetItem::find($old_id);
                $new = LegacyBudgetItem::find($new_id);
                if ($old === null || $new === null) {
                    $this->fail('One of the Budgets does not exist.');
                }
            }

            $projectPosts = ProjectPost::where('titel_id', $old_id);
            $this->info($projectPosts->count().' ProjectPosts will be shifted');
            $bookings = Booking::where('titel_id', $old_id);
            $this->info($bookings->count().' Bookings will be shifted');

            if (! $this->option('non-interactive')) {
                if (! $this->confirm('Continue?')) {
                    return self::FAILURE;
                }
            }

            $projectPosts->update(['titel_id' => $new_id]);
            $bookings->update(['titel_id' => $new_id]);

            return self::SUCCESS;
        });
    }
}
