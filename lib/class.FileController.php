<?php
use SILMPH\File;
/**
 * CONTROLLER FileHandler
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			08.05.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */

require_once dirname(__FILE__).'/class.FileHandler.php';

class FileController {

	/**
	 * 
	 * @var DBConnector
	 */
	private $db;
	
	/**
	 * constructor
	 * @param DBConnector $dbconnector
	 */
	public function __construct(){
		$this->db = DBConnector::getInstance();
	}
	
	public function handle($routeInfo){
		if ($routeInfo['action'] == 'get'){
			if (!isset($routeInfo['fdl'])){
				$routeInfo['fdl'] = 0;
			}
			$this->get($routeInfo);
		}
	}

	/**
	 * ACTION get
	 * handle file delivery
	 */
	private function get($routeInfo){
		$fh = new FileHandler($this->db);
		//get file
		$file = $fh->checkFileHash($routeInfo['key']);
		if (!$file){
			ErrorHandler::_renderError(NULL, 404);
			return;
		}
		//TODO ACL - user has permission to download/view this file?
		if (false){//!checkUserPermission($top['gname'])) {
			ErrorHandler::_renderError(NULL, 403);
			die();
		}
		$fh->deliverFileData($file, $routeInfo['fdl']);
		return;
	}
}