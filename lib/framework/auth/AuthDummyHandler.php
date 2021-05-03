<?php
/**
 * DummyAuth Handler
 * used for debugging login
 * replaces SAML login and provide simple login
 * implements the SAML Interface of AuthHandler/AuthSamlHandler
 *
 * @package           Stura - Referat IT - ProtocolHelper
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

class AuthDummyHandler implements AuthHandler{
    
    /**
     * reference to own instance
     * singleton instance of this class
     *
     * @var AuthDummyHandler
     */
    private static $instance; //singleton instance of this class
    
    /**
     * current user data
     *  keys
     *    eduPersonPrincipalName
     *    mail
     *    displayName
     *    groups
     *
     * @var array
     */
    private $attributes;

    /**
     * class constructor
     * protected cause of singleton class
     */
    protected function __construct(){
        //create session
        $this->attributes = DEV_ATTRIBUTES;
    }

    /** {@inheritDoc} */
    public static function getInstance() : AuthHandler
    {
        if (!isset(self::$instance)){
            self::$instance = new AuthDummyHandler();
        }
        return self::$instance;
    }

    /** {@inheritDoc} */
    public function requireGroup($groups) : void
    {
        $this->requireAuth();
        if (!$this->hasGroup($groups)){
            $this->reportPermissionDenied('Eine der Gruppen ' . $groups . ' wird benötigt');
        }
    }

    /** {@inheritDoc} */
    public function requireAuth() :void
    {
    }

    /** {@inheritDoc} */
    public function hasGroup($groups, $delimiter = ",") : bool
    {
        $this->requireAuth();
        $attributes = $this->getAttributes();
        if($this->isAdmin()){
            return true;
        }
        if (count(array_intersect(explode($delimiter, strtolower($groups)), array_map("strtolower", $attributes["groups"]))) === 0){
            return false;
        }
        return true;
    }

    /** {@inheritDoc} */
    public function getAttributes() : array
    {
        return $this->attributes;
    }

    /** {@inheritDoc} */
    public function logout() :void
    {
        header('Location: ' . $this->getLogoutURL());
        die();
    }

    /** {@inheritDoc} */
    public function getLogoutURL() : string
    {
        return URIBASE;
    }

    /** {@inheritDoc} */
    public function getUsername() : ?string
    {
        $attributes = $this->getAttributes();
        return $attributes["eduPersonPrincipalName"][0] ?? $attributes["mail"] ?? null;
    }

    /** {@inheritDoc} */
    public function getUserFullName() : string{
        $this->requireAuth();
        return $this->getAttributes()["displayName"][0];
    }

    /** {@inheritDoc} */
    public function getUserMail() : string{
        $this->requireAuth();
        return $this->getAttributes()["mail"];
    }

    /** {@inheritDoc} */
    public function isAdmin() : bool{
        return in_array("admin", $this->attributes["groups"], true);
    }

    /** {@inheritDoc} */
    public function hasGremium($gremien, string $delimiter = ","): bool
    {
        $attributes = $this->getAttributes();
        if (!isset($attributes["gremien"])){
            return false;
        }
        return count(array_intersect(explode($delimiter, strtolower($gremien)), array_map("strtolower", $attributes["gremien"]))) !== 0;
    }

    public function reportPermissionDenied(string $errorMsg, string $debug = null): void
    {
        if(isset($debug)){
            $debug = var_export($this->attributes,true);
        }
        ErrorHandler::handleError(403, $errorMsg, $debug);
    }
}
