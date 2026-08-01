<?php

use App\Models\BudgetItem;
use App\Models\BudgetItemChange;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Draft;
use App\Support\Budget\AmendmentDeltaSummary;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * F5 (OP#581): the aggregated income/expense delta an amendment would apply, plus the resulting
 * saldo shift. Only LEAF items ever contribute — a group's value is always derived (the live sum
 * of its children), so counting it too would double-count every leaf underneath it.
 */
uses(DatabaseTransactions::class);

function deltaSummaryParent(): BudgetPlan
{
    return BudgetPlan::factory()->create(['state' => Active::class]);
}

function deltaSummaryAmendment(BudgetPlan $parent): BudgetPlan
{
    return BudgetPlan::create([
        'state' => Draft::class,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
    ]);
}

it('does not double-count an added group\'s value alongside its added leaf child', function (): void {
    $parent = deltaSummaryParent();
    $amendment = deltaSummaryAmendment($parent);

    // mirrors addGroup(): a group AND its first leaf child both get their own `add` change row
    $group = BudgetItem::create([
        'budget_plan_id' => $amendment->id, 'is_group' => true, 'budget_type' => BudgetType::EXPENSE,
        'position' => 0, 'short_name' => 'A9', 'name' => 'Neue Gruppe', 'value' => Money::EUR(0),
    ]);
    $leaf = BudgetItem::create([
        'budget_plan_id' => $amendment->id, 'parent_id' => $group->id, 'is_group' => false,
        'budget_type' => BudgetType::EXPENSE, 'position' => 0, 'short_name' => 'A9.1',
        'name' => 'Neuer Titel', 'value' => Money::EUR(5000),
    ]);
    BudgetItemChange::create(['budget_plan_id' => $amendment->id, 'budget_item_id' => $group->id, 'action' => BudgetItemChange::ACTION_ADD]);
    BudgetItemChange::create(['budget_plan_id' => $amendment->id, 'budget_item_id' => $leaf->id, 'action' => BudgetItemChange::ACTION_ADD]);

    $summary = new AmendmentDeltaSummary()->compute($amendment);

    // only the leaf's 50 EUR counts — the group is skipped entirely, so it's not 100 EUR
    expect((int) $summary['expense']->getAmount())->toBe(5000)
        ->and((int) $summary['income']->getAmount())->toBe(0)
        ->and((int) $summary['saldo']->getAmount())->toBe(-5000);
});

it('sums a mixed modify/add/delete scenario across both budget types into the correct saldo', function (): void {
    $parent = deltaSummaryParent();

    // income leaf: modified from 200 to 250 (+50 income)
    $incomeLeaf = $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::INCOME, 'position' => 0,
        'short_name' => 'E1', 'name' => 'Zuschuss', 'value' => Money::EUR(20000),
    ]);
    // expense leaf: will be deleted (-30 expense removed from the plan)
    $expenseLeafToDelete = $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Wegfallend', 'value' => Money::EUR(3000),
    ]);
    $amendment = deltaSummaryAmendment($parent);

    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $incomeLeaf->id,
        'action' => BudgetItemChange::ACTION_MODIFY,
        'diff' => ['value' => ['from' => 20000, 'to' => 25000]],
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $expenseLeafToDelete->id,
        'action' => BudgetItemChange::ACTION_DELETE,
    ]);
    // new expense leaf added: +80 expense
    $addedExpenseLeaf = BudgetItem::create([
        'budget_plan_id' => $amendment->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
        'position' => 1, 'short_name' => 'A2', 'name' => 'Neu', 'value' => Money::EUR(8000),
    ]);
    BudgetItemChange::create(['budget_plan_id' => $amendment->id, 'budget_item_id' => $addedExpenseLeaf->id, 'action' => BudgetItemChange::ACTION_ADD]);

    $summary = new AmendmentDeltaSummary()->compute($amendment);

    // income delta: +50 EUR (25000 - 20000 cents)
    expect((int) $summary['income']->getAmount())->toBe(5000)
        // expense delta: +80 (added) - 30 (deleted, subtracted) = +50 EUR
        ->and((int) $summary['expense']->getAmount())->toBe(5000)
        // saldo = income delta - expense delta = 0
        ->and((int) $summary['saldo']->getAmount())->toBe(0);
});

it('renders the delta summary in both the editor\'s Begründungen tab and the amendment plan-view diff', function (): void {
    $this->actingAs(budgetManager());
    $parent = deltaSummaryParent();
    $leaf = $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Material', 'value' => Money::EUR(10000),
    ]);
    $amendment = deltaSummaryAmendment($parent);

    $editorHtml = Livewire::test('pages::budget-plan.amendment-edit', ['plan_id' => $parent->id, 'amendment_id' => $amendment->id])
        ->set('items.'.$leaf->id.'.value', Money::EUR(15000))
        ->html();
    expect($editorHtml)->toContain(__('budget-plan.amendment.delta-heading'))
        ->and($editorHtml)->toContain(Money::EUR(5000)->format());

    BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)
        ->update(['reason' => 'Preissteigerung']);

    $viewHtml = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])->html();
    expect($viewHtml)->toContain(__('budget-plan.amendment.delta-heading'))
        ->and($viewHtml)->toContain(Money::EUR(5000)->format())
        // F6: the reason label precedes the reason text
        ->and($viewHtml)->toContain(__('budget-plan.amendment.reason-label'))
        ->and($viewHtml)->toContain('Preissteigerung');
});
