<?php

namespace Tests\Pest\TaxBudget;

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\TaxBudget;
use App\States\BudgetPlan\Draft;

beforeEach(function (): void {
    $this->plan = BudgetPlan::create(['state' => Draft::class]);
});

function taxTitleCount(int $planId): int
{
    return BudgetItem::where('budget_plan_id', $planId)->where('is_group', false)->count();
}

it('adds the Umsatzsteuer group and two tax titles', function (): void {
    TaxBudget::addToPlan($this->plan->id);

    expect(BudgetItem::where('budget_plan_id', $this->plan->id)->where('is_group', true)->count())->toBe(1)
        ->and(taxTitleCount($this->plan->id))->toBe(2)
        ->and(TaxBudget::where('plan_id', $this->plan->id)->count())->toBe(2);
});

it('does not add duplicates when called repeatedly', function (): void {
    TaxBudget::addToPlan($this->plan->id);
    TaxBudget::addToPlan($this->plan->id);
    TaxBudget::addToPlan($this->plan->id);

    expect(BudgetItem::where('budget_plan_id', $this->plan->id)->where('is_group', true)->count())->toBe(1)
        ->and(taxTitleCount($this->plan->id))->toBe(2)
        ->and(TaxBudget::where('plan_id', $this->plan->id)->count())->toBe(2);
});
