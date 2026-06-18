<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountingPolicy
{
    use HandlesAuthorization;

    public function instruct(User $user): bool
    {
        return false;
    }

    public function confirmInstruction(User $user): bool
    {
        return false;
    }

    public function deleteInstruction(User $user): bool
    {
        return false;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function cancelBooking(): bool
    {
        return false;
    }
}
