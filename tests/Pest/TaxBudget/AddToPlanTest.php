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

it('adds the Umsatzsteuer group and a title per rate, returning the count added', function (): void {
    $added = TaxBudget::addToPlan($this->plan->id);

    expect($added)->toBe(2)
        ->and(BudgetItem::where('budget_plan_id', $this->plan->id)->where('is_group', true)->count())->toBe(1)
        ->and(taxTitleCount($this->plan->id))->toBe(2)
        ->and(TaxBudget::where('plan_id', $this->plan->id)->count())->toBe(2);
});

it('is idempotent: a repeated call adds nothing and returns 0', function (): void {
    expect(TaxBudget::addToPlan($this->plan->id))->toBe(2)
        ->and(TaxBudget::addToPlan($this->plan->id))->toBe(0)
        ->and(TaxBudget::addToPlan($this->plan->id))->toBe(0);

    expect(BudgetItem::where('budget_plan_id', $this->plan->id)->where('is_group', true)->count())->toBe(1)
        ->and(taxTitleCount($this->plan->id))->toBe(2)
        ->and(TaxBudget::where('plan_id', $this->plan->id)->count())->toBe(2);
});

it('honours explicit rates and normalises them (dedupe, drop non-positive, sort)', function (): void {
    $added = TaxBudget::addToPlan($this->plan->id, [19, 7, 7, 0]);

    expect($added)->toBe(2)
        ->and(TaxBudget::where('plan_id', $this->plan->id)->orderBy('tax_percent')->pluck('tax_percent')->map(fn ($p) => (int) $p)->all())
        ->toBe([7, 19]);
});
