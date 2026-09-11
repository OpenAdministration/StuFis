<?php

namespace App\Support\Budget;

use App\Models\BudgetPlan;
use RuntimeException;

/**
 * Thrown by AmendmentApplier when an amendment cannot be applied/reverted: either its parent plan
 * isn't (or is no longer) in the right state, or optimistic concurrency finds the live budget_item
 * data has drifted since the change was drafted. Carries the conflicting items/fields so the
 * Approved↔Active transition in plan-view can show a useful error toast instead of crashing.
 */
class AmendmentConflictException extends RuntimeException
{
    /**
     * @param  list<array{budget_item_id: int, field: string|null, message: string}>  $conflicts
     */
    public function __construct(string $message, private readonly array $conflicts = [])
    {
        parent::__construct($message);
    }

    public static function parentNotActive(BudgetPlan $amendment): self
    {
        return new self(__('budget-plan.amendment.conflict.parent-not-active', ['plan' => $amendment->parentPlan?->label() ?? '?']));
    }

    /**
     * @param  list<array{budget_item_id: int, field: string|null, message: string}>  $conflicts
     */
    public static function withConflicts(array $conflicts): self
    {
        $summary = collect($conflicts)->pluck('message')->implode(' ');

        return new self(__('budget-plan.amendment.conflict.stale-items', ['details' => $summary]), $conflicts);
    }

    /**
     * @return list<array{budget_item_id: int, field: string|null, message: string}>
     */
    public function conflicts(): array
    {
        return $this->conflicts;
    }
}
