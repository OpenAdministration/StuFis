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
        return false;
    }

    public function view(User $user, Project $Projects): bool
    {
        return true;
    }

    public function create(User $user, Project $project): bool
    {
        // depends on the project
        return false;
    }

    public function update(User $user, Project $project): bool
    {
        $userGroups = $user->getGroups();
        $financeAll = $userGroups->contains('ref-finanzen-belege');
        $approveFinance = $userGroups->contains('ref-finanzen-hv');
        $approveOrg = $userGroups->contains('ref-finanzen-hv');
        $approveOther = $userGroups->contains('ref-finanzen-hv');

        return match($project->state::class)  {
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

    public function delete(User $user, Project $Projects): bool
    {
        // depends on the state
        return false;
    }

    public function createExpense(User $user, Project $project): bool
    {
        return $project->state->expensable();
    }

    public function transitionTo(User $user, Project $project, ProjectState $newState): bool
    {
        $currentState = $project->state;

        // check if transition is possible
        if(!$currentState->canTransitionTo($newState)) {
            return false;
        }

        $isOwner = $user->id === $project->creator->id;
        $isOrg = $user->getCommittees()->contains($project->org);
        $userGroups = $user->getGroups();

        $financeAll = $userGroups->contains('ref-finanzen-belege');
        $approveFinance = $userGroups->contains('ref-finanzen-hv');
        $approveOrg = $userGroups->contains('ref-finanzen-hv');
        $approveOther = $userGroups->contains('ref-finanzen-hv');
        $terminator = $userGroups->contains('ref-finanzen-hv');

        // there are some minor exceptions for certain states, but most of the time the needed permission is only
        // defined by the new state, not the current one
        return match($newState::class)  {
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

    public function updateField(User $user, Project $project, string $field)
    {
        if($this->update($user, $project) === false){
            return false;
        }

        if($field === 'recht' || $field === 'recht_additional' || $field === 'posten-titel') {
            return match ($project->state::class) {
                Draft::class => false,
                default => true
            };
        }
        return true;
    }

}
