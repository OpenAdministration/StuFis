<?php

use App\Models\BudgetItemChange;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetItemChangeAction;
use App\Models\Enums\BudgetType;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Draft;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * OP#587: the title detail view (⚡item-view) surfaces every BudgetItemChange currently pointing
 * at the item, so a budget officer sees at a glance that a title has been (or is about to be)
 * touched by an amendment — this was previously invisible there. Parallel amendments
 * are allowed (OP#581: unique key is (budget_plan_id, budget_item_id), not budget_item_id alone),
 * so more than one row can apply to the same title at once, in different states.
 */
uses(DatabaseTransactions::class);

/** An Active parent plan with one bookable leaf; returns [plan, leaf]. */
function hintParentAndLeaf(): array
{
    $plan = BudgetPlan::factory()->create(['state' => Active::class]);
    $leaf = $plan->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Material', 'value' => Money::EUR(100, true),
    ]);

    return [$plan, $leaf];
}

/** An amendment plan against $parent, created directly in $state (bypassing the workflow). */
function hintAmendment(BudgetPlan $parent, string $state, ?string $name = null): BudgetPlan
{
    return BudgetPlan::create([
        'state' => $state,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
        'name' => $name,
    ]);
}

it('shows no amendment hint when the title has never been touched by an amendment', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = hintParentAndLeaf();

    $this->get(route('budget-plan.item.view', [$plan->id, $leaf->id]))
        ->assertOk()
        ->assertDontSee(__('budget-plan.item.amendment-hint.heading'));
});

it('shows a hint for a still-pending (Draft) amendment, with its label, state and reason', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = hintParentAndLeaf();
    $amendment = hintAmendment($plan, Draft::class, 'Nachtrag Sommerfest');

    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $leaf->id,
        'action' => BudgetItemChangeAction::Modify,
        'diff' => ['value' => ['from' => 10000, 'to' => 15000]],
        'reason' => 'Preissteigerung beim Lieferanten',
    ]);

    $this->get(route('budget-plan.item.view', [$plan->id, $leaf->id]))
        ->assertOk()
        ->assertSee(__('budget-plan.item.amendment-hint.heading'))
        ->assertSee(__('budget-plan.amendment.change.modify'))
        ->assertSee('Nachtrag Sommerfest')
        ->assertSee(__('budget-plan.stateNames.draft'))
        ->assertSee('Preissteigerung beim Lieferanten')
        ->assertSee(route('budget-plan.view', $amendment->id), false);
});

it('shows a hint for an already-applied (Active) amendment', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = hintParentAndLeaf();
    // unnamed — the hint must fall back to the same "Nachtrag vom <date>" label as everywhere else
    $amendment = hintAmendment($plan, Active::class);

    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $leaf->id,
        'action' => BudgetItemChangeAction::Modify,
        'diff' => ['value' => ['from' => 10000, 'to' => 12000]],
    ]);

    $this->get(route('budget-plan.item.view', [$plan->id, $leaf->id]))
        ->assertOk()
        ->assertSee(__('budget-plan.item.amendment-hint.heading'))
        ->assertSee(__('budget-plan.amendment.unnamed-fallback', ['date' => $amendment->created_at->format('d.m.Y')]))
        ->assertSee(__('budget-plan.stateNames.active'));
});

it('lists TWO parallel amendments touching the same title at once, each with its own state', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = hintParentAndLeaf();

    $pending = hintAmendment($plan, Draft::class, 'Nachtrag Herbst');
    $applied = hintAmendment($plan, Active::class, 'Nachtrag Fruehjahr');

    BudgetItemChange::create([
        'budget_plan_id' => $pending->id, 'budget_item_id' => $leaf->id,
        'action' => BudgetItemChangeAction::Modify,
        'diff' => ['value' => ['from' => 10000, 'to' => 11000]],
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $applied->id, 'budget_item_id' => $leaf->id,
        'action' => BudgetItemChangeAction::Modify,
        'diff' => ['value' => ['from' => 10000, 'to' => 9000]],
    ]);

    $html = Livewire::test('pages::budget-plan.item-view', ['plan_id' => $plan->id, 'item_id' => $leaf->id])->html();

    expect($html)->toContain('Nachtrag Herbst')
        ->and($html)->toContain(__('budget-plan.stateNames.draft'))
        ->and($html)->toContain('Nachtrag Fruehjahr')
        ->and($html)->toContain(__('budget-plan.stateNames.active'))
        ->and($html)->toContain(route('budget-plan.view', $pending->id))
        ->and($html)->toContain(route('budget-plan.view', $applied->id));
});

it('spells out WHAT each amendment changes on this title, field by field', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = hintParentAndLeaf();
    $amendment = hintAmendment($plan, Draft::class, 'Nachtrag Sommerfest');
    $amendment->update(['activation_date' => '2026-09-01']);

    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $leaf->id,
        'action' => BudgetItemChangeAction::Modify,
        'diff' => [
            'value' => ['from' => 10000, 'to' => 15000],
            'name' => ['from' => 'Material', 'to' => 'Material und Werkzeug'],
        ],
    ]);

    $html = Livewire::test('pages::budget-plan.item-view', ['plan_id' => $plan->id, 'item_id' => $leaf->id])->html();

    // the touched fields, each with its own from -> to — not just "this title is affected"
    expect($html)->toContain(__('budget-plan.amendment.field.value'))
        ->and($html)->toContain('100,00')
        ->and($html)->toContain('150,00')
        ->and($html)->toContain(__('budget-plan.amendment.field.name'))
        ->and($html)->toContain('Material und Werkzeug')
        // and when it lands in the plan
        ->and($html)->toContain(__('budget-plan.item.amendment-hint.effective', ['date' => '01.09.2026']));
});

it('names the amount at stake for a title an amendment deletes', function (): void {
    $this->actingAs(user());
    [$plan, $leaf] = hintParentAndLeaf();
    $amendment = hintAmendment($plan, Draft::class, 'Nachtrag Streichung');

    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $leaf->id,
        'action' => BudgetItemChangeAction::Delete,
    ]);

    $html = Livewire::test('pages::budget-plan.item-view', ['plan_id' => $plan->id, 'item_id' => $leaf->id])->html();

    expect($html)->toContain(__('budget-plan.amendment.change.delete'))
        ->and($html)->toContain(__('budget-plan.amendment.change-detail.removed'))
        // the amount that disappears from the plan, struck through
        ->and($html)->toContain('100,00');
});
