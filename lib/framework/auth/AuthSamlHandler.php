<?php

namespace framework\auth;

use framework\render\ErrorHandler;

class AuthSamlHandler extends AuthHandler
{
    private static $SIMPLESAMLDIR;
    private static $SIMPLESAMLAUTHSOURCE;
    private static $AUTHGROUP;
    private static $ADMINGROUP;
    private $saml;

    protected function __construct()
    {
        require_once self::$SIMPLESAMLDIR . '/lib/_autoload.php';
        $this->saml = new \SimpleSAML_Auth_Simple(self::$SIMPLESAMLAUTHSOURCE);
    }

    final protected static function static__set($name, $value): void
    {
        if (property_exists(__CLASS__, $name)) {
            self::$$name = $value;
        } else {
            exit("$name ist keine Variable in " . __CLASS__);
        }
    }

    public function getUserFullName(): string
    {
        $this->requireAuth();
        return $this->getAttributes()['displayName'][0];
    }

    public function requireAuth(): void
    {
        if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] && !$this->saml->isAuthenticated()) {
            $this->reportPermissionDenied('Login nicht (mehr) gültig');
        }
        $this->saml->requireAuth();
        if (!$this->hasGroup(self::$AUTHGROUP)) {
            $this->reportPermissionDenied('Eine der Gruppen ' . self::$AUTHGROUP . ' wird benötigt');
        }
    }

    protected function getAttributes(): array
    {
        $attributes = $this->saml->getAttributes();
        //var_dump($attributes['groups']);
        return $attributes;
    }

    public function getUserMail(): string
    {
        $this->requireAuth();
        return $this->getAttributes()['mail'][0];
    }

    public function requireGroup(array|string $groups): void
    {
        $this->requireAuth();
        if ($this->isAdmin()) {
            return;
        }
        if (!$this->hasGroup($groups)) {
            $this->reportPermissionDenied('Eine der Gruppen ' . $groups . ' wird benötigt');
        }
    }

    /**
     * @param array|string $groups    String of groups
     * @param string $delimiter Delimiter of the groups in $group
     *
     * @return bool  true if the user has one or more groups from $group
     */
    public function hasGroup(array|string $groups, string $delimiter = ','): bool
    {
        $attributes = $this->getAttributes();
        if (!isset($attributes['groups'])) {
            return false;
        }
        if ($this->isAdmin()) {
            return true;
        }
        if (count(array_intersect(explode($delimiter, strtolower($groups)), array_map('strtolower', $attributes['groups']))) === 0) {
            return false;
        }
        return true;
    }

    public function hasGremium($gremien, $delimiter = ','): bool
    {
        $attributes = $this->getAttributes();
        if (!isset($attributes['gremien'])) {
            return false;
        }
        if (count(array_intersect(explode($delimiter, strtolower($gremien)), array_map('strtolower', $attributes['gremien']))) === 0) {
            return false;
        }
        return true;
    }

    public function getUsername(): ?string
    {
        $attributes = $this->getAttributes();
        return $attributes['eduPersonPrincipalName'][0] ?? $attributes['mail'][0] ?? null;
    }

    public function getLogoutURL(): string
    {
        return $this->saml->getLogoutURL();
    }

    /**
     * send html header to redirect to logout url
     */
    public function logout(): void
    {
        header('Location: '. $this->getLogoutURL());
        exit();
    }

    public function isAdmin($delimiter = ','): bool
    {
        $attributes = $this->getAttributes();
        if (!isset($attributes['groups'])) {
            return false;
        }
        if (in_array(self::$ADMINGROUP, $attributes['groups'], true)) {
            return true;
        }
        return false;
    }

    public function reportPermissionDenied(string $errorMsg, string $debug = null): void
    {
        if (isset($debug)) {
            $debug = var_export($this->getAttributes(), true);
        }
        ErrorHandler::handleError(403, $errorMsg, $debug);
    }
}
