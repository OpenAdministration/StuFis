<?php

namespace Database\Factories;

use App\Models\BudgetItem;
use App\Models\FiscalYear;
use App\States\BudgetPlan\Published;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization' => 'Students Council',
            'fiscal_year_id' => FiscalYear::factory(),
            'resolution_date' => now()->subDays(60),
            'approval_date' => now()->subDays(30),
            'state' => Published::class,
        ];
    }

    public function populate()
    {
        return $this->has(
            BudgetItem::factory(5)
        );
    }
}
