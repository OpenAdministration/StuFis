<?php

namespace App\Support\Budget;

use App\Models\BudgetItem;
use App\Models\BudgetItemChange;
use App\Models\BudgetPlan;
use App\States\BudgetPlan\Active;
use Cknow\Money\Money;
use Illuminate\Support\Facades\DB;

/**
 * Applies/reverts a Nachtragshaushaltsplan (amendment) onto the live budget_item rows of its
 * parent plan — see the "Architecture: change-set overlay" section of OP#581.
 *
 * apply() runs on the amendment's Approved -> Active transition, revert() on Active -> Approved.
 * Both are single DB transactions: every touched item is first verified against the value it had
 * when the change was drafted (optimistic concurrency — someone could have booked against it, or
 * another amendment could have applied first), and only if every check passes are the writes
 * performed. A single stale item aborts the whole transaction, leaving both the items and the
 * amendment's state untouched.
 *
 * Item ids are never reused or reassigned: modify only ever updates fields on the existing row,
 * add/delete only ever move a row between the amendment plan and its parent (`budget_plan_id`),
 * so `booking.titel_id` / `projektposten.titel_id` never dangle.
 */
class AmendmentApplier
{
    public function apply(BudgetPlan $amendment): void
    {
        if (! $amendment->isAmendment()) {
            return;
        }

        DB::transaction(function () use ($amendment): void {
            $parent = $amendment->parentPlan;
            if ($parent === null || ! ($parent->state instanceof Active)) {
                throw AmendmentConflictException::parentNotActive($amendment);
            }

            $changes = $amendment->itemChanges()->get();

            $conflicts = [];
            foreach ($changes as $change) {
                $this->verifyForApply($change, $conflicts);
            }
            if ($conflicts !== []) {
                throw AmendmentConflictException::withConflicts($conflicts);
            }

            foreach ($changes as $change) {
                match ($change->action) {
                    BudgetItemChange::ACTION_MODIFY => $this->writeModify($change),
                    BudgetItemChange::ACTION_ADD => $this->rehome($change, $amendment->parent_plan_id),
                    BudgetItemChange::ACTION_DELETE => $this->rehome($change, $amendment->id),
                    default => null,
                };
            }
        });
    }

    public function revert(BudgetPlan $amendment): void
    {
        if (! $amendment->isAmendment()) {
            return;
        }

        DB::transaction(function () use ($amendment): void {
            $changes = $amendment->itemChanges()->get();

            $conflicts = [];
            foreach ($changes as $change) {
                $this->verifyForRevert($change, $conflicts);
            }
            if ($conflicts !== []) {
                throw AmendmentConflictException::withConflicts($conflicts);
            }

            foreach ($changes as $change) {
                match ($change->action) {
                    BudgetItemChange::ACTION_MODIFY => $this->writeRevert($change),
                    // add: re-home back under the amendment (undo the apply-time re-home)
                    BudgetItemChange::ACTION_ADD => $this->rehome($change, $amendment->id),
                    // delete: un-park back under the parent plan
                    BudgetItemChange::ACTION_DELETE => $this->rehome($change, $amendment->parent_plan_id),
                    default => null,
                };
            }
        });
    }

    /**
     * @param  list<array{budget_item_id: int, field: string|null, message: string}>  $conflicts
     */
    private function verifyForApply(BudgetItemChange $change, array &$conflicts): void
    {
        $item = BudgetItem::find($change->budget_item_id);

        if ($change->action === BudgetItemChange::ACTION_MODIFY) {
            if ($item === null) {
                $conflicts[] = $this->conflict($change->budget_item_id, null, __('budget-plan.amendment.conflict.item-missing', ['id' => $change->budget_item_id]));

                return;
            }
            foreach ((array) $change->diff as $field => $pair) {
                // short_name (Titelnummer) is immutable for base items (F2, OP#581) — the editor
                // refuses to ever record it, but skip it here too in case an older change row
                // somehow still carries one
                if ($field === 'short_name') {
                    continue;
                }
                if (! $this->fieldsEqual($field, $item->getAttribute($field), $pair['from'] ?? null)) {
                    $conflicts[] = $this->conflict($item->id, $field, __('budget-plan.amendment.conflict.field-changed', [
                        'item' => $item->short_name ?? $item->id, 'field' => $field,
                    ]));
                }
            }

            return;
        }

        if ($change->action === BudgetItemChange::ACTION_DELETE && ($item !== null && $item->hasBookings())) {
            $conflicts[] = $this->conflict($item->id, null, __('budget-plan.amendment.conflict.now-booked', ['item' => $item->short_name ?? $item->id]));
        }
        // add: nothing to verify — the item already belongs to the amendment, re-homing can't conflict
    }

    /**
     * @param  list<array{budget_item_id: int, field: string|null, message: string}>  $conflicts
     */
    private function verifyForRevert(BudgetItemChange $change, array &$conflicts): void
    {
        if ($change->action !== BudgetItemChange::ACTION_MODIFY) {
            return; // add/delete revert only re-homes — nothing field-level to verify
        }

        $item = BudgetItem::find($change->budget_item_id);
        if ($item === null) {
            $conflicts[] = $this->conflict($change->budget_item_id, null, __('budget-plan.amendment.conflict.item-missing', ['id' => $change->budget_item_id]));

            return;
        }
        foreach ((array) $change->diff as $field => $pair) {
            if ($field === 'short_name') {
                continue;
            }
            if (! $this->fieldsEqual($field, $item->getAttribute($field), $pair['to'] ?? null)) {
                $conflicts[] = $this->conflict($item->id, $field, __('budget-plan.amendment.conflict.field-changed', [
                    'item' => $item->short_name ?? $item->id, 'field' => $field,
                ]));
            }
        }
    }

    private function writeModify(BudgetItemChange $change): void
    {
        $item = BudgetItem::findOrFail($change->budget_item_id);
        $updates = [];
        foreach ((array) $change->diff as $field => $pair) {
            if ($field === 'short_name') {
                continue;
            }
            $updates[$field] = $this->fromStorage($field, $pair['to'] ?? null);
        }
        $item->update($updates);
    }

    private function writeRevert(BudgetItemChange $change): void
    {
        $item = BudgetItem::findOrFail($change->budget_item_id);
        $updates = [];
        foreach ((array) $change->diff as $field => $pair) {
            if ($field === 'short_name') {
                continue;
            }
            $updates[$field] = $this->fromStorage($field, $pair['from'] ?? null);
        }
        $item->update($updates);
    }

    private function rehome(BudgetItemChange $change, ?int $budgetPlanId): void
    {
        BudgetItem::whereKey($change->budget_item_id)->update(['budget_plan_id' => $budgetPlanId]);
    }

    /**
     * Semantic comparison of a live model attribute against a stored (JSON-decoded) value.
     * `value` is stored/compared in integer cents (the Money-safe canonical form); every other
     * field is compared as a normalized string, so `null`/`''` don't spuriously conflict.
     */
    private function fieldsEqual(string $field, mixed $live, mixed $stored): bool
    {
        if ($field === 'value') {
            $liveCents = $live instanceof Money ? $live->getAmount() : Money::EUR(0)->getAmount();

            return (int) $liveCents === (int) $stored;
        }

        if ($field === 'position' || $field === 'parent_id') {
            return ($live === null ? null : (int) $live) === ($stored === null ? null : (int) $stored);
        }

        return (string) ($live ?? '') === (string) ($stored ?? '');
    }

    /** Convert a stored JSON scalar back into the value BudgetItem::update() expects for $field. */
    private function fromStorage(string $field, mixed $stored): mixed
    {
        if ($field === 'value') {
            // stored in integer cents (Money::getAmount()'s minor-unit form) — parse directly,
            // without forceDecimals, so it isn't misread as a euro amount 100x too small
            return Money::EUR((int) $stored);
        }
        if ($field === 'position' || $field === 'parent_id') {
            return $stored === null ? null : (int) $stored;
        }

        return $stored;
    }

    /**
     * @return array{budget_item_id: int, field: string|null, message: string}
     */
    private function conflict(int $itemId, ?string $field, string $message): array
    {
        return ['budget_item_id' => $itemId, 'field' => $field, 'message' => $message];
    }
}
