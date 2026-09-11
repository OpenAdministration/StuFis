<?php

namespace App\Policies;

use App\Models\BudgetPlan;
use App\Models\User;
use App\States\BudgetPlan\BudgetPlanState;
use Illuminate\Auth\Access\HandlesAuthorization;

class BudgetPlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BudgetPlan $budgetPlan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->can('budget-officer', User::class);
    }

    public function update(User $user, BudgetPlan $budgetPlan): bool
    {
        return $user->can('budget-officer', User::class) && $budgetPlan->state->isEditable();
    }

    public function delete(User $user, BudgetPlan $budgetPlan): bool
    {
        return $user->can('budget-officer', User::class) && $budgetPlan->state->isEditable();
    }

    public function transitionTo(User $user, BudgetPlan $budgetPlan, BudgetPlanState $newState): bool
    {
        // the transition must be allowed by the state machine ...
        if (! $budgetPlan->state->canTransitionTo($newState)) {
            return false;
        }

        // an amendment's Active <-> Completed arcs move only in lockstep with the original plan it
        // was applied to (OP#589 F8) — cascaded by CompleteAmendmentsTransition /
        // ReactivateAmendmentsTransition, never walked individually. Its Approved <-> Active arcs
        // (apply/revert) stay individually available; only this cascading arc is refused here.
        if ($budgetPlan->isAmendment() && $budgetPlan->state->isCascadingArc($newState)) {
            return false;
        }

        // ... and only budget officers may move a plan along its workflow
        return $user->can('budget-officer', User::class);
    }
}
