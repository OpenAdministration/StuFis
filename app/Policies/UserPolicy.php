<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->getGroups()->contains('admin') ? true : null;
    }

    public function login(User $user): bool
    {
        return $user->getGroups()->contains('login');
    }

    public function finance(User $user): bool
    {
        return $user->getGroups()->contains('ref-finanzen');
    }

    public function cashOfficer(User $user): bool
    {
        return $user->getGroups()->contains('ref-finanzen-kv');
    }

    public function budgetOfficer(User $user): bool
    {
        return $user->getGroups()->contains('ref-finanzen-hv');
    }

    public function admin(User $user): bool
    {
        return $user->getGroups()->contains('admin');
    }
}
