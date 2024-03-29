<?php

namespace framework\auth;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use phpCAS;

class AuthCasHandler extends AuthHandler
{
    private static array $attributes;

    protected function __construct()
    {
        if ($_ENV['AUTH_DEBUG']) {
            $logger = new Logger('phpCAS');
            $logger->pushHandler(new RotatingFileHandler(SYSBASE . '/runtime/logs/cas.log', 5));
            phpCAS::setLogger($logger);
            if (DEV) {
                phpCAS::setVerbose(true);
            }
        }
        phpCAS::client($_ENV['CAS_VERSION'], $_ENV['CAS_HOST'], (int) $_ENV['CAS_PORT'], $_ENV['CAS_PATH']);
        phpCAS::setFixedServiceURL(FULL_APP_PATH);
        if (empty($_ENV['CAS_CERTFILE'])) {
            phpCAS::setNoCasServerValidation();
        } else {
            phpCAS::setCasServerCACert($_ENV['CAS_CERTFILE']);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function requireAuth(): void
    {
        if (!phpCAS::isAuthenticated() && !phpCAS::forceAuthentication()) {
            $this->reportPermissionDenied('Zugriff verweigert', 'forceAuth');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function requireGroup(array|string $groups): void
    {
        if (!$this->hasGroup($groups)) {
            $this->reportPermissionDenied('Fehlende Gruppenberechtigung', $groups);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getLogoutURL(): string
    {
        return phpCAS::getServerLogoutURL();
    }

    /**
     * {@inheritDoc}
     */
    public function logout(): void
    {
        phpCAS::logout();
    }

    /**
     * {@inheritDoc}
     */
    protected function getAttributes(): array
    {
        $this->requireAuth();
        if (!isset(self::$attributes)) {
            self::$attributes = phpCAS::getAttributes();
            self::$attributes['groups'] ??= [];
            self::$attributes['groups'] = (array) self::$attributes['groups'];
            self::$attributes['gremien'] ??= [];
            self::$attributes['gremien'] = (array) self::$attributes['gremien'];
            self::$attributes['mailinglists'] ??= [];
            self::$attributes['mailinglists'] = (array) self::$attributes['mailinglists'];
        }
        return self::$attributes;
    }

    /**
     * {@inheritDoc}
     */
    public function getUsername(): ?string
    {
        $this->requireAuth();
        return phpCAS::getUser();
    }

    /**
     * {@inheritDoc}
     */
    public function getUserFullName(): string
    {
        return $this->getAttributes()['fullName'] ?? phpCAS::getUser();
    }

    /**
     * {@inheritDoc}
     */
    public function getUserMail(): string
    {
        return $this->getAttributes()['email'];
    }

    /**
     * {@inheritDoc}
     */
    public function isAdmin(): bool
    {
        return in_array($_ENV['AUTH_REALM'] . '-' . $_ENV['AUTH_ADMIN_GROUP'], $this->getUserGroups(), true);
    }

    /**
     * DEBUG PURPOSE ONLY!!
     */
    public function getUserGroups(): array
    {
        return array_merge([REALM . '-sgis'], parent::getUserGroups());
    }
}
