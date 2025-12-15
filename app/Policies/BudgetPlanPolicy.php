<?php

namespace App\Policies;

use App\Models\BudgetPlan;
use App\Models\User;
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
        return $user->getGroups()->contains('ref-finanzen-hv');
    }

    public function update(User $user, BudgetPlan $budgetPlan): bool
    {
        return $user->getGroups()->contains('ref-finanzen-hv');
    }

    public function delete(User $user, BudgetPlan $budgetPlan): bool
    {
        return $user->getGroups()->contains('ref-finanzen-hv');
    }
}
