<?php

namespace App\Policies;

use App\Models\Legacy\Expense;
use App\Models\Legacy\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ExpensesPolicy
{
    use HandlesAuthorization;

    public function viewSensitiveData(User $user, Expense $expenses): bool
    {
        return false;
    }

    public function viewZahlungsanweisungPdf(User $user, Expense $expenses) : bool
    {
        return false;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Expense $expenses): bool
    {
        return false;
    }

    public function create(User $user, Project $project): bool
    {
        // depends on the project
        return false;
    }

    public function update(User $user, Expense $expenses): bool
    {
        return false;
    }

    public function delete(User $user, Expense $expenses): bool
    {
        return false;
    }

    public function stateChange(User $user, Expense $expense, $newState) : bool
    {
        return false;
    }
}
