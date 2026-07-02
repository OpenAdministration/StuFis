<?php

use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\FiscalYear;
use App\States\BudgetPlan\Draft;
use App\Support\Budget\BudgetPlanCloner;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

function clonePlan(?int $fiscalYearId = null, ?string $organization = null): BudgetPlan
{
    return BudgetPlan::create([
        'state' => Draft::class,
        'fiscal_year_id' => $fiscalYearId,
        'organization' => $organization,
    ]);
}

function addMount(BudgetPlan $parent, BudgetPlan $sub, BudgetType $side, int $position): void
{
    $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => $side, 'position' => $position,
        'referenced_plan_id' => $sub->id,
    ]);
}

function cloner(): BudgetPlanCloner
{
    return app(BudgetPlanCloner::class);
}

it('clones a multi-level forest preserving structure, names, values and positions', function (): void {
    $source = clonePlan();
    $group = $source->budgetItems()->create([
        'is_group' => true, 'budget_type' => BudgetType::INCOME, 'position' => 0, 'short_name' => 'E1',
    ]);
    $group->children()->create([
        'budget_plan_id' => $source->id, 'is_group' => false, 'budget_type' => BudgetType::INCOME,
        'position' => 0, 'short_name' => 'E1.1', 'value' => Money::EUR(200, true),
    ]);
    $group->children()->create([
        'budget_plan_id' => $source->id, 'is_group' => false, 'budget_type' => BudgetType::INCOME,
        'position' => 1, 'short_name' => 'E1.2', 'value' => Money::EUR(300, true),
    ]);

    $target = clonePlan();
    cloner()->cloneInto($source, $target, []);

    expect($target->budgetItems()->count())->toBe(3);

    $root = $target->rootBudgetItems()->first();
    expect($root->is_group)->toBeTrue()->and($root->short_name)->toBe('E1');

    $children = $root->orderedChildren;
    expect($children->pluck('short_name')->all())->toBe(['E1.1', 'E1.2'])
        ->and($children->pluck('position')->all())->toBe([0, 1])
        ->and($target->incomeTotal()->getAmount())->toBe('50000');
});

it('copies a mounted sub-plan and re-points the mount when the choice is copy', function (): void {
    $sub = clonePlan();
    $sub->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::INCOME, 'position' => 0,
        'short_name' => 'E1', 'value' => Money::EUR(500, true),
    ]);
    $source = clonePlan();
    addMount($source, $sub, BudgetType::INCOME, 0);

    $target = clonePlan();
    cloner()->cloneInto($source, $target, [$sub->id => 'copy']);

    $mount = $target->rootBudgetItems()->first();
    expect($mount->isMount())->toBeTrue()
        ->and($mount->referenced_plan_id)->not->toBe($sub->id)
        ->and($target->incomeTotal()->getAmount())->toBe('50000');

    $clonedSub = BudgetPlan::find($mount->referenced_plan_id);
    expect($clonedSub->id)->not->toBe($sub->id)
        ->and($clonedSub->incomeTotal()->getAmount())->toBe('50000');
});

it('drops a mount to an empty group when the choice is drop', function (): void {
    $sub = clonePlan();
    $sub->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::INCOME, 'position' => 0,
        'short_name' => 'E1', 'value' => Money::EUR(500, true),
    ]);
    $source = clonePlan();
    addMount($source, $sub, BudgetType::INCOME, 0);

    $target = clonePlan();
    cloner()->cloneInto($source, $target, [$sub->id => 'drop']);

    $item = $target->rootBudgetItems()->first();
    expect($item->isMount())->toBeFalse()
        ->and($item->is_group)->toBeTrue()
        ->and($item->effectiveValue()->getAmount())->toBe('0')
        ->and($target->incomeTotal()->getAmount())->toBe('0');
});

it('clones a sub-plan only once when it is mounted multiple times', function (): void {
    $sub = clonePlan();
    $sub->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::INCOME, 'position' => 0,
        'short_name' => 'E1', 'value' => Money::EUR(500, true),
    ]);
    $source = clonePlan();
    addMount($source, $sub, BudgetType::INCOME, 0);
    addMount($source, $sub, BudgetType::EXPENSE, 0);

    $target = clonePlan();
    $before = BudgetPlan::count();
    cloner()->cloneInto($source, $target, [$sub->id => 'copy']);

    expect(BudgetPlan::count() - $before)->toBe(1); // one shared sub-plan clone

    $refs = $target->budgetItems()->pluck('referenced_plan_id')->unique()->values();
    expect($refs)->toHaveCount(1)->and($refs->first())->not->toBe($sub->id);
});

it('suffixes the organization only on a same-year collision', function (): void {
    $year = FiscalYear::factory()->create();
    BudgetPlan::create(['state' => Draft::class, 'organization' => 'Acme', 'fiscal_year_id' => $year->id]);

    $suffix = __('budget-plan.edit.copy-suffix');
    expect(BudgetPlan::resolveOrganization('Acme', $year->id))->toBe("Acme ($suffix)")
        ->and(BudgetPlan::resolveOrganization('Acme', null))->toBe('Acme')   // different year
        ->and(BudgetPlan::resolveOrganization('Other', $year->id))->toBe('Other');
});
