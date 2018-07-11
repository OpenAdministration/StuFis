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
 
require_once (dirname(__FILE__).'/class.AuthHandler.php');

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
class AuthBasicHandler extends Singleton implements AuthHandler{
	
	/**
	 * reference to own instance
	 * singelton instance of this class
	 * @var BasicAuthHandler
	 */
	private static $instance; 
	
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
	 * @param bool $noPermCheck
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
	 * @param bool $noPermCheck
	 * @return BasicAuthHandler
	 */
	public static function getInstance(...$pars): AuthHandler{
		return parent::getInstance(...$pars);
	}
	
	final static protected function static__set($name, $value){
		if (property_exists(get_class(), $name))
			self::$$name = $value;
		else
			die("$name ist keine Variable in " . get_class());
	}
	private static $ADMINGROUP;
	function isAdmin(){
		return $this->hasGroup(self::$ADMINGROUP);
	}
	
	/**
	 * handle session and user login
	 */
	function requireAuth(){
		//check IP and user agent
		if(isset($_SESSION['SILMPH']) && isset($_SESSION['SILMPH']['CLIENT_IP']) && isset($_SESSION['SILMPH']['CLIENT_AGENT'])){
			if ($_SESSION['SILMPH']['CLIENT_IP'] != $_SERVER['REMOTE_ADDR'] || $_SESSION['SILMPH']['CLIENT_AGENT'] != ((isset($_SERVER ['HTTP_USER_AGENT']))? $_SERVER['HTTP_USER_AGENT']: 'Unknown-IP:'.$_SERVER['REMOTE_ADDR'])){
				//die or reload page is IP isn't the same when session was created -> need new login
				session_destroy();
				session_start();
				header("Refresh: 0");
				die();
			}
		} else {
			$_SESSION['SILMPH']['CLIENT_IP'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['SILMPH']['CLIENT_AGENT'] = ((isset($_SERVER ['HTTP_USER_AGENT']))? $_SERVER['HTTP_USER_AGENT']: 'Unknown-IP:'.$_SERVER['REMOTE_ADDR']);
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
			header('WWW-Authenticate: Basic realm="'.BASE_TITLE.' Please Login"');
			header('HTTP/1.0 401 Unauthorized');
			echo 'You have no permission to access this page.';
			die();
		}
		
		if(!isset($_SESSION['SILMPH']['MESSAGES'])){
			$_SESSION['SILMPH']['MESSAGES'] = array();
		}
		if (!self::$noPermCheck && !isset($_SERVER['PHP_AUTH_USER'])){
			$_SESSION['SILMPH']['USER_ID'] = 0;
			header('WWW-Authenticate: Basic realm="'.BASE_TITLE.' Please Login"');
			header('HTTP/1.0 401 Unauthorized');
			echo '<strong>You are not allowd to access this page. Please Login.</strong>';
			die();
		} else {
			if (!self::$noPermCheck) {
				$_SESSION['SILMPH']['USER_ID'] = 0;
				if (isset(self::$usermap[$_SERVER['PHP_AUTH_USER']]) && 
					self::$usermap[$_SERVER['PHP_AUTH_USER']]['password'] == $_SERVER['PHP_AUTH_PW']){
					$this->attributes = array_slice(self::$usermap[$_SERVER['PHP_AUTH_USER']], 1 );
				} else {
					header('WWW-Authenticate: Basic realm="basic_'.BASE_TITLE.'_realm"');
					header('HTTP/1.0 401 Unauthorized');
					echo '<strong>You are not allowd to access this page. Please Login.</strong>';
					die();
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
	}
	
	/**
	 * check group permission - die on error
	 * return true if successfull
	 * @param string $groups    String of groups
	 * @return bool  true if the user has one or more groups from $group
	 */
	function requireGroup($group){
		$this->requireAuth();
		if (!$this->hasGroup($group)){
			header('WWW-Authenticate: Basic realm="'.BASE_TITLE.' Please Login"');
			header('HTTP/1.0 401 Unauthorized');
			echo 'You have no permission to access this page.';
			die();
		}
		return true;
	}
	
	/**
	 * check group permission - return result of check as boolean
	 * @param string $groups    String of groups
	 * @param string $delimiter Delimiter of the groups in $group
	 * @return bool  true if the user has one or more groups from $group
	 */
	function hasGroup($group, $delimiter = ","){
		$this->requireAuth();
		$attributes = $this->getAttributes();
		if (count(array_intersect(explode($delimiter, strtolower($group)), array_map("strtolower", $attributes["groups"]))) == 0){
			return false;
		}
		return true;
	}
	
	/**
	 * return log out url
	 * @return string
	 */
	function getLogoutURL(){
		return BASE_URL.$_SERVER['REQUEST_URI'] . '?logout=1';
	}
	
	/**
	 * send html header to redirect to logout url
	 * @param string $param
	 */
	function logout(){
		header('Location: '. $this->getLogoutURL());
		die();
	}
	
	/**
	 * return current user attributes
	 * @return array
	 */
	function getAttributes(){
		return $this->attributes;
	}
	
	/**
	 * return username or user mail address
	 * if not set return null
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
	 * @return string
	 */
	function getUserFullName(){
		$this->requireAuth();
		return $this->getAttributes()["displayName"];
	}
	
	/**
	 * return user mail address
	 * @return string
	 */
	function getUserMail(){
		$this->requireAuth();
		return $this->getAttributes()["mail"];
	}
}
