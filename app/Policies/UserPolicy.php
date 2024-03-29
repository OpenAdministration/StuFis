<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
   public function finance(User $user) : bool
   {
       return $user->getGroups()->contains('ref-finanzen');
   }

    public function cashOfficer(User $user) : bool
    {
        return $user->getGroups()->contains('ref-finanzen-kv');
    }

    public function budgetOfficer(User $user) : bool
    {
        return $user->getGroups()->contains('ref-finanzen-hv');
    }

}
