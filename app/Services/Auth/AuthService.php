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

    abstract public function groupMapping(): Collection;

    public function userGroups(): Collection
    {
        $rawGroups = $this->userGroupsRaw();
        $mapping = $this->groupMapping();

        // mapping is not supported in this AuthService
        if ($mapping->isEmpty()) {
            return $this->userGroupsRaw();
        }

        // permissions to obtain are the keys of the $mapping
        $groups = $mapping->filter(function ($value) use ($rawGroups) {
            // filter permissions, that are not given by provider
            // prevent permission escalation by ignoring empty mappings
            // groups where mapping is 'true' are given as default
            return $rawGroups->contains($value) && ! empty($value);
        })->keys();

        return $groups;
    }

    abstract public function afterLogout();
}
