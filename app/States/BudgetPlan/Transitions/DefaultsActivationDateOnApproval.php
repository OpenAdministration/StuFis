<?php

namespace App\States\BudgetPlan\Transitions;

use App\Models\BudgetPlan;

/**
 * Shared by every transition that lands a budget_plan in Approved (Resolved -> Approved, and
 * Active -> Approved via revert): defaults an amendment's activation_date to its approval_date
 * when neither is set yet. Guarded on `=== null` so a date already supplied — e.g. by
 * ⚡plan-view::changeState() onto the in-memory model before calling transitionTo() — always wins.
 * No-op for an original (non-amendment) plan.
 */
trait DefaultsActivationDateOnApproval
{
    private function defaultActivationDate(BudgetPlan $model): void
    {
        if ($model->isAmendment() && $model->activation_date === null) {
            $model->activation_date = $model->approval_date;
        }
    }
}
