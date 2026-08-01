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
 * F5 (OP#589), closing the intent of OP#583: deleting a plan must be admin-only AND state-gated —
 * only while BudgetPlan::isEditable() (Draft/Resolved) may it be wiped outright. Past Approved the
 * plan is meant to be a stable, agreed-upon document. The delete-plan-modal surfaces both
 * conditions as a checklist (same pattern as ⚡show-project's delete-modal); deletePlan() enforces
 * the state condition server-side too, not just via the disabled Confirm button.
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

it('lets an admin delete the plan while Draft or Resolved', function (string $stateClass): void {
    $this->actingAs(adminUser());
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

it('refuses an admin from deleting the plan once past Resolved, server-side, regardless of the modal', function (string $stateClass): void {
    $this->actingAs(adminUser());
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

it('renders the delete-plan-modal checklist rows and disables Confirm once the plan is past Resolved', function (): void {
    $this->actingAs(adminUser());
    $plan = deletableStatePlan(Active::class);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])->html();

    expect($html)->toContain(__('budget-plan.view.delete-modal.conditions.admin'))
        ->and($html)->toContain(__('budget-plan.view.delete-modal.conditions.editable-state', ['state' => $plan->state->label()]))
        // the editable-state row's condition failed -> its cross icon must render
        ->and($html)->toContain('fill-red-600');
});

it('renders the delete-plan-modal checklist with no cross icon while the plan is still Draft', function (): void {
    $this->actingAs(adminUser());
    $plan = deletableStatePlan(Draft::class);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])->html();

    expect($html)->not->toContain('fill-red-600');
});
