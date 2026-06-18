<?php

namespace Tests\Pest\TaxBudget;

use App\Models\Legacy\LegacyBudgetGroup;
use App\Models\Legacy\LegacyBudgetItem;
use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\TaxBudget;

beforeEach(function (): void {
    $this->plan = LegacyBudgetPlan::create([
        'von' => now()->startOfYear(),
        'bis' => now()->endOfYear(),
        'state' => 'final',
    ]);
});

function taxTitleCount(int $planId): int
{
    $groupIds = LegacyBudgetGroup::where('hhp_id', $planId)->pluck('id');

    return LegacyBudgetItem::whereIn('hhpgruppen_id', $groupIds)->count();
}

it('adds the Umsatzsteuer group and two tax titles', function (): void {
    TaxBudget::addToPlan($this->plan->id);

    expect(LegacyBudgetGroup::where('hhp_id', $this->plan->id)->count())->toBe(1)
        ->and(taxTitleCount($this->plan->id))->toBe(2)
        ->and(TaxBudget::where('hhp_id', $this->plan->id)->count())->toBe(2);
});

it('does not add duplicates when called repeatedly', function (): void {
    TaxBudget::addToPlan($this->plan->id);
    TaxBudget::addToPlan($this->plan->id);
    TaxBudget::addToPlan($this->plan->id);

    expect(LegacyBudgetGroup::where('hhp_id', $this->plan->id)->count())->toBe(1)
        ->and(taxTitleCount($this->plan->id))->toBe(2)
        ->and(TaxBudget::where('hhp_id', $this->plan->id)->count())->toBe(2);
});
