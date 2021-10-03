<?php

namespace framework\auth;

use framework\render\ErrorHandler;
use framework\Singleton;

/**
 * Class AuthHandler
 * @package framework\auth
 * @static requireAuth()
 * @static hasGroup()
 *
 */
abstract class AuthHandler extends Singleton
{
    public static function getInstance(): static
    {
        return self::initSingleton(AUTH_HANDLER);
    }

    public function __call(string $name, array $arguments)
    {
        self::initSingleton(AUTH_HANDLER)->$name(...$arguments);
    }

    /**
     * check group permission - die on error
     * return void if successful
     * @param array|string $groups String of groups
     * @return void die() if group is not there
     */
    abstract public function requireGroup(array|string $groups): void;

    public function getUserMailinglists(): array
    {
        return $this->getAttributes()['mailinglists'] ?? [];
    }

    /**
     * return current user attributes
     * @return array
     */
    abstract public function getAttributes(): array;

    /**
     * check group permission - return result of check as boolean
     * @param array|string $groups String of groups
     * @param string $delimiter Delimiter of the groups in $group
     * @return bool  true if the user has one or more groups from $group
     */
    public function hasGroup(array|string $groups, string $delimiter = ","): bool
    {
        $this->requireAuth();

        if ($this->isAdmin()) {
            return true;
        }
        $attrGroups = $this->getUserGroups();
        if (is_string($groups)) {
            $groups = explode($delimiter, $groups);
        }
        $realm = $_ENV['AUTH_REALM'];
        array_walk($groups, static function (&$val) use ($realm) {
            $val = $realm . '-' . $val;
        });
        $hasGroups = array_intersect($groups, $attrGroups);

        return count($hasGroups) > 0;
    }

    /**
     * handle session and user login
     */
    abstract public function requireAuth(): void;

    /**
     * @return bool if user has Admin Privileges
     */
    abstract public function isAdmin(): bool;

    public function getUserGroups(): array
    {
        return $this->getAttributes()['groups'] ?? [];
    }

    /**
     * return log out url
     * @return string
     */
    abstract public function getLogoutURL(): string;

    /**
     * send html header to redirect to logout url
     */
    abstract public function logout(): void;

    /**
     * return username or user mail address
     * if not set return null
     * @return string|NULL
     */
    abstract public function getUsername(): ?string;

    /**
     * return user displayname
     * @return string
     */
    abstract public function getUserFullName(): string;

    /**
     * return user mail address
     * @return string
     */
    abstract public function getUserMail(): string;

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
        $gremienArray = explode($delimiter, $gremien);
        $hasGremien = array_intersect($gremienArray, $attrGremien);

        return count($hasGremien) > 0;
    }

    public function getUserGremien(): array
    {
        return $this->getAttributes()['gremien'] ?? [];
    }

    public function reportPermissionDenied(string $errorMsg, string $debug): void
    {
        ErrorHandler::handleError(403, $errorMsg, $debug);
    }
}
