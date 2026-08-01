<?php

use App\Models\BudgetItem;
use App\Models\BudgetItemChange;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\Legacy\Booking;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Draft;
use App\States\BudgetPlan\Resolved;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * The ⚡amendment-edit change-set editor: every edit against a base (parent-plan) item is
 * recorded as a `budget_item_change` row instead of writing the live budget_item, while an
 * amendment's own additions are written to directly (same as a normal draft plan). Mirrors the
 * area-C cases of the OP#581 test plan.
 */
uses(DatabaseTransactions::class);

/** An Active parent plan with one expense group + one leaf ('A1' / 'A1.1', 100 EUR). */
function nhhpParentWithLeaf(): array
{
    $plan = BudgetPlan::factory()->create(['state' => Active::class]);
    $group = $plan->budgetItems()->create([
        'is_group' => true, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Ausgaben', 'value' => Money::EUR(10000),
    ]);
    $leaf = $group->children()->create([
        'budget_plan_id' => $plan->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
        'position' => 0, 'short_name' => 'A1.1', 'name' => 'Material', 'value' => Money::EUR(10000),
    ]);

    return [$plan, $group, $leaf];
}

function nhhpDraftAmendment(BudgetPlan $parent): BudgetPlan
{
    return BudgetPlan::create([
        'state' => Draft::class,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
    ]);
}

function nhhpEditComponent(BudgetPlan $parent, BudgetPlan $amendment)
{
    return Livewire::test('pages::budget-plan.amendment-edit', ['plan_id' => $parent->id, 'amendment_id' => $amendment->id]);
}

/** Books against a leaf, wiring the (zahlung_id, zahlung_type) FK via a throwaway konto transaction. */
function nhhpEditBookLeaf(BudgetItem $leaf): Booking
{
    $account = BankAccount::factory()->create();
    BankTransaction::factory()->create(['konto_id' => $account->id, 'id' => 1]);

    return Booking::create([
        'titel_id' => $leaf->id,
        'user_id' => user()->id,
        'kostenstelle' => 0,
        'zahlung_id' => 1,
        'zahlung_type' => $account->id,
        'beleg_id' => 0,
        'beleg_type' => '',
        'comment' => 'Buchung',
        'value' => '10',
        'canceled' => 0,
    ]);
}

it('records a modify change row when editing a base item value, leaving the live row untouched', function (): void {
    $this->actingAs(budgetManager());
    [$parent, , $leaf] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);

    nhhpEditComponent($parent, $amendment)
        ->set('items.'.$leaf->id.'.value', Money::EUR(15000))
        ->assertHasNoErrors();

    $change = BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->sole();
    expect($change->action)->toBe(BudgetItemChange::ACTION_MODIFY)
        ->and((int) $change->changes['value']['from'])->toBe(10000)
        ->and((int) $change->changes['value']['to'])->toBe(15000);

    // the live parent-plan item is untouched
    expect((int) $leaf->fresh()->value->getAmount())->toBe(10000);
});

it('updates "to" on a second edit of the same field while keeping the original "from"', function (): void {
    $this->actingAs(budgetManager());
    [$parent, , $leaf] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);
    $lw = nhhpEditComponent($parent, $amendment);

    $lw->set('items.'.$leaf->id.'.value', Money::EUR(15000));
    $lw->set('items.'.$leaf->id.'.value', Money::EUR(20000));

    $change = BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->sole();
    expect((int) $change->changes['value']['from'])->toBe(10000)
        ->and((int) $change->changes['value']['to'])->toBe(20000);
});

it('drops the field (and the whole row once empty) when a value is reverted back to base', function (): void {
    $this->actingAs(budgetManager());
    [$parent, , $leaf] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);
    $lw = nhhpEditComponent($parent, $amendment);

    $lw->set('items.'.$leaf->id.'.value', Money::EUR(15000));
    expect(BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->exists())->toBeTrue();

    // revert back to the base (live) value of 100 EUR
    $lw->set('items.'.$leaf->id.'.value', Money::EUR(10000));

    expect(BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->exists())->toBeFalse();
});

it('accumulates multiple field edits on one item into a single change row', function (): void {
    $this->actingAs(budgetManager());
    [$parent, , $leaf] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);
    $lw = nhhpEditComponent($parent, $amendment);

    $lw->set('items.'.$leaf->id.'.value', Money::EUR(15000));
    $lw->set('items.'.$leaf->id.'.name', 'Neues Material');

    // the unique (budget_plan_id, budget_item_id) constraint means both edits share one row
    $rows = BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->get();
    expect($rows)->toHaveCount(1);
    expect($rows->first()->changes)->toHaveKeys(['value', 'name']);
});

it('creates a real budget_item under the amendment plus an add change row when a budget line is added', function (): void {
    $this->actingAs(budgetManager());
    [$parent, $group] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);

    nhhpEditComponent($parent, $amendment)
        ->call('addBudget', $group->id)
        ->assertHasNoErrors();

    $newItem = BudgetItem::where('budget_plan_id', $amendment->id)->where('parent_id', $group->id)->sole();
    $change = BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $newItem->id)->sole();
    expect($change->action)->toBe(BudgetItemChange::ACTION_ADD);
});

it('parks an unbooked base item for deletion without touching it, but refuses deletion for a booked item', function (): void {
    $this->actingAs(budgetManager());
    [$parent, $group, $leaf] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);
    $lw = nhhpEditComponent($parent, $amendment);

    $lw->call('delete', $leaf->id)->assertHasNoErrors();

    $change = BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->sole();
    expect($change->action)->toBe(BudgetItemChange::ACTION_DELETE);
    // the item itself is untouched — still live under the parent plan
    expect($leaf->fresh()->budget_plan_id)->toBe($parent->id);

    // undo puts it back into the normal (unmarked) state
    $lw->call('undoDelete', $leaf->id)->assertHasNoErrors();
    expect(BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->exists())->toBeFalse();

    // now book against the leaf and try again: deletion must be refused
    nhhpEditBookLeaf($leaf);
    $lw->call('delete', $leaf->id)->assertHasNoErrors();
    expect(BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->exists())->toBeFalse();
});

it('deletes an amendment-added item outright (item + change row), no delete-row parking', function (): void {
    $this->actingAs(budgetManager());
    [$parent, $group] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);
    $lw = nhhpEditComponent($parent, $amendment);

    $lw->call('addBudget', $group->id);
    $newItem = BudgetItem::where('budget_plan_id', $amendment->id)->where('parent_id', $group->id)->sole();

    $lw->call('delete', $newItem->id)->assertHasNoErrors();

    expect(BudgetItem::find($newItem->id))->toBeNull()
        ->and(BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $newItem->id)->exists())->toBeFalse();
});

it('records reordering of a base item as a position change without touching the live row', function (): void {
    $this->actingAs(budgetManager());
    [$parent, $group, $leaf] = nhhpParentWithLeaf();
    $sibling = $group->children()->create([
        'budget_plan_id' => $parent->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
        'position' => 1, 'short_name' => 'A1.2', 'name' => 'Sonstiges', 'value' => Money::EUR(0),
    ]);
    $amendment = nhhpDraftAmendment($parent);

    nhhpEditComponent($parent, $amendment)
        ->call('sort', $leaf->id, 1)
        ->assertHasNoErrors();

    $leafChange = BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->first();
    $siblingChange = BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $sibling->id)->first();

    expect($leafChange?->changes['position']['to'] ?? null)->toBe(1)
        ->and($siblingChange?->changes['position']['to'] ?? null)->toBe(0);

    // live rows never moved
    expect($leaf->fresh()->position)->toBe(0)
        ->and($sibling->fresh()->position)->toBe(1);
});

it('persists per-change reasons and the plan justification', function (): void {
    $this->actingAs(budgetManager());
    [$parent, , $leaf] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);
    $lw = nhhpEditComponent($parent, $amendment);

    $lw->set('items.'.$leaf->id.'.value', Money::EUR(15000));
    $change = BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->sole();

    $lw->set('justification', 'Mehrbedarf wegen gestiegener Materialkosten');
    $lw->set('reasonInputs.'.$change->id, 'Preissteigerung beim Lieferanten');

    expect($amendment->fresh()->justification)->toBe('Mehrbedarf wegen gestiegener Materialkosten')
        ->and($change->fresh()->reason)->toBe('Preissteigerung beim Lieferanten');
});

it('is only reachable while the amendment is still Draft, redirecting elsewhere', function (): void {
    $this->actingAs(budgetManager());
    [$parent] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);
    $amendment->state->transitionTo(Resolved::class);

    nhhpEditComponent($parent, $amendment)
        ->assertRedirect(route('budget-plan.view', $amendment->id));
});

it('404s the editor when the amendment does not belong to the given parent plan', function (): void {
    $this->actingAs(budgetManager());
    [$parent] = nhhpParentWithLeaf();
    [$otherParent] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($otherParent);

    nhhpEditComponent($parent, $amendment)->assertNotFound();
});

it('shows "Nachtrag erstellen" enabled on an Active plan and disabled otherwise, creating a Draft amendment on click', function (): void {
    $this->actingAs(budgetManager());
    [$parent] = nhhpParentWithLeaf(); // Active

    Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $parent->id])
        ->assertSee(__('budget-plan.amendment.create'))
        ->call('createAmendment')
        ->assertHasNoErrors();

    $amendment = BudgetPlan::query()->whereNotNull('parent_plan_id')->where('parent_plan_id', $parent->id)->sole();
    expect($amendment->state)->toBeInstanceOf(Draft::class)
        ->and($amendment->organization)->toBe($parent->organization)
        ->and($amendment->fiscal_year_id)->toBe($parent->fiscal_year_id);

    // a Draft (non-Active) plan must render the button disabled rather than hide it
    $draftPlan = BudgetPlan::create(['state' => Draft::class]);
    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $draftPlan->id])->html();
    expect($html)->toContain(__('budget-plan.amendment.create'))
        ->and($html)->toContain('disabled');
});
