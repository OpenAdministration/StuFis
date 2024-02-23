<?php

namespace framework\auth;

use framework\render\ErrorHandler;
use framework\Singleton;

/**
 * Class AuthHandler
 * @static requireAuth()
 * @static hasGroup()
 */
abstract class AuthHandler extends Singleton
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
            ErrorHandler::handleError(403, 'Fehlende Zugangsberechtigung', $groups);
        }
    }

    public function getUserMailinglists(): array
    {
        return $this->getAttributes()['mailinglists'] ?? [];
    }

    /**
     * return current user attributes, implemented by the provider
     */
    abstract protected function getAttributes(): array;

    /**
     * @param string $attributeName
     * @return bool returns if the given key exists in the attribute array implemented by the provider
     */
    protected function hasAttribute(string $attributeName) : bool
    {
        return isset($this->getAttributes()[$attributeName]);
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
        if($_ENV['AUTH_PREFIX_REALM_TO_GROUP']){
            $realm = $_ENV['AUTH_REALM'];
            array_walk($groups, static function (&$val) use ($realm) {
                $val = $realm . '-' . $val;
            });
        }
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
    public function isAdmin(): bool
    {
        // cannot use hasGroup here -> infinite recursion otherwise
        return in_array('admin', $this->getUserGroups(), true);
    }

    public function getRawUserGroups(): array
    {
        return $this->extractAttributeFromEnvVar('AUTH_ATTRIBUTE_GROUPS', asArray: true);
    }

    public function getUserGroups(): array
    {
        return $this->remapUserGroups($this->extractAttributeFromEnvVar('AUTH_ATTRIBUTE_GROUPS', asArray: true));
    }

    protected function remapUserGroups(array $groups) : array
    {
        $mappedGroups = [];
        $defaultGroups = [];

        if(!empty($_ENV['GROUP_MAPPING_LOGIN'])){
            if(in_array($_ENV['GROUP_MAPPING_LOGIN'], $groups, true)) {
                $mappedGroups[] = 'login';
            }
            if($_ENV['GROUP_MAPPING_LOGIN'] === 'true'){
                $mappedGroups[] = 'login';
            }
        }else{
            $defaultGroups[] = 'login';
        }
        if(!empty($_ENV['GROUP_MAPPING_REF_FINANZEN']) && in_array($_ENV['GROUP_MAPPING_REF_FINANZEN'], $groups, true)) {
            $mappedGroups[] = 'ref-finanzen';
        }else{
            $defaultGroups[] = 'ref-finanzen';
        }
        if(!empty($_ENV['GROUP_MAPPING_HHV']) && in_array($_ENV['GROUP_MAPPING_HHV'], $groups, true)) {
            $mappedGroups[] = 'ref-finanzen-hv';
        }else{
            $defaultGroups[] = 'ref-finanzen-hv';
        }
        if(!empty($_ENV['GROUP_MAPPING_KV']) && in_array($_ENV['GROUP_MAPPING_KV'], $groups, true)) {
            $mappedGroups[] = 'ref-finanzen-kv';
        }else{
            $defaultGroups[] = 'ref-finanzen-kv';
        }
        if(!empty($_ENV['GROUP_MAPPING_INVOICES']) && in_array($_ENV['GROUP_MAPPING_INVOICES'], $groups, true)) {
            $mappedGroups[] = 'ref-finanzen-belege';
        }else{
            $defaultGroups[] = 'ref-finanzen-belege';
        }
        if(!empty($_ENV['GROUP_MAPPING_ADMIN']) && in_array($_ENV['GROUP_MAPPING_ADMIN'], $groups, true)) {
            $mappedGroups[] = 'admin';
        }else{
            $defaultGroups[] = 'admin';
        }
        $defaultGroups = array_intersect($defaultGroups, $groups); // find the unmapped groups and deliver them
        return array_merge($defaultGroups, $mappedGroups); // deliver default and remapped groups
    }

    /**
     * return log out url
     */
    abstract public function getLogoutURL(): string;

    /**
     * send html header to redirect to logout url
     */
    abstract public function logout(): void;

    /**
     * return username or user mail address
     * if not set return null
     */
    public function getUsername(): string {
        return $this->extractAttributeFromEnvVar('AUTH_ATTRIBUTE_USERNAME');
    }

    /**
     * return user displayname
     */
    public function getUserFullName(): string {
        return $this->extractAttributeFromEnvVar('AUTH_ATTRIBUTE_COMMON_NAME');
    }

    /**
     * return user mail address
     */
    public function getUserMail(): string {
        return $this->extractAttributeFromEnvVar('AUTH_ATTRIBUTE_MAIL');
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
        return $this->extractAttributeFromEnvVar('AUTH_ATTRIBUTE_COMMITTEES', true);
    }

    private function extractAttributeFromEnvVar(string $varName, bool $asArray = false, bool $failIfNotSet = true) : mixed
    {
        if($this->hasAttribute($_ENV[$varName]) && $_ENV[$varName] !== "" && $_ENV[$varName] !== null) {
            $attr = $this->getAttributes()[$_ENV[$varName]];
            if($asArray === false){
                if(is_array($attr)){
                    return array_values($attr)[0];
                }
                return $attr;
            }
            // should be array returned
            if(is_array($attr)){
                return $attr;
            }
            return [$attr];
        }
        if($failIfNotSet){
            $this->reportPermissionDenied("Var $varName (Value: $_ENV[$varName]) not provided by Auth Attributes",
                var_export(array_keys($this->getAttributes()), true)
            );
        }
        return null;
    }

    public function reportPermissionDenied(string $errorMsg, string $debug = ''): void
    {
        if (empty($debug)) {
            $debug = var_export($this->getAttributes(), true);
        }
        ErrorHandler::handleError(403, $errorMsg, $debug);
    }

    public function debugInfo()
    {
        return $this->getAttributes();
    }
}
