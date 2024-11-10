<?php

namespace framework\auth;

use App\Exceptions\LegacyDieException;
use Auth;
use framework\Singleton;

/**
 * Class AuthHandler
 *
 * @static requireAuth()
 * @static hasGroup()
 */
class AuthHandler extends Singleton
{
    public static function getInstance(): static
    {
        return self::initSingleton(AUTH_HANDLER);
    }

    /**
     * check group permission - die on error
     * return void if successful
     *
     * @param  array|string  $groups  String of groups
     * @return void die() if group is not there
     */
    public function requireGroup(array|string $groups): void
    {
        if (! $this->hasGroup($groups)) {
            throw new LegacyDieException(403, 'Fehlende Zugangsberechtigung', $groups);
        }
    }

    public function getUserMailinglists(): array
    {
        return [];
    }

    /**
     * check group permission - return result of check as boolean
     *
     * @param  array|string  $groups  String of groups
     * @param  string  $delimiter  Delimiter of the groups in $group
     * @return bool true if the user has one or more groups from $group
     */
    public function hasGroup(array|string $groups, string $delimiter = ','): bool
    {
        $this->requireAuth();

        if ($this->isAdmin()) {
            return true;
        }
        $attrGroups = $this->getUserGroups();
        if (is_string($groups)) {
            $groups = explode($delimiter, $groups);
        }
        $hasGroups = array_intersect($groups, $attrGroups);

        return count($hasGroups) > 0;
    }

    /**
     * handle session and user login
     */
    public function requireAuth()
    {
        Auth::user()->getGroups()->has('login');
    }

    /**
     * @return bool if user has Admin Privileges
     */
    public function isAdmin(): bool
    {
        // cannot use hasGroup here -> infinite recursion otherwise
        return in_array('admin', $this->getUserGroups(), true);
    }

    public function getUserGroups(): array
    {
        return Auth::user()?->getGroups()->toArray();
    }

    /**
     * return username or user mail address
     * if not set return null
     */
    public function getUsername(): string
    {
        return Auth::user()->username;
    }

    /**
     * return user displayname
     */
    public function getUserFullName(): string
    {
        return Auth::user()->name;
    }

    /**
     * return user mail address
     */
    public function getUserMail(): string
    {
        return Auth::user()->email;
    }

    /**
     * @param  $gremien  array|string with $delimiter concat sting
     * @param  $delimiter  string delimiter between gremien
     */
    public function hasGremium(array|string $gremien, string $delimiter = ','): bool
    {
        if ($this->isAdmin()) {
            return true;
        }
        $attrGremien = $this->getUserGremien();
        if (is_string($gremien)) {
            $gremien = explode($delimiter, $gremien);
        }
        $hasGremien = array_intersect($gremien, $attrGremien);

        return count($hasGremien) > 0;
    }

    /**
     * Returns the Gremien of the User
     */
    public function getUserGremien(): array
    {
        return Auth::user()->getCommittees()->toArray();
    }
}
