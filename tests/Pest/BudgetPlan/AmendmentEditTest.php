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
        ->and((int) $change->diff['value']['from'])->toBe(10000)
        ->and((int) $change->diff['value']['to'])->toBe(15000);

    // the live parent-plan item is untouched
    expect((int) $leaf->fresh()->value->getAmount())->toBe(10000);
});

it('reflects an edited value back into the titles-table form and the group sum after a real browser round-trip', function (): void {
    // Reproduces the manual-test bug (B1): editing a value showed up correctly in the
    // Begründungen tab, but the titles-table input snapped back to the old value. That symptom
    // came from a naming collision — the JSON column used to be called `changes`, and
    // BudgetItemChange::fieldChange() read `$this->changes` from INSIDE the model, which
    // resolved to Eloquent's OWN internal dirty-tracking property of that exact name instead of
    // our column. Blade/AmendmentApplier accessed `$change->changes` from OUTSIDE the model, so
    // they went through the magic accessor and saw the correct value — only loadItems()'s
    // internal fieldChange() calls were broken. The column has since been renamed to `diff`
    // (see BudgetItemChange's class docblock), which removes the collision structurally; this
    // regression test is kept to guard the observable symptom either way.
    //
    // Passing a plain string mirrors what a real browser sends across the wire for the input: on
    // blur, `x-money-input` (a plain flux:input) posts back the literal (edited) text content of
    // the field — MoneySynth::hydrate() then converts it into a Money instance before this
    // component's updatedItems() ever sees it.
    $this->actingAs(budgetManager());
    [$parent, $group, $leaf] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);

    $lw = nhhpEditComponent($parent, $amendment)
        ->set('items.'.$leaf->id.'.value', '300')
        ->assertHasNoErrors();

    $change = BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->sole();
    expect((int) $change->diff['value']['to'])->toBe(30000); // 300,00 € as cents, not 300 cents

    // the re-rendered titles-table input must show the NEW value, not fall back to the old one
    $lw->assertSet('items.'.$leaf->id.'.value', Money::EUR(30000));

    // the group sum (100 -> group had only this leaf) must reflect the edit too
    expect($lw->html())->toContain(Money::EUR(30000)->format());
});

it('converts every euro-decimal input format the browser could send into the correct integer cents', function (string $input, int $expectedCents): void {
    $this->actingAs(budgetManager());
    [$parent, , $leaf] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);

    nhhpEditComponent($parent, $amendment)
        ->set('items.'.$leaf->id.'.value', $input)
        ->assertHasNoErrors();

    $change = BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->sole();
    expect((int) $change->diff['value']['to'])->toBe($expectedCents);
})->with([
    'plain integer' => ['300', 30000],
    'decimal comma' => ['300,50', 30050],
    'thousands separator' => ['1.500,00', 150000],
    'formatted with euro sign' => ['152,05 €', 15205],
]);

it('updates "to" on a second edit of the same field while keeping the original "from"', function (): void {
    $this->actingAs(budgetManager());
    [$parent, , $leaf] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);
    $lw = nhhpEditComponent($parent, $amendment);

    $lw->set('items.'.$leaf->id.'.value', Money::EUR(15000));
    $lw->set('items.'.$leaf->id.'.value', Money::EUR(20000));

    $change = BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->sole();
    expect((int) $change->diff['value']['from'])->toBe(10000)
        ->and((int) $change->diff['value']['to'])->toBe(20000);
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
    expect($rows->first()->diff)->toHaveKeys(['value', 'name']);
});

it('rings only the specific field that changed (F1), not the untouched sibling field', function (): void {
    $this->actingAs(budgetManager());
    [$parent, , $leaf] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);
    $lw = nhhpEditComponent($parent, $amendment);

    // only `value` is touched — `name` must NOT get the amber ring
    $lw->set('items.'.$leaf->id.'.value', Money::EUR(15000))->assertHasNoErrors();

    $html = $lw->html();
    expect($html)->toContain('ring-amber-400')
        ->and($html)->toContain(__('budget-plan.amendment.field-was', ['value' => Money::EUR(10000)->format()]));

    // now also touch `name` — both fields ring, each showing its OWN old value
    $lw->set('items.'.$leaf->id.'.name', 'Neues Material')->assertHasNoErrors();
    $html = $lw->html();
    expect($html)->toContain(__('budget-plan.amendment.field-was', ['value' => 'Material']))
        ->and(substr_count((string) $html, 'ring-amber-400'))->toBe(2);
});

it('refuses to record a short_name change for a base item (F2), but still accepts it for the amendment\'s own additions', function (): void {
    $this->actingAs(budgetManager());
    [$parent, $group, $leaf] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);
    $lw = nhhpEditComponent($parent, $amendment);

    $lw->set('items.'.$leaf->id.'.short_name', 'A9.9')->assertHasNoErrors();

    expect(BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->exists())->toBeFalse()
        ->and($leaf->fresh()->short_name)->toBe('A1.1');

    // the base item's Titelnummer input is not wired for editing at all (no wire:model)
    expect($lw->html())->not->toContain('wire:model.live.blur="items.'.$leaf->id.'.short_name"');

    // an item the amendment itself added is a real row under the amendment plan — short_name stays editable
    $lw->call('addBudget', $group->id);
    $newItem = BudgetItem::where('budget_plan_id', $amendment->id)->where('parent_id', $group->id)->sole();

    $lw->set('items.'.$newItem->id.'.short_name', 'A1.9')->assertHasNoErrors();
    expect($newItem->fresh()->short_name)->toBe('A1.9');
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

    $lw->call('deleteItem', $leaf->id)->assertHasNoErrors();

    $change = BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->sole();
    expect($change->action)->toBe(BudgetItemChange::ACTION_DELETE);
    // the item itself is untouched — still live under the parent plan
    expect($leaf->fresh()->budget_plan_id)->toBe($parent->id);

    // undo puts it back into the normal (unmarked) state
    $lw->call('undoDelete', $leaf->id)->assertHasNoErrors();
    expect(BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->exists())->toBeFalse();

    // now book against the leaf and try again: deletion must be refused
    nhhpEditBookLeaf($leaf);
    $lw->call('deleteItem', $leaf->id)->assertHasNoErrors();
    expect(BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->exists())->toBeFalse();
});

it('gives every rendered row a stable wire:key so morphdom can tell rows apart (B2)', function (): void {
    // Root cause of B2 (deleting an unbooked base item did nothing visible): the recursive
    // <x-budgetplan.item-group-amendment :wire:key="..."> call passed a key, but the component's
    // own root <div> never echoes $attributes, so Blade silently drops it — every row rendered
    // with NO wire:key at all. Livewire's morphdom then had no reliable per-row identity, so the
    // isDeleted-driven swap (dropdown-menu -> undo-button, a structurally different subtree)
    // could get morph-patched onto the wrong sibling instead of the row that was actually deleted.
    $this->actingAs(budgetManager());
    [$parent, $group, $leaf] = nhhpParentWithLeaf();
    $sibling = $group->children()->create([
        'budget_plan_id' => $parent->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
        'position' => 1, 'short_name' => 'A1.2', 'name' => 'Sonstiges', 'value' => Money::EUR(0),
    ]);
    $amendment = nhhpDraftAmendment($parent);

    $html = nhhpEditComponent($parent, $amendment)->html();

    expect($html)->toContain('wire:key="budget-item-'.$leaf->id.'"')
        ->and($html)->toContain('wire:key="budget-item-'.$sibling->id.'"')
        ->and($html)->toContain('wire:key="budget-item-'.$group->id.'"');
});

it('shows the delete badge/undo affordance on the deleted row, not a sibling row, after clicking delete', function (): void {
    $this->actingAs(budgetManager());
    [$parent, $group, $leaf] = nhhpParentWithLeaf();
    $sibling = $group->children()->create([
        'budget_plan_id' => $parent->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
        'position' => 1, 'short_name' => 'A1.2', 'name' => 'Sonstiges', 'value' => Money::EUR(0),
    ]);
    $amendment = nhhpDraftAmendment($parent);
    $lw = nhhpEditComponent($parent, $amendment);

    $lw->call('deleteItem', $leaf->id)->assertHasNoErrors();

    $change = BudgetItemChange::where('budget_plan_id', $amendment->id)->where('budget_item_id', $leaf->id)->sole();
    expect($change->action)->toBe(BudgetItemChange::ACTION_DELETE);

    // the deleted row's own DOM node (identified by its now-stable wire:key) must carry the
    // undo affordance; the untouched sibling must not. Slice from each row's own wire:key up to
    // the next sibling's wire:key (or end of document) so the two rows can't bleed into each other.
    $html = $lw->html();
    $row = function (int $itemId) use ($html): string {
        $start = strpos((string) $html, 'wire:key="budget-item-'.$itemId.'"');
        $next = strpos((string) $html, 'wire:key="budget-item-', $start + 1);

        return substr((string) $html, $start, $next !== false ? $next - $start : null);
    };
    $leafRow = $row($leaf->id);
    $siblingRow = $row($sibling->id);

    expect($leafRow)->toContain('undoDelete('.$leaf->id.')')
        ->and($leafRow)->toContain(__('budget-plan.amendment.change.delete'))
        ->and($siblingRow)->not->toContain('undoDelete('.$leaf->id.')');
});

it('deletes an amendment-added item outright (item + change row), no delete-row parking', function (): void {
    $this->actingAs(budgetManager());
    [$parent, $group] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);
    $lw = nhhpEditComponent($parent, $amendment);

    $lw->call('addBudget', $group->id);
    $newItem = BudgetItem::where('budget_plan_id', $amendment->id)->where('parent_id', $group->id)->sole();

    $lw->call('deleteItem', $newItem->id)->assertHasNoErrors();

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

    expect($leafChange?->diff['position']['to'] ?? null)->toBe(1)
        ->and($siblingChange?->diff['position']['to'] ?? null)->toBe(0);

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

it('persists the amendment\'s optional name (F3) and shows it in the editor headline', function (): void {
    $this->actingAs(budgetManager());
    [$parent] = nhhpParentWithLeaf();
    $amendment = nhhpDraftAmendment($parent);
    $lw = nhhpEditComponent($parent, $amendment);

    $lw->set('name', 'Nachtrag Sommerfest')->assertHasNoErrors();

    expect($amendment->fresh()->name)->toBe('Nachtrag Sommerfest')
        ->and($lw->html())->toContain('Nachtrag Sommerfest');
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

    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $parent->id])
        ->assertSee(__('budget-plan.amendment.create'))
        ->call('createAmendment')
        ->assertHasNoErrors()
        ->html();

    // F7 (OP#581): "Nachtrag erstellen" lives in the actions dropdown as a real menu item now,
    // not a standalone header button — Flux renders every flux:menu.item with the
    // data-flux-menu-item marker, so its proximity to wire:click="createAmendment" pins the move
    // (a standalone flux:button never carries that marker).
    $pos = strpos((string) $html, 'wire:click="createAmendment"');
    expect($pos)->not->toBeFalse()
        ->and(substr((string) $html, max(0, $pos - 200), 200))->toContain('data-flux-menu-item');

    $amendment = BudgetPlan::query()->whereNotNull('parent_plan_id')->where('parent_plan_id', $parent->id)->sole();
    expect($amendment->state)->toBeInstanceOf(Draft::class)
        ->and($amendment->organization)->toBe($parent->organization)
        ->and($amendment->fiscal_year_id)->toBe($parent->fiscal_year_id);

    // a Draft (non-Active) plan must render the item disabled rather than hide it
    $draftPlan = BudgetPlan::create(['state' => Draft::class]);
    $html = Livewire::test('pages::budget-plan.plan-view', ['plan_id' => $draftPlan->id])->html();
    expect($html)->toContain(__('budget-plan.amendment.create'))
        ->and($html)->toContain('disabled');
});
