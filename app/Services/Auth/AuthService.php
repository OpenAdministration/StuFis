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

    public function groupMapping(): Collection
    {
        return collect([
            'login' => 'login',
            'ref-finanzen' => 'ref-finanzen',
            'ref-finanzen-belege' => 'ref-finanzen-belege',
            'ref-finanzen-hv' => 'ref-finanzen-hv',
            'ref-finanzen-kv' => 'ref-finanzen-kv',
            'admin' => 'admin',
        ]);
    }

    public function userGroups(): Collection
    {
        // remove falsy values from raw groups like empty string, 0 and false
        // prevent permission escalation by ignoring empty mappings
        $rawGroups = $this->userGroupsRaw()->filter();
        $mapping = $this->groupMapping();

        // mapping is not supported in this AuthService
        if ($mapping->isEmpty()) {
            return $this->userGroupsRaw();
        }

        // permissions to obtain are the keys of the $mapping
        $groups = $mapping->filter(function ($groupToSearch) use ($rawGroups) {
            // groups where mapping is 'true' are given as default
            if ($groupToSearch === true) {
                return true;
            }

            // filter permissions, that are not given by provider
            return $rawGroups->containsStrict($groupToSearch);
        })->keys();

        return $groups;
    }

    abstract public function afterLogout();
}
