<?php
/**
 * FRAMEWORK ProtocolHelper
 * AuthBasicHandler
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			michael gnehr
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			04.05.2018
 * @copyright 		Copyright (C) Michael Gnehr 2018, All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
 

/**
 * BasicAuth Handler
 * handles Basic Authentification
 * used on cron routes and routes without permission value
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			michael gnehr
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			04.05.2018
 * @copyright 		Copyright (C) Michael Gnehr 2018, All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
namespace framework\auth;

use framework\Singleton;

class AuthBasicHandler extends Singleton implements AuthHandler{
	
	/**
	 * user map
	 * @var array
	 */
	private static $BASICUSER;
	
	/**
	 * user array from config
	 * @var array
	 */
	private static $usermap;
	
	/**
	 * current user data
	 *  keys
	 *    eduPersonPrincipalName
	 *    mail
	 *    displayName
	 *    groups
	 * @var array
	 */
	private $attributes;
	
	/**
	 * disable permissioncheck on require Auth/session creation
	 * used on routes without permission
	 * @var boolean
	 */
	private static $noPermCheck;
	
	/**
	 * class constructor
	 * private cause of singleton class
	 */
	protected function __construct(){
		$noPermCheck = false;
		//create session
		session_start();
		self::$usermap = self::$BASICUSER;
		self::$noPermCheck = $noPermCheck;
	}
	
	/**
	 * return instance of this class
	 * singleton class
	 * return same instance on every call
	 */
	public static function getInstance(...$pars): AuthHandler{
		return parent::getInstance(...$pars);
	}
	
	final protected static function static__set($name, $value): void
    {
		if (property_exists(get_class(), $name)) {
            self::$$name = $value;
        } else {
            die("$name ist keine Variable in " . get_class());
        }
	}
	private static $ADMINGROUP;

	public function isAdmin() :bool
    {
		return $this->hasGroup(self::$ADMINGROUP);
	}
	
	/**
	 * handle session and user login
	 */
	public function requireAuth() : void
    {
		//check IP and user agent
		if(isset($_SESSION['SILMPH']['CLIENT_IP'], $_SESSION['SILMPH']['CLIENT_AGENT'])){
			if ($_SESSION['SILMPH']['CLIENT_IP'] !== $_SERVER['REMOTE_ADDR'] || $_SESSION['SILMPH']['CLIENT_AGENT'] !== ((isset($_SERVER ['HTTP_USER_AGENT']))? $_SERVER['HTTP_USER_AGENT']: 'Unknown-IP:'.$_SERVER['REMOTE_ADDR'])){
				//die or reload page is IP isn't the same when session was created -> need new login
				session_destroy();
				session_start();
				header("Refresh: 0");
				die();
			}
		} else {
			$_SESSION['SILMPH']['CLIENT_IP'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['SILMPH']['CLIENT_AGENT'] = ($_SERVER['HTTP_USER_AGENT'] ?? ('Unknown-IP:' . $_SERVER['REMOTE_ADDR']));
		}
		
		if(!isset($_SESSION['SILMPH']['USER_ID'])){
			$_SESSION['SILMPH']['USER_ID'] = 0;
		}
		
		if(!isset($_SESSION['SILMPH']['LAST_ACTION'])){
			$_SESSION['SILMPH']['LAST_ACTION'] = time();
		}
		
		if ( isset($_GET['logout']) && (strpos($_SERVER['REQUEST_URI'], '?logout=1') !== false || strpos($_SERVER['REQUEST_URI'], '&logout=1') !== false )){
			session_destroy();
			session_start();
			$this->reportPermissionDenied('You have no permission to access this page.');
		}
		
		if(!isset($_SESSION['SILMPH']['MESSAGES'])){
			$_SESSION['SILMPH']['MESSAGES'] = array();
		}
		if (!self::$noPermCheck && !isset($_SERVER['PHP_AUTH_USER'])){
			$_SESSION['SILMPH']['USER_ID'] = 0;
			$this->reportPermissionDenied('<strong>You are not allowd to access this page. Please Login.</strong>');
		}
        if (!self::$noPermCheck) {
            $_SESSION['SILMPH']['USER_ID'] = 0;
            if (isset(self::$usermap[$_SERVER['PHP_AUTH_USER']]) &&
                self::$usermap[$_SERVER['PHP_AUTH_USER']]['password'] === $_SERVER['PHP_AUTH_PW']){
                $this->attributes = array_slice(self::$usermap[$_SERVER['PHP_AUTH_USER']], 1 );
            } else {
                $this->reportPermissionDenied('<strong>You are not allowd to access this page. Please Login.</strong>');
            }
        } else {
            $this->attributes = [
                'displayName' => 'Anonymous',
                'mail' => '',
                'groups' => ['anonymous'],
                'eduPersonPrincipalName' => ['nologin'],
            ];
        }
	}
	
    /** {@inheritDoc} */
	public function requireGroup($groups) :void
    {
		$this->requireAuth();
		if (!$this->hasGroup($groups)){
            $this->reportPermissionDenied('You have no permission to access this page.');
		}
	}

    /** {@inheritDoc} */
	public function hasGroup($group, $delimiter = ",") : bool
    {
		$this->requireAuth();
		$attributes = $this->getAttributes();
        return count(array_intersect(explode($delimiter, strtolower($group)), array_map("strtolower", $attributes["groups"]))) !== 0;
    }

    /** {@inheritDoc} */
	public function getLogoutURL(): string
    {
		return BASE_URL.$_SERVER['REQUEST_URI'] . '?logout=1';
	}

    /** {@inheritDoc} */
	public function logout(): void
    {
		header('Location: '. $this->getLogoutURL());
		die();
	}

    /** {@inheritDoc} */
	public function getAttributes() : array
    {
		return $this->attributes;
	}

    /** {@inheritDoc} */
	public function getUsername(): ?string
    {
		$attributes = $this->getAttributes();
        return $attributes["eduPersonPrincipalName"][0] ?? $attributes["mail"] ?? null;
    }

    /** {@inheritDoc} */
	public function getUserFullName(): string{
		$this->requireAuth();
		return $this->getAttributes()["displayName"];
	}
	
	/**
	 * return user mail address
	 * @return string
	 */
	public function getUserMail():string
    {
		$this->requireAuth();
		return $this->getAttributes()["mail"];
	}

    /** {@inheritDoc} */
    public function hasGremium($gremien, $delimiter = ","): bool
    {
        $attributes = $this->getAttributes();
        if (!isset($attributes["gremien"])){
            return false;
        }
        return count(array_intersect(explode($delimiter, strtolower($gremien)), array_map("strtolower", $attributes["gremien"]))) !== 0;
    }

    public function reportPermissionDenied(string $errorMsg = '', string $debug = ''): void
    {
        header('WWW-Authenticate: Basic realm="'.BASE_TITLE.' Please Login"');
        header('HTTP/1.0 401 Unauthorized');
        echo $errorMsg;
        if(DEV){
            echo $debug;
        }
        die();
    }
}
