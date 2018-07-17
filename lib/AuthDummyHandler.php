<?php
/**
 * FRAMEWORK ProtocolHelper
 * AuthHandler
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

require_once(dirname(__FILE__) . '/class.AuthHandler.php');

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
class AuthDummyHandler implements AuthHandler{
    
    /**
     * reference to own instance
     * singelton instance of this class
     *
     * @var AuthDummyHandler
     */
    private static $instance; //singelton instance of this class
    
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
        $this->attributes = [
            'displayName' => 'Test Nutzer',
            'mail' => 'Test@Test.de',
            'groups' => DEVGROUPS,
            'gremien' => DEVGREMIEN,
            'eduPersonPrincipalName' => ['usernameFromRZ'],
        ];
    }
    
    /**
     * return instance of this class
     * singleton class
     * return same instance on every call
     *
     * @return AuthHandler
     */
    public static function getInstance(){
        if (!isset(self::$instance)){
            self::$instance = new AuthDummyHandler();
        }
        return self::$instance;
    }
    
    /**
     * check group permission - die on error
     * return true if successfull
     *
     * @param string $groups String of groups
     *
     * @return bool  true if the user has one or more groups from $group
     */
    function requireGroup($groups){
        $this->requireAuth();
        if (!$this->hasGroup($groups)){
            header('HTTP/1.0 403 Unauthorized');
            echo 'You have no permission to access this page.';
            die();
        }
        return true;
    }
    
    /**
     * handle session and user login
     */
    function requireAuth(){
    }
    
    /**
     * check group permission - return result of check as boolean
     *
     * @param string $groups    String of groups
     * @param string $delimiter Delimiter of the groups in $group
     *
     * @return bool  true if the user has one or more groups from $group
     */
    function hasGroup($groups, $delimiter = ","){
        $this->requireAuth();
        $attributes = $this->getAttributes();
        if (count(array_intersect(explode($delimiter, strtolower($groups)), array_map("strtolower", $attributes["groups"]))) == 0){
            return false;
        }
        return true;
    }
    
    /**
     * return current user attributes
     *
     * @return array
     */
    function getAttributes(){
        return $this->attributes;
    }
    
    /**
     * send html header to redirect to logout url
     */
    function logout(){
        header('Location: ' . $this->getLogoutURL());
        die();
    }
    
    /**
     * return log out url
     *
     * @return string
     */
    function getLogoutURL(){
        return URIBASE;
    }
    
    /**
     * return username or user mail address
     * if not set return null
     *
     * @return string|NULL
     */
    function getUsername(){
        $attributes = $this->getAttributes();
        if (isset($attributes["eduPersonPrincipalName"]) && isset($attributes["eduPersonPrincipalName"][0]))
            return $attributes["eduPersonPrincipalName"][0];
        if (isset($attributes["mail"]) && isset($attributes["mail"]))
            return $attributes["mail"];
        return null;
    }
    
    /**
     * return user displayname
     *
     * @return string
     */
    function getUserFullName(){
        $this->requireAuth();
        return $this->getAttributes()["displayName"];
    }
    
    /**
     * return user mail address
     *
     * @return string
     */
    function getUserMail(){
        $this->requireAuth();
        return $this->getAttributes()["mail"];
    }
    
    function isAdmin(){
        return DEVADMIN;
    }
}
