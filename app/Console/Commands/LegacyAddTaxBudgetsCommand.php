<?php

namespace App\Console\Commands;

use App\Models\Legacy\LegacyBudgetGroup;
use App\Models\Legacy\LegacyBudgetItem;
use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\TaxBudget;
use Illuminate\Console\Command;

class LegacyAddTaxBudgetsCommand extends Command
{
    protected $signature = 'legacy:add-tax-budgets {plan_id : The LegacyBudgetPlan ID}';

    protected $description = 'Add tax budgets (group and two budget items) to a given LegacyBudgetPlan';

    public function handle(): int
    {
        return \DB::transaction(function (): int {
            $planId = $this->argument('plan_id');

            // Validate that the budget plan exists
            $plan = LegacyBudgetPlan::find($planId);
            if ($plan === null) {
                $this->error("LegacyBudgetPlan with ID {$planId} does not exist.");
                return self::FAILURE;
            }

            $this->info("Adding tax budgets to LegacyBudgetPlan ID: {$planId}");

            // Create a new budget group for taxes
            $taxGroup = new LegacyBudgetGroup([
                'hhp_id' => $planId,
                'gruppen_name' => 'Umsatzsteuer',
                'type' => 1, // 1 for Ausgabe (expenses)
            ]);
            $taxGroup->save();

            $this->info("Created budget group: {$taxGroup->gruppen_name} (ID: {$taxGroup->id})");

            // Create two budget items for the tax group
            $budgetItem1 = new LegacyBudgetItem([
                'hhpgruppen_id' => $taxGroup->id,
                'titel_name' => '7% Umsatzsteuer',
                'titel_nr' => 'A.99.1',
                'value' => 0,
            ]);
            $budgetItem1->save();

            TaxBudget::create([
                'hhp_id' => $planId,
                'titel_id' => $budgetItem1->id,
                'tax_percent' => 7,
            ]);

            $this->info("Created budget item: {$budgetItem1->titel_name} (ID: {$budgetItem1->id})");

            $budgetItem2 = new LegacyBudgetItem([
                'hhpgruppen_id' => $taxGroup->id,
                'titel_name' => '19% Umsatzsteuer',
                'titel_nr' => 'A.99.2',
                'value' => 0,
            ]);
            $budgetItem2->save();

            TaxBudget::create([
                'hhp_id' => $planId,
                'titel_id' => $budgetItem2->id,
                'tax_percent' => 19,
            ]);

            $this->info("Created budget item: {$budgetItem2->titel_name} (ID: {$budgetItem2->id})");

            $this->info('Successfully added tax budgets!');

            return self::SUCCESS;
        });
    }
}
