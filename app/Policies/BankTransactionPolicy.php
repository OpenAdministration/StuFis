<?php

namespace App\Policies;

use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BankTransactionPolicy
{
    use HandlesAuthorization;

    public function useFinTs(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('cash-officer', User::class);
    }

    public function useCsvUpload(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('cash-officer', User::class);
    }

    public function useCashJournal(User $user, BankAccount $bankAccount): bool
    {
        return $user->can('cash-officer', User::class);
    }

    public function viewAny(User $user): bool
    {
        return $user->can('cash-officer', User::class);
    }

    public function view(User $user, BankTransaction $bankTransaction): bool
    {
        return $user->can('cash-officer', User::class);
    }

    public function createKonto(User $user): bool
    {
        return $user->can('cash-officer', User::class);
    }
}
