<?php

namespace App\Console\Commands\legacy;

use App\Models\Legacy\LegacyBudgetItem;
use App\Models\Legacy\LegacyBudgetPlan;
use Illuminate\Console\Command;

/**
 * @deprecated Legacy HHP tooling, slated for deletion. The legacy budget tables are now read-only
 * views over budget_plan/budget_item (migration swap_legacy_budget_tables_for_views), so this
 * command can no longer write them. Manage budget plans in the new budget plan module.
 */
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
    protected $description = '[DEPRECATED] Deletes a given HHP - DANGER - deletes old one without looking out for foreign keys - Potentially corrupting datas ';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->warn('⚠️  DEPRECATED: the legacy budget tables are now views; this command is slated for deletion and will fail against them.');

        $hhp = LegacyBudgetPlan::findOrFail($this->argument('id'));
        $groups = $hhp->budgetGroups();
        $title = LegacyBudgetItem::whereIn('hhpgruppen_id', $groups->pluck('id'));
        $this->info("Found {$title->count()} titles");
        $this->warn('This operation cannot be rolled back and will be done without checking corresponding foreign keys');
        if (! $this->confirm("Are you sure you want to delete HHP from {$hhp->von} to {$hhp->bis} in state {$hhp->state}?")) {
            return;
        }

        \DB::transaction(function () use ($hhp, $groups, $title): void {
            \Schema::disableForeignKeyConstraints();
            $title->delete();
            $groups->delete();
            $hhp->delete();
            \Schema::enableForeignKeyConstraints();
            $this->info('Plan, Groups and Bugets are deleted successfully!');
        });

    }
}
