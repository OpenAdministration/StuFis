<?php

namespace App\Policies;

use App\Models\Legacy\Project;
use App\Models\User;
use App\States\Project\Applied;
use App\States\Project\ApprovedByFinance;
use App\States\Project\ApprovedByOrg;
use App\States\Project\ApprovedByOther;
use App\States\Project\Draft;
use App\States\Project\NeedFinanceApproval;
use App\States\Project\NeedOrgApproval;
use App\States\Project\ProjectState;
use App\States\Project\Revoked;
use App\States\Project\Terminated;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $Projects): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        $financeAll = $user->can('check-receipts', User::class);
        $approveFinance = $user->can('budget-officer', User::class);
        $approveOrg = $user->can('budget-officer', User::class);
        $approveOther = $user->can('budget-officer', User::class);

        return match ($project->state::class) {
            Draft::class => true,
            Applied::class => $financeAll,
            NeedOrgApproval::class => $approveOrg,
            ApprovedByOrg::class => $approveOrg,
            NeedFinanceApproval::class => $approveFinance,
            ApprovedByFinance::class => $approveFinance,
            ApprovedByOther::class => $approveOther,
            Revoked::class => false,
            Terminated::class => false,

            default => false,
        };
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->id === $project->creator_id || $user->can('budget-officer', User::class);
    }

    public function createExpense(User $user, Project $project): bool
    {
        return $project->state->expensable(); // we could check if user has the correct committee (optionaly) here
    }

    public function transitionTo(User $user, Project $project, ProjectState $newState): bool
    {
        $currentState = $project->state;

        // check if transition is possible
        if (! $currentState->canTransitionTo($newState)) {
            return false;
        }

        $isOwner = $user->id === $project->creator->id;
        $isOrg = $user->getCommittees()->contains($project->org);

        $financeAll = $user->can('check-receipts', User::class);
        $approveFinance = $user->can('budget-officer', User::class);
        $approveOrg = $user->can('budget-officer', User::class);
        $approveOther = $user->can('budget-officer', User::class);
        $terminator = $user->can('budget-officer', User::class);

        // there are some minor exceptions for certain states, but most of the time the needed permission is only
        // defined by the new state, not the current one
        return match ($newState::class) {
            Draft::class => $isOwner || $isOrg || $financeAll,
            Applied::class => $isOwner || $isOrg || $financeAll,
            NeedOrgApproval::class => $financeAll,
            ApprovedByOrg::class => $approveOrg,
            NeedFinanceApproval::class => $financeAll,
            ApprovedByFinance::class => $approveFinance,
            ApprovedByOther::class => $approveOther,
            Revoked::class => $isOwner || $isOrg || $financeAll,
            Terminated::class => $isOwner || $isOrg || $terminator,

            default => false,
        };
    }

    public function updateBudget(User $user, Project $project): bool
    {
        if ($this->update($user, $project) === false) {
            return false;
        }

        return $user->can('budget-officer', User::class);
    }

    public function updateApproval(User $user, Project $project): bool
    {
        if ($this->update($user, $project) === false) {
            return false;
        }

        return $user->can('budget-officer', User::class);
    }

    public function pickAnyCommittee(User $user): bool
    {
        return $user->can('budget-officer', User::class);
    }
}
