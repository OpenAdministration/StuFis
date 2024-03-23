<?php

namespace framework\auth;

use App\Exceptions\LegacyDieException;
use Auth;
use framework\render\ErrorHandler;
use framework\Singleton;

/**
 * Class AuthHandler
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
     * @param array|string $groups String of groups
     * @return void die() if group is not there
     */
    public function requireGroup(array|string $groups): void
    {
        if (!$this->hasGroup($groups)) {
            throw new LegacyDieException(403, 'Fehlende Zugangsberechtigung', $groups);
        }
    }

    public function getUserMailinglists(): array
    {
        return $this->getAttributes()['mailinglists'] ?? [];
    }

    /**
     * return current user attributes
     */
    protected function getAttributes(): array {
        return [
            'groups' => '',
        ];
    }

    /**
     * check group permission - return result of check as boolean
     * @param array|string $groups String of groups
     * @param string $delimiter Delimiter of the groups in $group
     * @return bool  true if the user has one or more groups from $group
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
    public function requireAuth(){
        // laravel does this, just do nothing
    }

    /**
     * @return bool if user has Admin Privileges
     */
    public function isAdmin(): bool
    {
        // cannot use hasGroup here -> infinite recursion otherwise
        return in_array($_ENV['AUTH_ADMIN_GROUP'], $this->getUserGroups(), true);
    }

    public function getUserGroups(): array
    {
        return request()?->user()->getGroups();
    }

    /**
     * return log out url
     */
    public function getLogoutURL(): string {
        return "not my job anymore";
    }

    /**
     * send html header to redirect to logout url
     */
    public function logout(): void{

    }

    /**
     * return username or user mail address
     * if not set return null
     */
    public function getUsername(): string {
        return Auth::user()->username;
    }

    /**
     * return user displayname
     */
    public function getUserFullName(): string {
        return Auth::user()->name;
    }

    /**
     * return user mail address
     */
    public function getUserMail(): string {
        return Auth::user()->email;
    }

    /**
     * @param $gremien   array|string with $delimiter concat sting
     * @param $delimiter string delimiter between gremien
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
     * @return array
     */
    public function getUserGremien(): array
    {
        return Auth::user()->getCommittees();
    }

    public function reportPermissionDenied(string $errorMsg, string $debug): void
    {
        throw new LegacyDieException(403, $errorMsg, $debug);
    }
}