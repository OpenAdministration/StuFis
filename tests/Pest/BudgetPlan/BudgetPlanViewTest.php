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

it('renders the collapse wiring: toggle on group rows and x-show on every row', function (): void {
    $this->actingAs(user());
    $plan = planWithItems();

    $group = $plan->budgetItems()->whereIsGroup(true)->firstOrFail();

    $html = $this->get(route('budget-plan.view', $plan->id))
        ->assertOk()
        ->assertSee('budget-collapse-'.$plan->id.'-in', false) // per-plan, per-tab persist key
        ->assertSee('isHidden(', false)                        // rows react to collapsed ancestors
        ->content();

    // the group row carries the click-to-toggle and its own id
    expect($html)->toContain('toggle('.$group->id.')');

    // the collapse-all / expand-all toolbar is wired to the shared Alpine scope
    expect($html)->toContain('collapseAll()')
        ->and($html)->toContain('expandAll()');
});

it('derives ancestor group ids from the adjacency-list path', function (): void {
    $plan = planWithItems();
    $leaf = $plan->budgetItemsTree(BudgetType::INCOME)->firstWhere('is_group', false);
    $group = $plan->budgetItemsTree(BudgetType::INCOME)->firstWhere('is_group', true);

    expect($leaf->ancestorIds())->toBe([$group->id])
        ->and($group->ancestorIds())->toBe([]); // root group has no ancestors
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
