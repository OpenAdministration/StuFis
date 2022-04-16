<?php
/**
 * DummyAuth Handler
 * used for debugging login
 * replaces SAML login and provide simple login
 * implements the SAML Interface of AuthHandler/AuthSamlHandler
 *
 * @category          framework
 * @author            michael gnehr
 * @author            Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since             18.02.2018
 * @copyright         Copyright (C) Michael Gnehr 2018, All rights reserved
 * @platform          PHP
 * @requirements      PHP 7.0 or higher
 */

namespace framework\auth;

use framework\render\ErrorHandler;

class AuthDummyHandler extends AuthHandler
{
    private array $attributes;

    /**
     * class constructor
     * protected cause of singleton class
     */
    protected function __construct()
    {
        $this->attributes = ['groups' => explode(',', $_ENV['AUTH_DUMMY_ATTRIBUTES'])];
    }

    /**
     * {@inheritDoc}
     */
    public function requireAuth(): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getLogoutURL(): string
    {
        return URIBASE;
    }

    /**
     * {@inheritDoc}
     */
    public function getUsername(): ?string
    {
        return 'dummy';
    }

    /**
     * {@inheritDoc}
     */
    public function getUserFullName(): string
    {
        return 'Dummy Nutzer';
    }

    /**
     * {@inheritDoc}
     */
    public function getUserMail(): string
    {
        return 'dummy@example.org';
    }

    /**
     * {@inheritDoc}
     */
    public function getUserGremien(): array
    {
        return array_merge(...array_values(ORG_DATA['gremien']));
    }

    /**
     * {@inheritDoc}
     */
    public function isAdmin(): bool
    {
        return in_array('admin', $this->attributes['groups'], true);
    }

    public function reportPermissionDenied(string $errorMsg, string $debug = null): void
    {
        if (isset($debug)) {
            $debug = var_export($this->attributes, true);
        }
        ErrorHandler::handleError(403, $errorMsg, $debug);
    }

    protected function getAttributes(): array
    {
        return $this->attributes;
    }

    public function logout(): void
    {
    }
}
