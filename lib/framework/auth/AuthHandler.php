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

namespace framework\auth;

interface AuthHandler
{	
	/**
	 * return instance of this class
	 * singleton class
	 * return same instance on every call
	 *
     * @return AuthHandler
	 */
	public static function getInstance(): AuthHandler;
	
	/**
	 * handle session and user login
	 */
	public function requireAuth() : void;
	
	/**
	 * check group permission - die on error
	 * return void if successful
	 * @param string|array $groups    String of groups
	 * @return void die() if group is not there
	 */
    public function requireGroup($groups): void;
	
	/**
	 * check group permission - return result of check as boolean
	 * @param string|array $groups    String of groups
	 * @param string $delimiter Delimiter of the groups in $group
	 * @return bool  true if the user has one or more groups from $group
	 */
    public function hasGroup($groups, $delimiter = ","): bool;
	
	/**
	 * return log out url
	 * @return string
	 */
	public function getLogoutURL(): string;
	
	/**
	 * send html header to redirect to logout url
	 */
	public function logout() : void;
	
	/**
	 * return current user attributes
	 * @return array
	 */
	public function getAttributes(): array;
	
	/**
	 * return username or user mail address
	 * if not set return null
	 * @return string|NULL
	 */
	public function getUsername(): ?string;
	
	/**
	 * return user displayname
	 * @return string
	 */
	public function getUserFullName(): string;
	
	/**
	 * return user mail address
	 * @return string
	 */
	public function getUserMail(): string;
    
    /**
     * @return bool if user has Admin Privileges
     */
    public function isAdmin(): bool;
    
    /**
     * @param $gremien   string with $delimiter concat sting
     * @param $delimiter string delimiter between gremien
     */
    public function hasGremium(string $gremien, string $delimiter) : bool;
}
