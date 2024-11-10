<?php

namespace App\Services\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;

abstract class AuthService
{
    abstract public function prepareLogin(): Response|RedirectResponse;

    abstract public function userFromCallback(Request $request): array;

    abstract public function userCommittees(): Collection;

    abstract public function allCommittees(): Collection;

    abstract public function userGroupsRaw(): Collection;

    public function userGroups(): Collection
    {
        return $this->userGroupsRaw();
    }

    abstract public function afterLogout();
}
