<?php

use App\Models\BudgetItem;
use App\Models\BudgetItemChange;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetItemChangeAction;
use App\Models\Enums\BudgetType;
use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\Legacy\Booking;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Approved;
use App\States\BudgetPlan\Draft;
use App\States\BudgetPlan\Resolved;
use App\Support\Budget\AmendmentConflictException;
use Cknow\Money\Money;
use Illuminate\Foundation\Testing\DatabaseTransactions;

/**
 * The AmendmentApplier's revert() half — runs on the amendment's Active -> Approved transition
 * (RevertAmendmentTransition), the exact inverse of apply(). Mirrors area E of the OP#581 test
 * plan.
 */
uses(DatabaseTransactions::class);

/** An Active parent plan with an expense group A1 and two leaves A1.1 (100€) / A1.2 (50€). */
function nhhpRevertParent(): array
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

function nhhpRevertApprovedAmendment(BudgetPlan $parent): BudgetPlan
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

function nhhpRevertModify(BudgetPlan $amendment, BudgetItem $item, int $toCents, string $field = 'value'): BudgetItemChange
{
    $from = $field === 'value' ? (int) $item->value->getAmount() : $item->getAttribute($field);

    return BudgetItemChange::create([
        'budget_plan_id' => $amendment->id,
        'budget_item_id' => $item->id,
        'action' => BudgetItemChangeAction::Modify,
        'diff' => [$field => ['from' => $from, 'to' => $toCents]],
    ]);
}

function nhhpRevertAdd(BudgetPlan $amendment, BudgetItem $parentGroup): BudgetItem
{
    $newItem = BudgetItem::create([
        'budget_plan_id' => $amendment->id, 'parent_id' => $parentGroup->id, 'is_group' => false,
        'budget_type' => BudgetType::EXPENSE, 'position' => 9, 'short_name' => 'A1.9', 'name' => 'Neu',
        'value' => Money::EUR(2000),
    ]);
    BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $newItem->id,
        'action' => BudgetItemChangeAction::Add,
    ]);

    return $newItem;
}

function nhhpRevertMarkDelete(BudgetPlan $amendment, BudgetItem $item): BudgetItemChange
{
    return BudgetItemChange::create([
        'budget_plan_id' => $amendment->id, 'budget_item_id' => $item->id,
        'action' => BudgetItemChangeAction::Delete,
    ]);
}

function nhhpRevertBookLeaf(BudgetItem $leaf): Booking
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

it('applies then reverts back to the exact pre-apply state (modify/add/delete round-trip)', function (): void {
    [$parent, $group, $leaf1, $leaf2] = nhhpRevertParent();
    $amendment = nhhpRevertApprovedAmendment($parent);
    nhhpRevertModify($amendment, $leaf1, 20000);
    $added = nhhpRevertAdd($amendment, $group);
    nhhpRevertMarkDelete($amendment, $leaf2);

    // snapshot pre-apply state for a field-by-field comparison after the round-trip
    $leaf1Before = $leaf1->fresh()->only(['id', 'budget_plan_id', 'value']);
    $leaf1Before['value'] = (int) $leaf1Before['value']->getAmount();
    $leaf2Before = $leaf2->fresh()->only(['id', 'budget_plan_id']);
    $addedBefore = $added->fresh()->only(['id', 'budget_plan_id']);

    $amendment->state->transitionTo(Active::class);
    // apply moved things around — sanity check before reverting
    expect($leaf1->fresh()->budget_plan_id)->toBe($parent->id)
        ->and((int) $leaf1->fresh()->value->getAmount())->toBe(20000)
        ->and($added->fresh()->budget_plan_id)->toBe($parent->id)
        ->and($leaf2->fresh()->budget_plan_id)->toBe($amendment->id);

    $amendment->state->transitionTo(Approved::class);

    $leaf1After = $leaf1->fresh()->only(['id', 'budget_plan_id', 'value']);
    $leaf1After['value'] = (int) $leaf1After['value']->getAmount();

    expect($leaf1After)->toBe($leaf1Before)
        ->and($leaf2->fresh()->only(['id', 'budget_plan_id']))->toBe($leaf2Before)
        ->and($added->fresh()->only(['id', 'budget_plan_id']))->toBe($addedBefore)
        ->and($amendment->fresh()->state)->toBeInstanceOf(Approved::class);
});

it('aborts a revert atomically when the live item was edited again after apply, state stays Active', function (): void {
    [$parent, , $leaf1] = nhhpRevertParent();
    $amendment = nhhpRevertApprovedAmendment($parent);
    nhhpRevertModify($amendment, $leaf1, 20000);
    $amendment->state->transitionTo(Active::class);

    // someone edits the item again directly after the amendment applied
    $leaf1->update(['value' => Money::EUR(99999)]);

    expect(fn () => $amendment->state->transitionTo(Approved::class))
        ->toThrow(AmendmentConflictException::class);

    expect($amendment->fresh()->state)->toBeInstanceOf(Active::class)
        // nothing was partially restored
        ->and((int) $leaf1->fresh()->value->getAmount())->toBe(99999);
});

it('re-applies cleanly after a revert (idempotent round-trip)', function (): void {
    [$parent, , $leaf1] = nhhpRevertParent();
    $amendment = nhhpRevertApprovedAmendment($parent);
    nhhpRevertModify($amendment, $leaf1, 20000);

    $amendment->state->transitionTo(Active::class);
    $amendment->state->transitionTo(Approved::class);
    expect((int) $leaf1->fresh()->value->getAmount())->toBe(10000);

    $amendment->state->transitionTo(Active::class);
    expect($amendment->fresh()->state)->toBeInstanceOf(Active::class)
        ->and((int) $leaf1->fresh()->value->getAmount())->toBe(20000);
});

it('keeps bookings on a modified item intact after revert', function (): void {
    [$parent, , $leaf1] = nhhpRevertParent();
    nhhpRevertBookLeaf($leaf1);
    $amendment = nhhpRevertApprovedAmendment($parent);
    nhhpRevertModify($amendment, $leaf1, 20000);

    $amendment->state->transitionTo(Active::class);
    $amendment->state->transitionTo(Approved::class);

    expect($leaf1->fresh()->hasBookings())->toBeTrue()
        ->and(Booking::where('titel_id', $leaf1->id)->sum('value'))->toEqual(10.0);
});
