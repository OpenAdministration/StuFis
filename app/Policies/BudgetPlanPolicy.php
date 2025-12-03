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
        return false;
    }

    public function view(User $user, BudgetPlan $budgetPlan): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, BudgetPlan $budgetPlan): bool
    {
        return false;
    }

    public function delete(User $user, BudgetPlan $budgetPlan): bool
    {
        return $user->getGroups()->contains('ref-finanzen-hv');
    }
}
