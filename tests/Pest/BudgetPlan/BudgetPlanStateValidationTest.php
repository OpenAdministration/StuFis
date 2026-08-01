<?php

use App\Models\BudgetItem;
use App\Models\BudgetItemChange;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Approved;
use App\States\BudgetPlan\Completed;
use App\States\BudgetPlan\Draft;
use App\States\BudgetPlan\Resolved;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

/**
 * OP#584: business-rule validation of a plan's budget items before it may advance along its
 * workflow — short_name (Titelnummer) unique within scope, name non-empty, value non-negative.
 * Enforced only at the Livewire layer (⚡plan-view::changeState()), and only on a FORWARD step
 * (BudgetPlanState::advancesTo(), keyed off the canonical order() Draft < Resolved < Approved <
 * Active < Completed): a backward step only ever demotes data away from "official" and must never
 * be gated by a pre-existing violation it cannot fix — reverting an applied amendment, or
 * reactivating a Completed plan, must always stay possible.
 */
uses(DatabaseTransactions::class);

/** A Draft plan seeded with one root budget_item per $items entry (short_name/name/value overrides). */
function draftPlanWithItems(array ...$items): BudgetPlan
{
    $plan = BudgetPlan::create(['state' => Draft::class]);
    foreach ($items as $i => $attrs) {
        $plan->budgetItems()->create(array_merge([
            'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => $i,
        ], $attrs));
    }

    return $plan;
}

/** A Draft amendment against $parent, immediately advanced to Approved (no items touched yet). */
function stateValidationApprovedAmendment(BudgetPlan $parent): BudgetPlan
{
    $amendment = BudgetPlan::create([
        'state' => Draft::class,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
    ]);
    $amendment->state->transitionTo(Resolved::class);
    $amendment->state->transitionTo(Approved::class);

    return $amendment->fresh();
}

it('blocks the transition out of Draft when two items share a Titelnummer', function (): void {
    $this->actingAs(budgetManager());
    $plan = draftPlanWithItems(
        ['short_name' => 'A1', 'name' => 'Material', 'value' => Money::EUR(100, true)],
        ['short_name' => 'A1', 'name' => 'Fahrtkosten', 'value' => Money::EUR(50, true)],
    );

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->set('newState', 'resolved')
        ->call('changeState')
        ->assertHasErrors('newState');

    expect($plan->fresh()->state)->toBeInstanceOf(Draft::class);
});

it('blocks the transition out of Draft when an item has an empty name', function (): void {
    $this->actingAs(budgetManager());
    $plan = draftPlanWithItems(
        ['short_name' => 'A1', 'name' => '', 'value' => Money::EUR(100, true)],
    );

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->set('newState', 'resolved')
        ->call('changeState')
        ->assertHasErrors('newState');

    expect($plan->fresh()->state)->toBeInstanceOf(Draft::class);
});

it('blocks the transition out of Draft when an item has a negative value', function (): void {
    $this->actingAs(budgetManager());
    $plan = draftPlanWithItems(
        ['short_name' => 'A1', 'name' => 'Material', 'value' => Money::EUR(-100, true)],
    );

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->set('newState', 'resolved')
        ->call('changeState')
        ->assertHasErrors('newState');

    expect($plan->fresh()->state)->toBeInstanceOf(Draft::class);
});

it('lets a clean plan advance all the way from Draft to Active', function (): void {
    $this->actingAs(budgetManager());
    $plan = draftPlanWithItems(
        ['short_name' => 'A1', 'name' => 'Material', 'value' => Money::EUR(100, true)],
        ['short_name' => 'A2', 'name' => 'Fahrtkosten', 'value' => Money::EUR(50, true)],
    );

    foreach (['resolved', 'approved', 'active'] as $newState) {
        Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
            ->set('newState', $newState)
            ->call('changeState')
            ->assertHasNoErrors();
    }

    expect($plan->fresh()->state)->toBeInstanceOf(Active::class);
});

it('catches an amendment introducing a Titelnummer that already exists on its base plan', function (): void {
    $this->actingAs(budgetManager());
    $parent = BudgetPlan::factory()->create(['state' => Active::class]);
    $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1.1', 'name' => 'Material', 'value' => Money::EUR(100, true),
    ]);
    $amendment = stateValidationApprovedAmendment($parent);

    // a brand-new item, drafted under the amendment, that collides with the parent's A1.1 —
    // a per-plan-only uniqueness check would miss this (the two rows live under different
    // budget_plan_id values right up until apply)
    $added = BudgetItem::create([
        'budget_plan_id' => $amendment->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
        'position' => 0, 'short_name' => 'A1.1', 'name' => 'Doppelt', 'value' => Money::EUR(10, true),
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $added->id,
        'action' => BudgetItemChange::ACTION_ADD,
    ]);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('newState', 'active')
        ->call('changeState')
        ->assertHasErrors('newState');

    expect($amendment->fresh()->state)->toBeInstanceOf(Approved::class);
});

it('does not flag an amendment\'s own modify row — which points at the live base item, not a copy — as a duplicate of itself', function (): void {
    $this->actingAs(budgetManager());
    $parent = BudgetPlan::factory()->create(['state' => Active::class]);
    $leaf = $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1.1', 'name' => 'Material', 'value' => Money::EUR(100, true),
    ]);
    $amendment = stateValidationApprovedAmendment($parent);

    // modify: budget_item_id points straight at the live $leaf row (see BudgetItemChange's class
    // doc) — nothing new is created under the amendment's own budget_plan_id, so the item shows
    // up exactly once in the combined [amendment, parent] scope, never as a duplicate of itself
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $leaf->id,
        'action' => BudgetItemChange::ACTION_MODIFY,
        'diff' => ['value' => ['from' => (int) $leaf->value->getAmount(), 'to' => 20000]],
    ]);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('newState', 'active')
        ->call('changeState')
        ->assertHasNoErrors();

    expect($amendment->fresh()->state)->toBeInstanceOf(Active::class);
});

it('lets an applied amendment be reverted (Active -> Approved) even though its base plan currently violates the item rules', function (): void {
    $this->actingAs(budgetManager());
    $parent = BudgetPlan::factory()->create(['state' => Active::class]);
    // two items sharing a Titelnummer on the BASE plan — would block any forward move, but
    // reverting is a backward step and must not be gated by it
    $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Material', 'value' => Money::EUR(100, true),
    ]);
    $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 1,
        'short_name' => 'A1', 'name' => 'Fahrtkosten', 'value' => Money::EUR(50, true),
    ]);
    $amendment = BudgetPlan::create([
        'state' => Draft::class, 'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id, 'parent_plan_id' => $parent->id,
    ]);
    $amendment->state->transitionTo(Resolved::class);
    $amendment->state->transitionTo(Approved::class);
    $amendment->state->transitionTo(Active::class); // applies cleanly: no item changes drafted

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('newState', 'approved')
        ->call('changeState')
        ->assertHasNoErrors();

    expect($amendment->fresh()->state)->toBeInstanceOf(Approved::class);
});

it('lets a Completed plan be reactivated (Completed -> Active) even though it currently violates the item rules', function (): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::factory()->create(['state' => Completed::class]);
    $plan->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => null, 'name' => '', 'value' => Money::EUR(-50, true),
    ]);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->set('newState', 'active')
        ->call('changeState')
        ->assertHasNoErrors();

    expect($plan->fresh()->state)->toBeInstanceOf(Active::class);
});
