<?php

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\States\BudgetPlan\Draft;
use App\States\BudgetPlan\Resolved;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

function planWithItems(): BudgetPlan
{
    $plan = BudgetPlan::create(['state' => Draft::class]);
    $group = $plan->budgetItems()->create([
        'is_group' => true, 'budget_type' => BudgetType::INCOME, 'position' => 0,
        'short_name' => 'E1', 'name' => 'Einnahmen', 'value' => Money::EUR(100, true),
    ]);
    $group->children()->create([
        'budget_plan_id' => $plan->id, 'is_group' => false, 'budget_type' => BudgetType::INCOME,
        'position' => 0, 'short_name' => 'E1.1', 'name' => 'Beiträge', 'value' => Money::EUR(100, true),
    ]);

    return $plan;
}

it('renders the read-only view with real totals and item rows', function (): void {
    $this->actingAs(user());
    $plan = planWithItems();

    $this->get(route('budget-plan.view', $plan->id))
        ->assertOk()
        ->assertSee(__('budget-plan.view.summary.income'))
        ->assertSee(__('budget-plan.view.col.planned'))
        ->assertDontSee('budget-plan.view.')
        ->assertSee('E1.1')
        ->assertSee('100,00 €')
        ->assertDontSee('Avg. Open Rate')
        ->assertDontSee('Semesterbeiträge');
});

it('lets an admin delete the whole plan (with its items)', function (): void {
    $this->actingAs(adminUser());
    $plan = planWithItems();

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->call('deletePlan')
        ->assertHasNoErrors()
        ->assertRedirect(route('budget-plan.index'));

    expect(BudgetPlan::find($plan->id))->toBeNull()
        ->and(BudgetItem::where('budget_plan_id', $plan->id)->count())->toBe(0);
});

it('forbids a non-admin from deleting the plan', function (): void {
    $this->actingAs(budgetManager()); // budget-officer, not admin
    $plan = planWithItems();

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->call('deletePlan')
        ->assertForbidden();

    expect(BudgetPlan::find($plan->id))->not->toBeNull();
});

it('lets a budget officer advance the plan state along an allowed transition', function (): void {
    $this->actingAs(budgetManager()); // budget-officer
    $plan = planWithItems(); // starts as draft

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->set('newState', 'resolved')
        ->call('changeState')
        ->assertHasNoErrors();

    expect(BudgetPlan::find($plan->id)->state)->toBeInstanceOf(Resolved::class);
});

it('forbids a non-officer from changing the state', function (): void {
    $this->actingAs(user()); // not a budget officer
    $plan = planWithItems();

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->set('newState', 'resolved')
        ->call('changeState')
        ->assertForbidden();

    expect(BudgetPlan::find($plan->id)->state)->toBeInstanceOf(Draft::class);
});

it('forbids an illegal transition (draft straight to completed)', function (): void {
    $this->actingAs(budgetManager());
    $plan = planWithItems(); // draft

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])
        ->set('newState', 'completed')
        ->call('changeState')
        ->assertForbidden();

    expect(BudgetPlan::find($plan->id)->state)->toBeInstanceOf(Draft::class);
});
