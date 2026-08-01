<?php

use App\Models\BudgetItem;
use App\Models\BudgetItemChange;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\Legacy\Booking;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Approved;
use App\States\BudgetPlan\Draft;
use App\States\BudgetPlan\Resolved;
use App\Support\Budget\AmendmentApplier;
use App\Support\Budget\AmendmentConflictException;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * The AmendmentApplier core — apply() runs on the amendment's Approved -> Active transition (see
 * ApplyAmendmentTransition). Every touched item is verified against the value it had when the
 * change was drafted before ANY write happens, so a single stale/rechecked-invalid item aborts
 * the whole batch atomically. Mirrors area D of the OP#581 test plan.
 */
uses(DatabaseTransactions::class);

/** An Active parent plan with an expense group A1 and two leaves A1.1 (100€) / A1.2 (50€). */
function nhhpApplyParent(): array
{
    $plan = BudgetPlan::factory()->create(['state' => Active::class]);
    $group = $plan->budgetItems()->create([
        'is_group' => true, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Ausgaben', 'value' => Money::EUR(15000),
    ]);
    $leaf1 = $group->children()->create([
        'budget_plan_id' => $plan->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
        'position' => 0, 'short_name' => 'A1.1', 'name' => 'Material', 'value' => Money::EUR(10000),
    ]);
    $leaf2 = $group->children()->create([
        'budget_plan_id' => $plan->id, 'is_group' => false, 'budget_type' => BudgetType::EXPENSE,
        'position' => 1, 'short_name' => 'A1.2', 'name' => 'Fahrtkosten', 'value' => Money::EUR(5000),
    ]);

    return [$plan, $group, $leaf1, $leaf2];
}

/** A Draft amendment against $parent, immediately advanced to Approved (no items touched yet). */
function nhhpApprovedAmendment(BudgetPlan $parent): BudgetPlan
{
    $amendment = BudgetPlan::create([
        'state' => Draft::class,
        'organization' => $parent->organization,
        'fiscal_year_id' => $parent->fiscal_year_id,
        'parent_plan_id' => $parent->id,
    ]);
    $amendment->state->transitionTo(Resolved::class);
    $amendment->state->transitionTo(Approved::class);

    return $amendment->fresh();
}

/** Directly draft a modify change on $item (bypassing the editor) — from its live value to $toCents. */
function nhhpModify(BudgetPlan $amendment, BudgetItem $item, int $toCents, string $field = 'value'): BudgetItemChange
{
    $from = $field === 'value' ? (int) $item->value->getAmount() : $item->getAttribute($field);

    return BudgetItemChange::create([
        'budget_plan_id' => $amendment->id,
        'budget_item_id' => $item->id,
        'action' => BudgetItemChange::ACTION_MODIFY,
        'changes' => [$field => ['from' => $from, 'to' => $toCents]],
    ]);
}

/** Directly draft an addition under $amendment (a real budget_item owned by the amendment) + its add row. */
function nhhpAdd(BudgetPlan $amendment, BudgetItem $parentGroup): BudgetItem
{
    $newItem = BudgetItem::create([
        'budget_plan_id' => $amendment->id, 'parent_id' => $parentGroup->id, 'is_group' => false,
        'budget_type' => BudgetType::EXPENSE, 'position' => 9, 'short_name' => 'A1.9', 'name' => 'Neu',
        'value' => Money::EUR(2000),
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $newItem->id,
        'action' => BudgetItemChange::ACTION_ADD,
    ]);

    return $newItem;
}

/** Directly draft a deletion of a live (parent-plan) item. */
function nhhpMarkDelete(BudgetPlan $amendment, BudgetItem $item): BudgetItemChange
{
    return BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $item->id,
        'action' => BudgetItemChange::ACTION_DELETE,
    ]);
}

/** Books against a leaf, wiring the (zahlung_id, zahlung_type) FK via a throwaway konto transaction. */
function nhhpBookLeaf(BudgetItem $leaf): Booking
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

it('applies modify/add/delete in one pass: modify writes to-values onto the same item ids, add re-homes, delete parks', function (): void {
    [$parent, $group, $leaf1, $leaf2] = nhhpApplyParent();
    $amendment = nhhpApprovedAmendment($parent);

    nhhpModify($amendment, $leaf1, 20000); // A1.1: 100 -> 200
    $added = nhhpAdd($amendment, $group);
    nhhpMarkDelete($amendment, $leaf2);

    resolve(AmendmentApplier::class)->apply($amendment);

    // modify: same id, value/name/position/parent updated in place
    $freshLeaf1 = $leaf1->fresh();
    expect($freshLeaf1->id)->toBe($leaf1->id)
        ->and((int) $freshLeaf1->value->getAmount())->toBe(20000)
        ->and($freshLeaf1->budget_plan_id)->toBe($parent->id);

    // add: re-homed from the amendment to the parent plan, same id
    expect($added->fresh()->budget_plan_id)->toBe($parent->id);

    // delete: parked under the amendment plan, row still exists, id unchanged
    $freshLeaf2 = $leaf2->fresh();
    expect($freshLeaf2)->not->toBeNull()
        ->and($freshLeaf2->id)->toBe($leaf2->id)
        ->and($freshLeaf2->budget_plan_id)->toBe($amendment->id);
});

it('leaves the amendment Active and the parent plan state untouched after apply', function (): void {
    [$parent, , $leaf1] = nhhpApplyParent();
    $amendment = nhhpApprovedAmendment($parent);
    nhhpModify($amendment, $leaf1, 12000);

    $amendment->state->transitionTo(Active::class);

    expect($amendment->fresh()->state)->toBeInstanceOf(Active::class)
        ->and($parent->fresh()->state)->toBeInstanceOf(Active::class);
});

it('keeps bookings pointed at a modified item, IST sums unchanged, after apply', function (): void {
    [$parent, , $leaf1] = nhhpApplyParent();
    nhhpBookLeaf($leaf1);
    $amendment = nhhpApprovedAmendment($parent);
    nhhpModify($amendment, $leaf1, 30000);

    resolve(AmendmentApplier::class)->apply($amendment);

    expect($leaf1->fresh()->hasBookings())->toBeTrue()
        ->and(Booking::where('titel_id', $leaf1->id)->sum('value'))->toEqual(10.0);
});

it('rejects a stale modify (live value drifted since drafting) atomically: no partial writes, state stays Approved', function (): void {
    [$parent, , $leaf1, $leaf2] = nhhpApplyParent();
    $amendment = nhhpApprovedAmendment($parent);
    nhhpModify($amendment, $leaf1, 20000);
    nhhpModify($amendment, $leaf2, 8000); // a second, otherwise-valid change in the same batch

    // someone books/edits leaf1's value directly after the change was drafted
    $leaf1->update(['value' => Money::EUR(11111)]);

    expect(fn () => $amendment->state->transitionTo(Active::class))
        ->toThrow(AmendmentConflictException::class);

    expect($amendment->fresh()->state)->toBeInstanceOf(Approved::class)
        // neither change was applied — not even the one that was individually still valid
        ->and((int) $leaf1->fresh()->value->getAmount())->toBe(11111)
        ->and((int) $leaf2->fresh()->value->getAmount())->toBe(5000);
});

it('re-checks deletability at apply time: a since-booked item aborts the whole batch, unparked and unharmed', function (): void {
    [$parent, , $leaf1, $leaf2] = nhhpApplyParent();
    $amendment = nhhpApprovedAmendment($parent);
    nhhpMarkDelete($amendment, $leaf2); // unbooked at drafting time
    nhhpModify($amendment, $leaf1, 20000); // another change in the same batch

    // a booking appears directly on the to-be-deleted item after drafting
    $booking = nhhpBookLeaf($leaf2);

    expect(fn () => $amendment->state->transitionTo(Active::class))
        ->toThrow(AmendmentConflictException::class);

    expect($amendment->fresh()->state)->toBeInstanceOf(Approved::class)
        // the item was NOT parked under the amendment
        ->and($leaf2->fresh()->budget_plan_id)->toBe($parent->id)
        // the booking still points at it, unharmed
        ->and(Booking::find($booking->id)?->titel_id)->toBe($leaf2->id)
        // the other, individually-valid change was not applied either
        ->and((int) $leaf1->fresh()->value->getAmount())->toBe(10000);
});

it('fails cleanly when applying while the parent plan is not Active', function (): void {
    $parent = BudgetPlan::factory()->create(['state' => Draft::class]);
    $leaf = $parent->budgetItems()->create([
        'is_group' => false, 'budget_type' => BudgetType::EXPENSE, 'position' => 0,
        'short_name' => 'A1', 'name' => 'Material', 'value' => Money::EUR(10000),
    ]);
    $amendment = nhhpApprovedAmendment($parent);
    nhhpModify($amendment, $leaf, 20000);

    expect(fn () => $amendment->state->transitionTo(Active::class))
        ->toThrow(AmendmentConflictException::class);

    expect($amendment->fresh()->state)->toBeInstanceOf(Approved::class)
        ->and((int) $leaf->fresh()->value->getAmount())->toBe(10000);
});

it('compares an integer-cents "from" semantically against the live Money value (no false conflict)', function (): void {
    [$parent, , $leaf1] = nhhpApplyParent();
    $amendment = nhhpApprovedAmendment($parent);

    // simulate a change row whose "from" round-tripped through JSON as a numeric STRING rather
    // than an int — fieldsEqual() must still compare it against the live Money cents semantically
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $leaf1->id,
        'action' => BudgetItemChange::ACTION_MODIFY,
        'changes' => ['value' => ['from' => (string) $leaf1->value->getAmount(), 'to' => 20000]],
    ]);

    resolve(AmendmentApplier::class)->apply($amendment);

    expect((int) $leaf1->fresh()->value->getAmount())->toBe(20000);
});

it('lets two parallel amendments on the SAME item apply the first and conflict the second', function (): void {
    [$parent, , $leaf1] = nhhpApplyParent();
    $first = nhhpApprovedAmendment($parent);
    $second = nhhpApprovedAmendment($parent);
    nhhpModify($first, $leaf1, 20000);
    nhhpModify($second, $leaf1, 30000); // drafted against the same original (100€) base value

    $first->state->transitionTo(Active::class);
    expect((int) $leaf1->fresh()->value->getAmount())->toBe(20000);

    expect(fn () => $second->state->transitionTo(Active::class))
        ->toThrow(AmendmentConflictException::class);
    expect($second->fresh()->state)->toBeInstanceOf(Approved::class)
        ->and((int) $leaf1->fresh()->value->getAmount())->toBe(20000); // unchanged by the rejected second
});

it('lets two parallel amendments on DIFFERENT items both apply cleanly in sequence', function (): void {
    [$parent, , $leaf1, $leaf2] = nhhpApplyParent();
    $first = nhhpApprovedAmendment($parent);
    $second = nhhpApprovedAmendment($parent);
    nhhpModify($first, $leaf1, 20000);
    nhhpModify($second, $leaf2, 8000);

    $first->state->transitionTo(Active::class);
    $second->state->transitionTo(Active::class);

    expect($first->fresh()->state)->toBeInstanceOf(Active::class)
        ->and($second->fresh()->state)->toBeInstanceOf(Active::class)
        ->and((int) $leaf1->fresh()->value->getAmount())->toBe(20000)
        ->and((int) $leaf2->fresh()->value->getAmount())->toBe(8000);
});

it('runs no applier for a normal (non-amendment) plan\'s Approved -> Active transition', function (): void {
    $plan = BudgetPlan::factory()->create(['state' => Draft::class]);
    $plan->state->transitionTo(Resolved::class);
    $plan->state->transitionTo(Approved::class);

    $plan->state->transitionTo(Active::class);

    expect($plan->fresh()->state)->toBeInstanceOf(Active::class);
});
