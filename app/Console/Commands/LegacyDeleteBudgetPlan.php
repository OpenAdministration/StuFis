<?php

namespace App\Console\Commands;

use App\Models\Legacy\LegacyBudgetItem;
use App\Models\Legacy\LegacyBudgetPlan;
use Illuminate\Console\Command;

class LegacyDeleteBudgetPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:delete-hhp {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes a given HHP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hhp = LegacyBudgetPlan::findOrFail($this->argument('id'));
        $groups = $hhp->budgetGroups;
        $title = LegacyBudgetItem::whereIn('hhpgruppen_id', $groups->pluck('id'));
        $this->info("Found {$title->count()} titles");
        if ($this->confirm("Are you sure you want to delete HHP from {$hhp->von} to {$hhp->bis} in state {$hhp->state}?")) {
            return;
        }

        \DB::transaction(function () use ($hhp, $groups, $title) {
            $title->delete();
            $groups->delete();
            $hhp->delete();
        });

    }
}
