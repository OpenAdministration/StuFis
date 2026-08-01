<?php

use App\Models\BudgetItem;
use App\Models\BudgetItemChange;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Draft;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Area H (light) of the OP#581 test plan: the amendment's own plan-view renders a diff of
 * changed items only (from -> to, with reasons) instead of the full merged tree.
 *
 * History ("Stand zum Datum X" — reconstructing a plan's pre-amendment state) was NOT
 * implemented (left as a TODO per the implementing agent's report), so that half of area H is
 * intentionally not covered here.
 */
uses(DatabaseTransactions::class);

function nhhpDiffParent(): array
{
    $plan = BudgetPlan::factory()->create(['state' => Active::class]);
    $leaf = $plan->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Material', 'value' => Money::EUR(10000),
    ]);
    $untouched = $plan->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 1,
        'short_name' => 'A2', 'name' => 'Unveraendert', 'value' => Money::EUR(2000),
    ]);

    return [$plan, $leaf, $untouched];
}

function nhhpDiffAmendment(BudgetPlan $parent): BudgetPlan
{
    return BudgetPlan::create([
        'state' => Draft::class,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
    ]);
}

it('shows only changed items on the amendment plan-view, with from->to and the recorded reason', function (): void {
    $this->actingAs(user());
    [$parent, $leaf, $untouched] = nhhpDiffParent();
    $amendment = nhhpDiffAmendment($parent);

    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $leaf->id,
        'action' => BudgetItemChange::ACTION_MODIFY,
        'changes' => ['value' => ['from' => 10000, 'to' => 15000]],
        'reason' => 'Preissteigerung beim Lieferanten',
    ]);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])->html();

    expect($html)->toContain(__('budget-plan.amendment.diff-heading'))
        ->and($html)->toContain(__('budget-plan.amendment.change.modify'))
        ->and($html)->toContain('100,00')
        ->and($html)->toContain('150,00')
        ->and($html)->toContain('Preissteigerung beim Lieferanten')
        ->and($html)->toContain('A1')
        // the untouched item never appears — only the diff is rendered, not the full tree
        ->and($html)->not->toContain('Unveraendert');
});

it('shows added and deleted items on the diff with their action badges', function (): void {
    $this->actingAs(user());
    [$parent, , $untouched] = nhhpDiffParent();
    $amendment = nhhpDiffAmendment($parent);

    $added = BudgetItem::create([
        'budget_plan_id' => $amendment->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
        'position' => 9, 'short_name' => 'A9', 'name' => 'Neuer Titel', 'value' => Money::EUR(500),
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $added->id,
        'action' => BudgetItemChange::ACTION_ADD,
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $untouched->id,
        'action' => BudgetItemChange::ACTION_DELETE,
    ]);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])->html();

    expect($html)->toContain(__('budget-plan.amendment.change.add'))
        ->and($html)->toContain('Neuer Titel')
        ->and($html)->toContain(__('budget-plan.amendment.change.delete'))
        ->and($html)->toContain('Unveraendert');
});

it('shows a placeholder when the amendment has no changes yet', function (): void {
    $this->actingAs(user());
    [$parent] = nhhpDiffParent();
    $amendment = nhhpDiffAmendment($parent);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->assertSee(__('budget-plan.amendment.no-changes-yet'));
});
