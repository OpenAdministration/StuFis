<?php

class  AuthSamlHandler extends Singleton implements AuthHandler{
    private static $SIMPLESAMLDIR;
    private static $SIMPLESAMLAUTHSOURCE;
    private static $AUTHGROUP;
    private static $ADMINGROUP;
    private $saml;
    
    /**
     * @return AuthHandler
     */
    public static function getInstance(...$pars): AuthHandler{
        return parent::getInstance(...$pars);
    }
    
    protected function __construct(){
        require_once(self::$SIMPLESAMLDIR . '/lib/_autoload.php');
        $this->saml = new SimpleSAML_Auth_Simple(self::$SIMPLESAMLAUTHSOURCE);
    }
    
    final static protected function static__set($name, $value){
        if (property_exists(get_class(), $name))
            self::$$name = $value;
        else
            die("$name ist keine Variable in " . get_class());
    }
    
    function getUserFullName(){
        $this->requireAuth();
        return $this->getAttributes()["displayName"][0];
    }
    
    function requireAuth(){
        if (isset($_REQUEST["ajax"]) && $_REQUEST["ajax"] && !$this->saml->isAuthenticated()){
            header('HTTP/1.0 401 UNATHORISED');
            die("Login nicht (mehr) gueltig");
        }
        $this->saml->requireAuth();
        if (!$this->hasGroup(self::$AUTHGROUP)){
            header('HTTP/1.0 403 FORBIDDEN');
            die("Du besitzt nicht die nötigen Rechte um diese Seite zu sehen.");
        }
    }
    
    function getAttributes(){
        $attributes = $this->saml->getAttributes();
        //var_dump($attributes['groups']);
        if (!DEV){
            return $attributes;
        }else{
            return $attributes;
        }
    }
    
    function getUserMail(){
        $this->requireAuth();
        return $this->getAttributes()["mail"][0];
    }
    
    function requireGroup($group){
        $this->requireAuth();
        if($this->isAdmin()){
            return true;
        }
        if (!$this->hasGroup($group)){
            header('HTTP/1.0 401 Unauthorized');
            include SYSBASE . "/template/permission-denied.tpl";
            die();
        }
    }
    
    /**
     * @param string $groups    String of groups
     * @param string $delimiter Delimiter of the groups in $group
     *
     * @return bool  true if the user has one or more groups from $group
     */
    function hasGroup($groups, $delimiter = ","){
        $attributes = $this->getAttributes();
        if (!isset($attributes["groups"])){
            return false;
        }
        if($this->isAdmin()){
            return true;
        }
        if (count(array_intersect(explode($delimiter, strtolower($groups)), array_map("strtolower", $attributes["groups"]))) == 0){
            return false;
        }
        return true;
    }
    
    function hasGremium($gremien, $delimiter = ","){
        $attributes = $this->getAttributes();
        if (!isset($attributes["gremien"])){
            return false;
        }
        if (count(array_intersect(explode($delimiter, strtolower($gremien)), array_map("strtolower", $attributes["gremien"]))) == 0){
            return false;
        }
        return true;
    }
    
    function getUsername(){
        $attributes = $this->getAttributes();
        if (isset($attributes["eduPersonPrincipalName"]) && isset($attributes["eduPersonPrincipalName"][0]))
            return $attributes["eduPersonPrincipalName"][0];
        if (isset($attributes["mail"]) && isset($attributes["mail"][0]))
            return $attributes["mail"][0];
        return null;
    }
    
    function getLogoutURL(){
        return $this->saml->getLogoutURL();
    }
    
	/**
	 * send html header to redirect to logout url
	 * @param string $param
	 */
	function logout(){
		header('Location: '. $this->getLogoutURL());
		die();
	}
    
    function isAdmin($delimiter = ","){
        $attributes = $this->getAttributes();
        if (!isset($attributes["groups"])){
            return false;
        }
        if (in_array(self::$ADMINGROUP,$attributes["groups"])){
            return true;
        }
        return false;
    }
    
}










