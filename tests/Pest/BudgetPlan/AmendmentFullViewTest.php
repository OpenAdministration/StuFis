<?php

use App\Models\BudgetItem;
use App\Models\BudgetItemChange;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetItemChangeAction;
use App\Models\Enums\BudgetType;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Approved;
use App\States\BudgetPlan\Draft;
use App\States\BudgetPlan\Resolved;
use App\Support\Budget\AmendmentTree;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * The amendment's SECOND view (the counterpart to AmendmentDiffViewTest): the parent plan's full
 * tree with this amendment's changes marked in place, switchable against the diff on the same
 * page. Covers the toggle itself and App\Support\Budget\AmendmentTree's before/after annotation —
 * including after the amendment was applied, where the items have been re-homed between the two
 * plans and only the change rows still say what the plan looked like before.
 */
uses(DatabaseTransactions::class);

/**
 * A parent plan with a group holding two leaves — one the amendment will touch, one it won't.
 *
 * @return array{0: BudgetPlan, 1: BudgetItem, 2: BudgetItem, 3: BudgetItem}
 */
function nhhpFullParent(): array
{
    $plan = BudgetPlan::factory()->create(['state' => Active::class]);
    $group = $plan->budgetItems()->create([
        'is_group' => true, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A', 'name' => 'Sachkosten', 'value' => Money::EUR(0),
    ]);
    $leaf = $plan->budgetItems()->create([
        'parent_id' => $group->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Material', 'value' => Money::EUR(10000),
    ]);
    $untouched = $plan->budgetItems()->create([
        'parent_id' => $group->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 1,
        'short_name' => 'A2', 'name' => 'Unveraendert', 'value' => Money::EUR(2000),
    ]);

    return [$plan, $group, $leaf, $untouched];
}

function nhhpFullAmendment(BudgetPlan $parent): BudgetPlan
{
    return BudgetPlan::create([
        'state' => Draft::class,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
    ]);
}

it('defaults to the diff and only shows the full tree once toggled', function (): void {
    $this->actingAs(user());
    [$parent, , $leaf] = nhhpFullParent();
    $amendment = nhhpFullAmendment($parent);

    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $leaf->id,
        'action' => BudgetItemChangeAction::Modify,
        'diff' => ['value' => ['from' => 10000, 'to' => 15000]],
    ]);

    $component = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id]);

    // default: the diff, so an untouched item is nowhere on the page
    expect($component->html())->toContain(__('budget-plan.amendment.diff-heading'))
        ->and($component->html())->not->toContain('Unveraendert');

    $full = $component->set('amendmentView', 'full')->html();

    expect($full)->toContain(__('budget-plan.amendment.full-heading'))
        // the whole plan now, untouched items included...
        ->and($full)->toContain('Unveraendert')
        ->and($full)->toContain('Sachkosten')
        // ...with the change marked in place, before and after side by side
        ->and($full)->toContain(__('budget-plan.amendment.change.modify'))
        ->and($full)->toContain('100,00')
        ->and($full)->toContain('150,00');
});

it('renders a renamed item under its new name, with the previous one alongside', function (): void {
    $this->actingAs(user());
    [$parent, , $leaf] = nhhpFullParent();
    $amendment = nhhpFullAmendment($parent);

    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $leaf->id,
        'action' => BudgetItemChangeAction::Modify,
        'diff' => ['name' => ['from' => 'Material', 'to' => 'Material und Werkzeug']],
    ]);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('amendmentView', 'full')
        ->html();

    // the live row still says "Material" until the amendment is applied — the tree must read the
    // name off the overlay, not off the row
    expect($html)->toContain('Material und Werkzeug')
        ->and($html)->toContain('line-through');
});

it('marks added and deleted items in the full tree', function (): void {
    $this->actingAs(user());
    [$parent, $group, , $untouched] = nhhpFullParent();
    $amendment = nhhpFullAmendment($parent);

    $added = BudgetItem::create([
        'budget_plan_id' => $amendment->id, 'parent_id' => $group->id, 'is_group' => false,
        'budget_type' => BudgetType::EXPENSE, 'position' => 9,
        'short_name' => 'A9', 'name' => 'Neuer Titel', 'value' => Money::EUR(500),
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $added->id,
        'action' => BudgetItemChangeAction::Add,
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $untouched->id,
        'action' => BudgetItemChangeAction::Delete,
    ]);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('amendmentView', 'full')
        ->html();

    expect($html)->toContain('Neuer Titel')
        ->and($html)->toContain(__('budget-plan.amendment.change.add'))
        ->and($html)->toContain(__('budget-plan.amendment.change.delete'))
        // the amendment's own addition sits in the parent plan's group, not in a list of its own
        ->and($html)->toContain('Sachkosten');
});

it('counts the changed titles per side for the tab badges', function (): void {
    [$parent, $group, $leaf, $untouched] = nhhpFullParent();
    $amendment = nhhpFullAmendment($parent);

    // an income title the amendment never touches — that side must come out at zero
    $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::INCOME, 'position' => 0,
        'short_name' => 'E1', 'name' => 'Zuschuss', 'value' => Money::EUR(50000),
    ]);

    $added = BudgetItem::create([
        'budget_plan_id' => $amendment->id, 'parent_id' => $group->id, 'is_group' => false,
        'budget_type' => BudgetType::EXPENSE, 'position' => 9,
        'short_name' => 'A9', 'name' => 'Neuer Titel', 'value' => Money::EUR(500),
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $added->id,
        'action' => BudgetItemChangeAction::Add,
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $leaf->id,
        'action' => BudgetItemChangeAction::Modify,
        'diff' => ['value' => ['from' => 10000, 'to' => 15000]],
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $untouched->id,
        'action' => BudgetItemChangeAction::Delete,
    ]);

    $tree = new AmendmentTree($amendment);

    expect($tree->changeCounts(BudgetType::EXPENSE))
        ->toBe(['total' => 3, 'add' => 1, 'modify' => 1, 'delete' => 1])
        ->and($tree->changeCounts(BudgetType::INCOME))
        ->toBe(['total' => 0, 'add' => 0, 'modify' => 0, 'delete' => 0]);
});

it('annotates every node with its before and after figure, rolled up through groups', function (): void {
    [$parent, $group, $leaf, $untouched] = nhhpFullParent();
    $amendment = nhhpFullAmendment($parent);

    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $leaf->id,
        'action' => BudgetItemChangeAction::Modify,
        'diff' => ['value' => ['from' => 10000, 'to' => 15000]],
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $untouched->id,
        'action' => BudgetItemChangeAction::Delete,
    ]);

    $rows = new AmendmentTree($amendment)->annotate(BudgetType::EXPENSE)->keyBy('id');

    expect($rows->get($leaf->id)->before_value->getAmount())->toBe('10000')
        ->and($rows->get($leaf->id)->after_value->getAmount())->toBe('15000')
        // a deleted item still has a "before", but no "after" at all
        ->and($rows->get($untouched->id)->before_value->getAmount())->toBe('2000')
        ->and($rows->get($untouched->id)->after_value)->toBeNull()
        // the group sums whichever children exist on each side: 100 + 20 before, 150 after
        ->and($rows->get($group->id)->before_value->getAmount())->toBe('12000')
        ->and($rows->get($group->id)->after_value->getAmount())->toBe('15000')
        ->and($rows->get($group->id)->tree_level)->toBe(0)
        ->and($rows->get($leaf->id)->tree_level)->toBe(1)
        ->and($rows->get($leaf->id)->ancestor_ids)->toBe([$group->id]);

    $totals = new AmendmentTree($amendment)->totals(BudgetType::EXPENSE);
    expect($totals['before']->getAmount())->toBe('12000')
        ->and($totals['after']->getAmount())->toBe('15000');
});

it('reads the same before and after once the amendment has been applied', function (): void {
    [$parent, $group, $leaf, $untouched] = nhhpFullParent();
    $amendment = nhhpFullAmendment($parent);

    $added = BudgetItem::create([
        'budget_plan_id' => $amendment->id, 'parent_id' => $group->id, 'is_group' => false,
        'budget_type' => BudgetType::EXPENSE, 'position' => 9,
        'short_name' => 'A9', 'name' => 'Neuer Titel', 'value' => Money::EUR(500),
    ]);
    foreach ([[$leaf->id, BudgetItemChangeAction::Modify], [$added->id, BudgetItemChangeAction::Add], [$untouched->id, BudgetItemChangeAction::Delete]] as [$itemId, $action]) {
        BudgetItemChange::create([
            'budget_plan_id' => $amendment->id, 'budget_item_id' => $itemId, 'action' => $action,
            'diff' => $action === BudgetItemChangeAction::Modify ? ['value' => ['from' => 10000, 'to' => 15000]] : null,
        ]);
    }

    $amendment->state->transitionTo(Resolved::class);
    $amendment->state->transitionTo(Approved::class);
    $amendment->fresh()->state->transitionTo(Active::class);

    // apply() re-homed the addition onto the parent plan, parked the deletion on the amendment and
    // wrote the modify live — the tree must still read the plan exactly the same way
    $rows = new AmendmentTree($amendment->fresh())->annotate(BudgetType::EXPENSE)->keyBy('id');

    expect($rows->get($leaf->id)->before_value->getAmount())->toBe('10000')
        ->and($rows->get($leaf->id)->after_value->getAmount())->toBe('15000')
        ->and($rows->get($added->id)->before_value)->toBeNull()
        ->and($rows->get($added->id)->after_value->getAmount())->toBe('500')
        ->and($rows->get($untouched->id)->after_value)->toBeNull()
        ->and($rows->get($group->id)->before_value->getAmount())->toBe('12000')
        ->and($rows->get($group->id)->after_value->getAmount())->toBe('15500');
});
