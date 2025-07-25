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
    protected $signature = 'legacy:refactor-hhp {{--transformation : space seperated list of x->y title ids or x-y }}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes a given HHP - DANGER - deletes old one, upload over ui in the meantime and enter a transformation ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hhp = LegacyBudgetPlan::orderBy('id', 'desc')->firstOrFail();
        $groups = $hhp->budgetGroups();
        $title = LegacyBudgetItem::whereIn('hhpgruppen_id', $groups->pluck('id'))->keyBy('id');
        $this->info("Found {$title->count()} titles");
        $this->warn('This operation cannot be rolled back and will be done without checking corresponding foreign keys');
        if (! $this->confirm("Are you sure you want to delete HHP from {$hhp->von} to {$hhp->bis} in state {$hhp->state}?")) {
            return;
        }

        \DB::transaction(function () use ($hhp, $groups, $title) {
            \Schema::disableForeignKeyConstraints();
            $title->delete();
            $groups->delete();
            $hhp->delete();
            \Schema::enableForeignKeyConstraints();
            $this->info('Plan, Groups and Bugets are deleted successfully!');
        });

    }
}
