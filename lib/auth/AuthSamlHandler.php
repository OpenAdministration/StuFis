<?php

namespace auth;

use Singleton;

class AuthSamlHandler extends Singleton implements AuthHandler{
    private static $SIMPLESAMLDIR;
    private static $SIMPLESAMLAUTHSOURCE;
    private static $AUTHGROUP;
    private static $ADMINGROUP;
    private $saml;

    /**
     * @param mixed ...$pars
     * @return AuthHandler
     */
    public static function getInstance(...$pars): AuthHandler{
        return parent::getInstance(...$pars);
    }
    
    protected function __construct(){
        require_once(self::$SIMPLESAMLDIR . '/lib/_autoload.php');
        $this->saml = new SimpleSAML_Auth_Simple(self::$SIMPLESAMLAUTHSOURCE);
    }
    
    final protected static function static__set($name, $value): void
    {
        if (property_exists(get_class(), $name)) {
            self::$$name = $value;
        } else {
            die("$name ist keine Variable in " . get_class());
        }
    }
    
    public function getUserFullName() : string
    {
        $this->requireAuth();
        return $this->getAttributes()["displayName"][0];
    }
    
    function requireAuth() : void
    {
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
    
    public function getAttributes() : array
    {
        $attributes = $this->saml->getAttributes();
        //var_dump($attributes['groups']);
        return $attributes;

    }
    
    public function getUserMail() : string
    {
        $this->requireAuth();
        return $this->getAttributes()["mail"][0];
    }
    
    public function requireGroup($groups) : void
    {
        $this->requireAuth();
        if($this->isAdmin()){
            return;
        }
        if (!$this->hasGroup($groups)){
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
    public function hasGroup($groups, $delimiter = ","): bool
    {
        $attributes = $this->getAttributes();
        if (!isset($attributes["groups"])){
            return false;
        }
        if($this->isAdmin()){
            return true;
        }
        if (count(array_intersect(explode($delimiter, strtolower($groups)), array_map("strtolower", $attributes["groups"]))) === 0){
            return false;
        }
        return true;
    }
    
    public function hasGremium($gremien, $delimiter = ",") : bool
    {
        $attributes = $this->getAttributes();
        if (!isset($attributes["gremien"])){
            return false;
        }
        if (count(array_intersect(explode($delimiter, strtolower($gremien)), array_map("strtolower", $attributes["gremien"]))) === 0){
            return false;
        }
        return true;
    }
    
    public function getUsername() : ?string
    {
        $attributes = $this->getAttributes();
        return $attributes["eduPersonPrincipalName"][0] ?? $attributes["mail"][0] ?? null;
    }
    
    public function getLogoutURL() : string
    {
        return $this->saml->getLogoutURL();
    }
    
	/**
	 * send html header to redirect to logout url
	 */
	public function logout() :void
    {
		header('Location: '. $this->getLogoutURL());
		die();
	}
    
    public function isAdmin($delimiter = ",") :bool
    {
        $attributes = $this->getAttributes();
        if (!isset($attributes["groups"])){
            return false;
        }
        if (in_array(self::$ADMINGROUP, $attributes["groups"], true)){
            return true;
        }
        return false;
    }
    
}










