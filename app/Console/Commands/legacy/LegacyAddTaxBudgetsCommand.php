<?php

namespace App\Console\Commands\legacy;

use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\TaxBudget;
use Illuminate\Console\Command;

class LegacyAddTaxBudgetsCommand extends Command
{
    protected $signature = 'legacy:add-tax-budgets {plan_id : The LegacyBudgetPlan ID}';

    protected $description = 'Add tax budgets (group and two budget items) to a given LegacyBudgetPlan';

    public function handle(): int
    {
        $planId = (int) $this->argument('plan_id');

        // Validate that the budget plan exists
        if (LegacyBudgetPlan::find($planId) === null) {
            $this->error("LegacyBudgetPlan with ID {$planId} does not exist.");

            return self::FAILURE;
        }

        $this->info("Adding tax budgets to LegacyBudgetPlan ID: {$planId}");

        TaxBudget::addToPlan($planId);

        $this->info('Successfully added tax budgets!');

        return self::SUCCESS;
    }
}
