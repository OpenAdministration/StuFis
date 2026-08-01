<?php

use App\Models\BudgetItem;
use App\Models\BudgetItemChange;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Approved;
use App\States\BudgetPlan\Draft;
use App\States\BudgetPlan\Resolved;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * Area H (light) of the OP#581 test plan: the amendment's own plan-view renders a diff of
 * changed items only (from -> to, with reasons) instead of the full merged tree.
 *
 * History ("Stand zum Datum X" — reconstructing a plan's pre-amendment state) was NOT
 * implemented (left as a TODO per the implementing agent's report), so that half of area H is
 * intentionally not covered here.
 */
uses(DatabaseTransactions::class);

function nhhpDiffParent(): array
{
    $plan = BudgetPlan::factory()->create(['state' => Active::class]);
    $leaf = $plan->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Material', 'value' => Money::EUR(10000),
    ]);
    $untouched = $plan->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 1,
        'short_name' => 'A2', 'name' => 'Unveraendert', 'value' => Money::EUR(2000),
    ]);

    return [$plan, $leaf, $untouched];
}

function nhhpDiffAmendment(BudgetPlan $parent): BudgetPlan
{
    return BudgetPlan::create([
        'state' => Draft::class,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
    ]);
}

it('shows only changed items on the amendment plan-view, with from->to and the recorded reason', function (): void {
    $this->actingAs(user());
    [$parent, $leaf, $untouched] = nhhpDiffParent();
    $amendment = nhhpDiffAmendment($parent);

    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $leaf->id,
        'action' => BudgetItemChange::ACTION_MODIFY,
        'diff' => ['value' => ['from' => 10000, 'to' => 15000]],
        'reason' => 'Preissteigerung beim Lieferanten',
    ]);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])->html();

    expect($html)->toContain(__('budget-plan.amendment.diff-heading'))
        ->and($html)->toContain(__('budget-plan.amendment.change.modify'))
        ->and($html)->toContain('100,00')
        ->and($html)->toContain('150,00')
        ->and($html)->toContain('Preissteigerung beim Lieferanten')
        ->and($html)->toContain('A1')
        // the untouched item never appears — only the diff is rendered, not the full tree
        ->and($html)->not->toContain('Unveraendert');
});

it('shows added and deleted items on the diff with their action badges', function (): void {
    $this->actingAs(user());
    [$parent, , $untouched] = nhhpDiffParent();
    $amendment = nhhpDiffAmendment($parent);

    $added = BudgetItem::create([
        'budget_plan_id' => $amendment->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
        'position' => 9, 'short_name' => 'A9', 'name' => 'Neuer Titel', 'value' => Money::EUR(500),
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $added->id,
        'action' => BudgetItemChange::ACTION_ADD,
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $untouched->id,
        'action' => BudgetItemChange::ACTION_DELETE,
    ]);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])->html();

    expect($html)->toContain(__('budget-plan.amendment.change.add'))
        ->and($html)->toContain('Neuer Titel')
        ->and($html)->toContain(__('budget-plan.amendment.change.delete'))
        ->and($html)->toContain('Unveraendert');
});

it('shows a placeholder when the amendment has no changes yet', function (): void {
    $this->actingAs(user());
    [$parent] = nhhpDiffParent();
    $amendment = nhhpDiffAmendment($parent);

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])
        ->assertSee(__('budget-plan.amendment.no-changes-yet'));
});

/**
 * B3 (OP#581 manual-test finding): approval_date/effective_date had no editing affordance
 * anywhere — plan-view only ever displayed them, and ⚡plan-edit refuses amendments outright.
 * plan-view now doubles as the only place a budget officer can set them, while the amendment is
 * still Draft..Approved; it freezes to a read-only <dd> once Active (the applier has already
 * consumed them by then) or for a user without update rights.
 */
it('lets a budget officer set the amendment approval and effective dates from its plan-view, while Draft', function (): void {
    $this->actingAs(budgetManager());
    [$parent] = nhhpDiffParent();
    $amendment = nhhpDiffAmendment($parent);

    $lw = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id]);
    $lw->set('approval_date', '2026-05-01')->assertHasNoErrors();
    $lw->set('effective_date', '2026-06-01')->assertHasNoErrors();

    $amendment->refresh();
    expect($amendment->approval_date->format('Y-m-d'))->toBe('2026-05-01')
        ->and($amendment->effective_date->format('Y-m-d'))->toBe('2026-06-01');

    // rendered as live inputs (not a frozen <dd>) while still editable
    $html = $lw->html();
    expect($html)->toContain('wire:model.live.blur="approval_date"')
        ->and($html)->toContain('wire:model.live.blur="effective_date"');
});

it('also allows setting the dates while Approved, but freezes them once the amendment goes Active', function (): void {
    $this->actingAs(budgetManager());
    [$parent] = nhhpDiffParent();
    $amendment = nhhpDiffAmendment($parent);
    $amendment->state->transitionTo(Resolved::class);
    $amendment->state->transitionTo(Approved::class);

    $lw = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id]);
    $lw->set('approval_date', '2026-05-01')->assertHasNoErrors();
    expect($amendment->refresh()->approval_date->format('Y-m-d'))->toBe('2026-05-01');

    // $parent (from nhhpDiffParent()) is already Active — the amendment can now apply onto it
    $amendment->state->transitionTo(Active::class);

    $lw2 = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id]);
    $lw2->set('approval_date', '2099-01-01'); // an attempted edit must be silently refused server-side

    expect($amendment->refresh()->approval_date->format('Y-m-d'))->toBe('2026-05-01')
        ->and($lw2->html())->not->toContain('wire:model.live.blur="approval_date"')
        ->and($lw2->html())->toContain($amendment->approval_date->format('d.m.Y'));
});

it('shows the dates read-only to a user without budget-officer rights, even while Draft', function (): void {
    $this->actingAs(user());
    [$parent] = nhhpDiffParent();
    $amendment = nhhpDiffAmendment($parent);
    $amendment->update(['approval_date' => '2026-05-01']);

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $amendment->id])->html();

    expect($html)->not->toContain('wire:model.live.blur="approval_date"')
        ->and($html)->toContain('01.05.2026'); // d.m.Y display of the seeded 2026-05-01
});
