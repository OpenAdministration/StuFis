<?php

use App\Models\BudgetItem;
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
 * F5 (OP#589), closing the intent of OP#583: deleting a plan takes a budget officer
 * (ref-finanzen-hv) AND a still-editable plan — only while BudgetPlan::isEditable()
 * (Draft/Resolved) may it be wiped outright. Past Approved the plan is meant to be a stable,
 * agreed-upon document.
 *
 * Both conditions live in BudgetPlanPolicy::delete(); deletePlan() authorizes against it rather
 * than re-deriving the rule, so the server-side guard cannot drift from the checklist rows the
 * delete-plan-modal shows (same pattern as ⚡show-project's delete-modal). Admins pass too, via
 * UserPolicy::before().
 */
uses(DatabaseTransactions::class);

function deletableStatePlan(string $stateClass): BudgetPlan
{
    $plan = BudgetPlan::create(['state' => $stateClass]);
    $plan->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::INCOME, 'position' => 0,
        'short_name' => 'E1', 'name' => 'Beitrag', 'value' => Money::EUR(100, true),
    ]);

    return $plan;
}

it('lets a budget officer delete the plan while Draft or Resolved', function (string $stateClass): void {
    $this->actingAs(budgetManager());
    $plan = deletableStatePlan($stateClass);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->call('deletePlan')
        ->assertHasNoErrors()
        ->assertRedirect(route('budget-plan.index'));

    expect(BudgetPlan::find($plan->id))->toBeNull()
        ->and(BudgetItem::where('budget_plan_id', $plan->id)->count())->toBe(0);
})->with([
    'draft' => [Draft::class],
    'resolved' => [Resolved::class],
]);

it('refuses deleting the plan once past Resolved, server-side, regardless of the modal', function (string $stateClass): void {
    $this->actingAs(budgetManager());
    $plan = deletableStatePlan($stateClass);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->call('deletePlan')
        ->assertForbidden();

    expect(BudgetPlan::find($plan->id))->not->toBeNull();
})->with([
    'approved' => [Approved::class],
    'active' => [Active::class],
    'completed' => [Completed::class],
]);

it('lets an admin delete an editable plan too, via UserPolicy::before()', function (): void {
    $this->actingAs(adminUser());
    $plan = deletableStatePlan(Draft::class);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->call('deletePlan')
        ->assertHasNoErrors()
        ->assertRedirect(route('budget-plan.index'));

    expect(BudgetPlan::find($plan->id))->toBeNull();
});

it('refuses a user without the budget-officer role, even on a Draft plan', function (): void {
    $this->actingAs(user());
    $plan = deletableStatePlan(Draft::class);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->call('deletePlan')
        ->assertForbidden();

    expect(BudgetPlan::find($plan->id))->not->toBeNull();
});

it('hides the delete action and its modal from a user without the budget-officer role', function (): void {
    $this->actingAs(user());
    $plan = deletableStatePlan(Draft::class);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])->html();

    expect($html)->not->toContain('delete-plan-modal');
});

it('renders the delete-plan-modal checklist rows and disables Confirm once the plan is past Resolved', function (): void {
    $this->actingAs(budgetManager());
    $plan = deletableStatePlan(Active::class);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])->html();

    expect($html)->toContain(__('budget-plan.view.delete-modal.conditions.role'))
        ->and($html)->toContain(__('budget-plan.view.delete-modal.conditions.editable-state', ['state' => $plan->state->label()]))
        // the editable-state row's condition failed -> its cross icon must render
        ->and($html)->toContain('fill-red-600');
});

it('renders the delete-plan-modal checklist with no cross icon while the plan is still Draft', function (): void {
    $this->actingAs(budgetManager());
    $plan = deletableStatePlan(Draft::class);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])->html();

    expect($html)->not->toContain('fill-red-600');
});
