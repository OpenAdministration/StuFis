<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TodoPolicy
{
    use HandlesAuthorization;

    public function viewTodoLists(User $user): bool
    {
        return $user->getGroups()->contains('ref-finanzen');
    }
}
