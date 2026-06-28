<?php

use App\Models\BudgetPlan;
use App\Models\Enums\BudgetItemKind;
use App\Models\Enums\BudgetType;
use App\Models\FiscalYear;
use App\States\BudgetPlan\Draft;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

function planWithIncome(int $euros): BudgetPlan
{
    $plan = BudgetPlan::create(['state' => Draft::class]);
    $plan->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::INCOME, 'position' => 0,
        'short_name' => 'E1', 'value' => Money::EUR($euros, true),
    ]);

    return $plan;
}

function mountInto(BudgetPlan $parent, BudgetPlan $sub, BudgetType $side): void
{
    $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => $side, 'position' => 0,
        'referenced_plan_id' => $sub->id,
    ]);
}

it('resolves a mount item to the referenced plan side total', function (): void {
    $sub = planWithIncome(500);
    $parent = BudgetPlan::create(['state' => Draft::class]);
    mountInto($parent, $sub, BudgetType::INCOME);

    $mount = $parent->rootBudgetItems()->first();

    expect($mount->kind())->toBe(BudgetItemKind::Mount)
        ->and($mount->isMount())->toBeTrue()
        ->and($mount->effectiveValue()->getAmount())->toBe('50000'); // 500 € = 50000 cents

    // parent income rolls up the sub-plan; only the income side is pulled
    expect($parent->incomeTotal()->getAmount())->toBe('50000')
        ->and($parent->expenseTotal()->getAmount())->toBe('0');
});

it('rolls up live when the sub-plan changes', function (): void {
    $sub = planWithIncome(500);
    $parent = BudgetPlan::create(['state' => Draft::class]);
    mountInto($parent, $sub, BudgetType::INCOME);

    expect($parent->incomeTotal()->getAmount())->toBe('50000');

    $sub->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::INCOME, 'position' => 1,
        'short_name' => 'E2', 'value' => Money::EUR(100, true),
    ]);

    expect($parent->incomeTotal()->getAmount())->toBe('60000'); // now 600 €
});

it('rolls a nested mount up through its group and the plan total', function (): void {
    $sub = planWithIncome(500);
    $parent = BudgetPlan::create(['state' => Draft::class]);

    $group = $parent->budgetItems()->create([
        'is_group' => true, 'budget_type' => BudgetType::INCOME, 'position' => 0, 'short_name' => 'E1',
    ]);
    $group->children()->create([
        'budget_plan_id' => $parent->id, 'is_group' => false, 'budget_type' => BudgetType::INCOME,
        'position' => 0, 'short_name' => 'E1.1', 'value' => Money::EUR(200, true),
    ]);
    $group->children()->create([ // a mount nested inside the group
        'budget_plan_id' => $parent->id, 'is_group' => false, 'budget_type' => BudgetType::INCOME,
        'position' => 1, 'short_name' => 'E1.2', 'referenced_plan_id' => $sub->id,
    ]);

    // group = 200 € leaf + 500 € mounted sub-plan = 700 €, and so is the plan income total
    expect($group->fresh()->effectiveValue()->getAmount())->toBe('70000')
        ->and($parent->incomeTotal()->getAmount())->toBe('70000');
});

it('transforms a nested budget item into a mount via the editor', function (): void {
    $this->actingAs(budgetManager());

    $sub = planWithIncome(500);
    $parent = BudgetPlan::create(['state' => Draft::class]);
    $group = $parent->budgetItems()->create([
        'is_group' => true, 'budget_type' => BudgetType::INCOME, 'position' => 0, 'short_name' => 'E1',
    ]);
    $leaf = $group->children()->create([
        'budget_plan_id' => $parent->id, 'is_group' => false, 'budget_type' => BudgetType::INCOME,
        'position' => 0, 'short_name' => 'E1.1',
    ]);

    Livewire::test('pages::budget-plan.plan-edit', ['plan_id' => $parent->id])
        ->set('mount_item_id', $leaf->id)
        ->set('mount_plan_id', $sub->id)
        ->call('convertToMount')
        ->assertHasNoErrors();

    expect($leaf->fresh()->isMount())->toBeTrue()
        ->and($parent->incomeTotal()->getAmount())->toBe('50000'); // nested mount rolls up
});

it('mounts a plan via the editor and rolls the total up', function (): void {
    $this->actingAs(budgetManager());

    $sub = planWithIncome(500);
    $parent = BudgetPlan::create(['state' => Draft::class]);
    $root = $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::INCOME, 'position' => 0, 'short_name' => 'E1',
    ]);

    Livewire::test('pages::budget-plan.plan-edit', ['plan_id' => $parent->id])
        ->set('mount_item_id', $root->id)
        ->set('mount_plan_id', $sub->id)
        ->call('convertToMount')
        ->assertHasNoErrors();

    expect($root->fresh()->isMount())->toBeTrue()
        ->and($root->fresh()->referenced_plan_id)->toBe($sub->id)
        ->and($parent->incomeTotal()->getAmount())->toBe('50000');
});

it('loads mount candidates excluding self and cycle-creating plans', function (): void {
    $this->actingAs(budgetManager());

    $parent = BudgetPlan::create(['state' => Draft::class, 'organization' => 'Parent']);
    $ok = BudgetPlan::create(['state' => Draft::class, 'organization' => 'Mountable']);
    $cycle = BudgetPlan::create(['state' => Draft::class, 'organization' => 'WouldCycle']);
    mountInto($cycle, $parent, BudgetType::INCOME); // $cycle already reaches $parent

    $root = $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::INCOME, 'position' => 0, 'short_name' => 'E1',
    ]);

    $candidates = Livewire::test('pages::budget-plan.plan-edit', ['plan_id' => $parent->id])
        ->call('openMountPicker', $root->id)
        ->get('mount_candidates');
    $ids = collect($candidates)->pluck('id');

    expect($ids)->toContain($ok->id)
        ->and($ids)->not->toContain($parent->id)  // self excluded
        ->and($ids)->not->toContain($cycle->id);   // would-cycle excluded
});

it('only offers mount candidates from the same fiscal year', function (): void {
    $this->actingAs(budgetManager());

    $fy1 = FiscalYear::factory()->create();
    $fy2 = FiscalYear::factory()->create();
    $parent = BudgetPlan::create(['state' => Draft::class, 'fiscal_year_id' => $fy1->id]);
    $same = BudgetPlan::create(['state' => Draft::class, 'fiscal_year_id' => $fy1->id, 'organization' => 'Same year']);
    $other = BudgetPlan::create(['state' => Draft::class, 'fiscal_year_id' => $fy2->id, 'organization' => 'Other year']);

    $root = $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::INCOME, 'position' => 0, 'short_name' => 'E1',
    ]);

    $ids = collect(Livewire::test('pages::budget-plan.plan-edit', ['plan_id' => $parent->id])
        ->call('openMountPicker', $root->id)
        ->get('mount_candidates'))->pluck('id');

    expect($ids)->toContain($same->id)->and($ids)->not->toContain($other->id);
});

it('rejects mounting a plan that would create a cycle', function (): void {
    $this->actingAs(budgetManager());

    $a = BudgetPlan::create(['state' => Draft::class]);
    $b = BudgetPlan::create(['state' => Draft::class]);
    mountInto($b, $a, BudgetType::INCOME); // B already mounts A
    $root = $a->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::INCOME, 'position' => 0, 'short_name' => 'E1',
    ]);

    Livewire::test('pages::budget-plan.plan-edit', ['plan_id' => $a->id])
        ->set('mount_item_id', $root->id)
        ->set('mount_plan_id', $b->id)
        ->call('convertToMount');

    expect($root->fresh()->isMount())->toBeFalse(); // mounting B into A would cycle -> rejected
});

it('detects reference cycles (self and transitive)', function (): void {
    $a = BudgetPlan::create(['state' => Draft::class]);
    $b = BudgetPlan::create(['state' => Draft::class]);
    mountInto($a, $b, BudgetType::INCOME); // A mounts B

    expect($a->reachesPlan($a->id))->toBeTrue()   // self
        ->and($a->reachesPlan($b->id))->toBeTrue()  // A -> B
        ->and($b->reachesPlan($a->id))->toBeFalse(); // B does not reach A (yet)
});
