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

it('confirms the plan deletion through a flux modal instead of a native window.confirm', function (): void {
    $this->actingAs(adminUser());
    $plan = planWithItems();

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])->html();

    expect($html)->not->toContain('wire:confirm')
        ->and($html)->toContain('delete-plan-modal')
        ->and($html)->toContain(__('budget-plan.view.delete-modal.confirm'));
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

/**
 * F8 (OP#581): a normal plan's "Bearbeiten" must grey out from Approved onward — Approved is the
 * point past which the plan is meant to be a stable, agreed-upon document (Active/Completed only
 * ever come after Approved). Draft/Resolved stay editable. Server-side guard mirrors the button.
 */
it('renders "Bearbeiten" enabled in Draft/Resolved and disabled-with-tooltip from Approved onward', function (string $stateClass, bool $expectEditable): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => $stateClass]);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $plan->id])->html();

    $editHref = 'href="'.route('budget-plan.edit', $plan->id).'"';
    if ($expectEditable) {
        expect($html)->toContain($editHref);
    } else {
        expect($html)->not->toContain($editHref)
            ->and($html)->toContain(__('budget-plan.view.edit-not-possible', ['state' => $plan->state->label()]));
    }
})->with([
    'draft' => [Draft::class, true],
    'resolved' => [Resolved::class, true],
    'approved' => [Approved::class, false],
    'active' => [Active::class, false],
    'completed' => [Completed::class, false],
]);

it('refuses direct ⚡plan-edit access once Approved, redirecting to the read-only view (server-side guard matching the button)', function (): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => Approved::class]);

    Livewire::test('pages::budget-plan.plan-edit', ['plan_id' => $plan->id])
        ->assertRedirect(route('budget-plan.view', $plan->id));
});

it('still allows ⚡plan-edit access while Draft or Resolved', function (string $stateClass): void {
    $this->actingAs(budgetManager());
    $plan = BudgetPlan::create(['state' => $stateClass]);

    Livewire::test('pages::budget-plan.plan-edit', ['plan_id' => $plan->id])
        ->assertOk();
})->with([Draft::class, Resolved::class]);
