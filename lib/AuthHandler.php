<?php

class  AuthHandler{
    private static $instance; //singelton instance of this class
    private $saml;
    
    private function __construct($SIMPLESAML, $SIMPLESAMLAUTHSOURCE){
        
        require_once($SIMPLESAML . '/lib/_autoload.php');
        $this->saml = new SimpleSAML_Auth_Simple($SIMPLESAMLAUTHSOURCE);
        if (isset($_REQUEST["ajax"]) && $_REQUEST["ajax"] && $this->saml->isAuthenticated()){
            header('HTTP/1.0 401 Unauthorized');
            die();
        }
        
    }
    
    public static function getInstance(){
        if (!isset($instance)){
            global $SIMPLESAML, $SIMPLESAMLAUTHSOURCE;
            self::$instance = new AuthHandler($SIMPLESAML, $SIMPLESAMLAUTHSOURCE);
        }
        return self::$instance;
    }
    
    function getUserFullName(){
        $this->saml->requireAuth();
        return $this->saml->getAttributes()["displayName"][0];
    }
    
    function getUserMail(){
        $this->saml->requireAuth();
        return $this->getAttributes()["mail"][0];
    }
    
    function getAttributes(){
        return $this->saml->getAttributes();
    }
    
    function requireAuth(){
        $this->saml->requireAuth();
    }
    
    function requireGroup($group){
        $this->saml->requireAuth();
        if (!$this->hasGroup($group)){
            header('HTTP/1.0 401 Unauthorized');
            include SYSBASE . "/template/permission-denied.tpl";
            die();
        }
    }
    
    /**
     * @param string $group     String of groups
     * @param string $delimiter Delimiter of the groups in $group
     *
     * @return bool
     */
    function hasGroup($group, $delimiter = ","){
        $attributes = $this->getAttributes();
        if (count(array_intersect(explode($delimiter, strtolower($group)), array_map("strtolower", $attributes["groups"]))) == 0){
            return false;
        }
        return true;
    }
    
    function getUsername(){
        $attributes = $this->saml->getAttributes();
        if (isset($attributes["eduPersonPrincipalName"]) && isset($attributes["eduPersonPrincipalName"][0]))
            return $attributes["eduPersonPrincipalName"][0];
        if (isset($attributes["mail"]) && isset($attributes["mail"][0]))
            return $attributes["mail"][0];
        return null;
    }
    
    function getLogoutURL(){
        return $this->saml->getLogoutURL();
    }
}










