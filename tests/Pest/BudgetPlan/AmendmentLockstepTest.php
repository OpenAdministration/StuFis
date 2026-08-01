<?php

use App\Models\BudgetPlan;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Approved;
use App\States\BudgetPlan\Completed;
use App\States\BudgetPlan\Draft;
use App\States\BudgetPlan\Resolved;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;

/**
 * F8 (OP#589): "Der NHHP soll nur synchron von completed und active mit dem original wechseln.
 * Einzeln soll er nicht verschiebbar sein." An amendment's Active <-> Completed arcs move only in
 * lockstep with its parent plan (CompleteAmendmentsTransition / ReactivateAmendmentsTransition),
 * never on its own (blocked in BudgetPlanPolicy::transitionTo). Its Approved <-> Active arcs
 * (apply/revert) remain individually available — that's unrelated machinery and must not regress.
 */
uses(DatabaseTransactions::class);

/** A Draft amendment against $parent, advanced straight to Active (applies cleanly: no item changes drafted). */
function nhhpActiveAmendment(BudgetPlan $parent): BudgetPlan
{
    $amendment = BudgetPlan::create([
        'state' => Draft::class,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
    ]);
    $amendment->state->transitionTo(Resolved::class);
    $amendment->state->transitionTo(Approved::class);
    $amendment->state->transitionTo(Active::class);

    return $amendment->fresh();
}

it('cascades an Active amendment to Completed when its parent plan completes, in one go', function (): void {
    $parent = BudgetPlan::factory()->create(['state' => Active::class]);
    $amendment = nhhpActiveAmendment($parent);

    $parent->state->transitionTo(Completed::class);

    expect($parent->fresh()->state)->toBeInstanceOf(Completed::class)
        ->and($amendment->fresh()->state)->toBeInstanceOf(Completed::class);
});

it('cascades a Completed amendment back to Active when its parent plan reactivates', function (): void {
    $parent = BudgetPlan::factory()->create(['state' => Active::class]);
    $amendment = nhhpActiveAmendment($parent);
    $parent->state->transitionTo(Completed::class); // cascades the amendment to Completed too

    $parent->state->transitionTo(Active::class);

    expect($parent->fresh()->state)->toBeInstanceOf(Active::class)
        ->and($amendment->fresh()->state)->toBeInstanceOf(Active::class);
});

it('leaves a not-yet-applied amendment (Draft/Resolved/Approved) untouched when the parent plan completes', function (string $stateClass): void {
    $parent = BudgetPlan::factory()->create(['state' => Active::class]);
    $amendment = BudgetPlan::create([
        'state' => Draft::class,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
    ]);
    if ($stateClass !== Draft::class) {
        $amendment->state->transitionTo(Resolved::class);
    }
    if ($stateClass === Approved::class) {
        $amendment->state->transitionTo(Approved::class);
    }

    $parent->state->transitionTo(Completed::class);

    expect($amendment->fresh()->state)->toBeInstanceOf($stateClass);
})->with([
    'draft' => [Draft::class],
    'resolved' => [Resolved::class],
    'approved' => [Approved::class],
]);

it('forbids walking an amendment Active -> Completed individually — only via the parent cascade', function (): void {
    $this->actingAs(budgetManager());
    $parent = BudgetPlan::factory()->create(['state' => Active::class]);
    $amendment = nhhpActiveAmendment($parent);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('newState', 'completed')
        ->call('changeState')
        ->assertForbidden();

    expect($amendment->fresh()->state)->toBeInstanceOf(Active::class);
});

it('forbids walking a Completed amendment back to Active individually', function (): void {
    $this->actingAs(budgetManager());
    $parent = BudgetPlan::factory()->create(['state' => Active::class]);
    $amendment = nhhpActiveAmendment($parent);
    $parent->state->transitionTo(Completed::class); // cascades the amendment to Completed

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('newState', 'active')
        ->call('changeState')
        ->assertForbidden();

    expect($amendment->fresh()->state)->toBeInstanceOf(Completed::class);
});

it('still allows an amendment\'s individual Approved -> Active move (apply) through the Livewire flow', function (): void {
    $this->actingAs(budgetManager());
    $parent = BudgetPlan::factory()->create(['state' => Active::class]);
    $amendment = BudgetPlan::create([
        'state' => Draft::class,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
    ]);
    $amendment->state->transitionTo(Resolved::class);
    $amendment->state->transitionTo(Approved::class);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('newState', 'active')
        ->call('changeState')
        ->assertHasNoErrors();

    expect($amendment->fresh()->state)->toBeInstanceOf(Active::class);
});

it('still allows an amendment\'s individual Active -> Approved move (revert) through the Livewire flow', function (): void {
    $this->actingAs(budgetManager());
    $parent = BudgetPlan::factory()->create(['state' => Active::class]);
    $amendment = nhhpActiveAmendment($parent);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->set('newState', 'approved')
        ->call('changeState')
        ->assertHasNoErrors();

    expect($amendment->fresh()->state)->toBeInstanceOf(Approved::class);
});

it('does not offer the Active <-> Completed option as usable for an amendment in the state-modal dropdown', function (): void {
    $this->actingAs(budgetManager());
    $parent = BudgetPlan::factory()->create(['state' => Active::class]);
    $amendment = nhhpActiveAmendment($parent);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])->html();

    // the "completed" option is rendered (disable, don't hide) but must carry Flux's disabled marker
    $pos = strpos($html, 'value="completed"');
    expect($pos)->not->toBeFalse();
    expect(substr($html, $pos, 1500))->toContain('disabled');
});
