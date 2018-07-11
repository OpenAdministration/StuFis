<?php
/**
 * FRAMEWORK ProtocolHelper
 * interface AuthHandler
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework interface
 * @author 			michael gnehr
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			07.05.2018
 * @copyright 		Copyright (C) Michael Gnehr 2018, All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */
interface AuthHandler
{	
	/**
	 * return instance of this class
	 * singleton class
	 * return same instance on every call
	 * @param bool $noPermCheck
	 * @return BasicAuthHandler
	 */
	public static function getInstance();
	
	/**
	 * handle session and user login
	 */
	function requireAuth();
	
	/**
	 * check group permission - die on error
	 * return true if successfull
	 * @param string $groups    String of groups
	 * @return bool  true if the user has one or more groups from $group
	 */
	function requireGroup($group);
	
	/**
	 * check group permission - return result of check as boolean
	 * @param string $groups    String of groups
	 * @param string $delimiter Delimiter of the groups in $group
	 * @return bool  true if the user has one or more groups from $group
	 */
	function hasGroup($group, $delimiter = ",");
	
	/**
	 * return log out url
	 * @return string
	 */
	function getLogoutURL();
	
	/**
	 * send html header to redirect to logout url
	 * @param string $param
	 */
	function logout();
	
	/**
	 * return current user attributes
	 * @return array
	 */
	function getAttributes();
	
	/**
	 * return username or user mail address
	 * if not set return null
	 * @return string|NULL
	 */
	function getUsername();
	
	/**
	 * return user displayname
	 * @return string
	 */
	function getUserFullName();
	
	/**
	 * return user mail address
	 * @return string
	 */
	function getUserMail();
}

?>