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
 * F4 (OP#589): an amendment may be drafted against an Approved plan as well as an Active one —
 * Approved is already a stable, agreed-upon document, so there's no reason to force waiting for
 * activation first. Covers both halves that must stay in sync: the can_create_amendment flag
 * (menu item enabled/disabled) and the abort_unless() server-side guard in createAmendment().
 */
uses(DatabaseTransactions::class);

it('allows creating an amendment while the plan is Approved or Active', function (string $stateClass): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => $stateClass]);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->assertSee(__('budget-plan.amendment.create'))
        ->call('createAmendment')
        ->assertHasNoErrors();

    expect(BudgetPlan::query()->whereNotNull('parent_plan_id')->where('parent_plan_id', $plan->id)->exists())->toBeTrue();
})->with([
    'approved' => [Approved::class],
    'active' => [Active::class],
]);

it('refuses creating an amendment while the plan is Draft, Resolved, or Completed', function (string $stateClass): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => $stateClass]);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->call('createAmendment')
        ->assertForbidden();

    expect(BudgetPlan::query()->whereNotNull('parent_plan_id')->where('parent_plan_id', $plan->id)->exists())->toBeFalse();
})->with([
    'draft' => [Draft::class],
    'resolved' => [Resolved::class],
    'completed' => [Completed::class],
]);

it('renders the create-amendment menu item disabled (not hidden) once the plan is past Approved/Active', function (): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => Completed::class]);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])->html();

    expect($html)->toContain(__('budget-plan.amendment.create'))
        ->and($html)->toContain(__('budget-plan.amendment.create-not-possible'));
});
