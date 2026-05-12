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
        return $user->getGroups()->contains('ref-finanzen-kv');
    }

    public function useCsvUpload(User $user, BankAccount $bankAccount): bool
    {
        return $user->getGroups()->contains('ref-finanzen-kv');
    }

    public function useCashJournal(User $user, BankAccount $bankAccount): bool
    {
        return $user->getGroups()->contains('ref-finanzen-kv');
    }

    public function viewAny(User $user): bool
    {
        return $user->getGroups()->contains('ref-finanzen-kv');
    }

    public function view(User $user, BankTransaction $bankTransaction): bool
    {
        return $user->getGroups()->contains('ref-finanzen-kv');
    }

    public function createKonto(User $user): bool
    {
        return $user->getGroups()->contains('ref-finanzen-kv');
    }
}
