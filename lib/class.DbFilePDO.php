<?php
use SILMPH\File;
/**
 * FRAMEWORK ProtocolHelper
 * database connection
 * implements framework database functions
 *
 * @package         Stura - Referat IT - ProtocolHelper
 * @category        framework
 * @author 			michael g
 * @author 			Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since 			17.02.2018
 * @copyright 		Copyright (C) 2018 - All rights reserved
 * @platform        PHP
 * @requirements    PHP 7.0 or higher
 */

/**
 * 
 * @author Michael Gnehr <michael@gnehr.de>
 * @since 01.03.2017
 * @package SILMPH_framework
 */
class DbFilePDO
{
	/**
	 * database member
	 * @var PDO
	 */
	public $db;
	
	/**
	 * database connector
	 * @var DBConnector
	 * @see DBConnector.php
	 */
	public $dbconnector;
	
	private $TABLE_PREFIX;
	
	/**
	 * db error state: last request was error or not
	 * @var bool
	 */
	private $_isError = false;
	
	/**
	 * last error message
	 * @var $string
	 */
	private $msgError = '';
	
	/**
	 * db state: db was closed or not
	 * @var bool
	 */
	private $_isClose = false;
	
	/**
	 * Contains affected rows after update, delete and insert requests
	 * set by memberfunction: protectedInsert
	 * @var integer
	 */
	private $affectedRows = 0;

	/**
	 * class constructor
	 * @param DBConnector $dbconnector 
	 */
	function __construct($dbconnector)
	{
		$this->db = $dbconnector->getPdo();
		$this->dbconnector = $dbconnector;
		$this->TABLE_PREFIX = $dbconnector->getDbPrefix();
		if (!$this->db) {
			$this->_isError = true;
			$this->msgError = "Connect failed: No PDO object\n";
		    ErrorHandler::_renderError($this->msgError);
		    exit();
		}
	}
	
	// ======================== HELPER FUNCTIONS ======================================================
	
	/**
	 * generate reference array of array
	 * @param array $arr
	 * @return array
	 */
	function refValues($arr){
		if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+
		{
			$refs = array();
			foreach($arr as $key => $value)
				$refs[$key] = &$arr[$key];
			return $refs;
		}
		return $arr;
	}
	
	/**
	 * escape string by database
	 * @param string $in
	 * @return string escaped string
	 */
	function escapeString($in){
		return $this->db->real_escape_string($in);
	}
	
	// ======================== BASE FUNCTIONS ========================================================
	
	// ====================================================
	
	/**
	 * db: return las inserted id
	 * @return int last inserted id
	 */
	function lastInsertId(){
		return $this->db->lastInsertId();
	}
	
	/**
	 * db: return affected rows
	 * @return int affected rows
	 */
	function affectedRows(){
		return $this->affectedRows;
	}
	
	/**
	 * @return int $this->_isError
	 */
	public function isError(){
		return $this->_isError;
	}
	
	/**
	 * @return bool $this->_isClose
	 */
	public function isClose(){
		return $this->_isClose;
	}
	
	/**
	 * @retun string last error message
	 */
	public function getError(){
		return $this->msgError;
	}
	
	/**
	 * only delete reference to pdo object
	 * you need to remove all other references on your own
	 * connection stay alive for the lifetime of that PDO object
	 */
	function close(){
		if (!$this->_isClose){
			$this->_isClose = true;
			if ($this->db){
				$this->db = NULL;
				$this->dbconnector = NULL;
			}		
		}
	}
	
	// database file related functions ========================================================
	
	/**
	 * create filedata entry, set datablob null, set diskpath to file
	 * @param string $uploadfile
	 * @return false|int new inserted id or false
	 */
	public function createFileDataPath($filepath){
		if ($filepath == '') return false;
		try {
			$this->fileCloseLastGet();
			$this->dbconnector->dbInsert('filedata', ['diskpath' => $filepath]);
			$this->_isError = false;
			return $this->lastInsertId();
		} catch (Exception $e) {
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' );
			$this->affectedRows = -1;
			return false;
		}
	}
	
	/**
	 * create fileentry on table fileinfo
	 * @param File $f
	 * @return false|int new inserted id or false
	 */
	public function createFile($f){
		try {
			$this->fileCloseLastGet();
			$this->dbconnector->dbInsert('fileinfo',[
				'link' => $f->link,
				'hashname' => $f->hashname,
				'filename' => $f->filename,
				'size' => $f->size,
				'fileextension' => $f->fileextension,
				'mime' => $f->mime,
				'encoding' => $f->encoding,
				'data' => $f->data,
				'added_on' => ($f->added_on)? $f->added_on : date_create()->format('Y--m-d H:i:s')
			]);
			$this->_isError = false;
			return $this->lastInsertId();
		} catch (Exception $e) {
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' );
			$this->affectedRows = -1;
			return false;
		}
	}
	
	/**
	 * update file column 'data' of fileinfo entry
	 * @param File $f
	 * @return boolean success
	 */
	public function updateFile_DataId($f){
		try {
			$this->_isError = false;
			$this->fileCloseLastGet();
			$this->dbconnector->dbUpdate(
				'fileinfo',
				['id' => $f->id],
				['data' => $f->data]
			);
			return true;
		} catch (Exception $e) {
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' );
			$this->affectedRows = -1;
			return false;
		}
	}
	
	/**
	 * prevent duplicate files for one link/directory
	 * @param string $linkId link or directory name - hier beleg name/id
	 * @param string $filename
	 * @param string $extension
	 */
	public function checkFileExists($linkId, $filename, $extension){
		$res = NULL;
		try {
			$this->_isError = false;
			$res = $this->dbconnector->dbFetchAll(
			"fileinfo", 
			[], 
			["fileinfo.link" => $linkId, "fileinfo.filename" => $filename, "fileinfo.fileextension" => $extension]);
		} catch (Exception $e) {
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' );
			$this->affectedRows = -1;
		}
    	if ($res && !empty($res)){
    		return true;
    	} else {
    		return false;
    	}
	}
	
	/**
	 * return list of all existing links
	 * @return array
	 */
	public function getAllFileLinkIds(){
		$result = [];
		try {
			$this->_isError = false;
			$stmt = $this->db->prepare("SELECT DISTINCT F.link FROM `".$this->TABLE_PREFIX."fileinfo` F");
			$stmt->execute();
			$this->affectedRows = $stmt->rowCount();
			$result = $stmt->fetchAll();
		} catch (Exception $e) {
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' );
			$this->affectedRows = -1;
			$result = [];
		}
		$return = [];
		foreach ($result as $line){
			$return[] = $line['link'];
		}
		return $return;
	}
	
	/**
	 * returns fileinfo by id
	 * @return File|NULL
	 */
	public function getFileInfoById($id){
		$result = [];
		try {
			$this->_isError = false;
			$result = $this->dbconnector->dbFetchAll(
				"fileinfo",
				[],
				["fileinfo.id" => $id]);
		} catch (Exception $e) {
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' );
			$this->affectedRows = -1;
			$result = [];
		}
		$f = NULL;
		foreach ($result as $line){
			$f = new File();
			$f->id = $line['id'];
			$f->link = $line['link'];
			$f->data = $line['data'];
			$f->size = $line['size'];
			$f->added_on = $line['added_on'];
			$f->hashname = $line['hashname'];
			$f->encoding = $line['encoding'];
			$f->mime = $line['mime'];
			$f->fileextension = $line['fileextension'];
			$f->filename = $line['filename'];
			break;
		}
		return $f;
	}
	
	/**
	 * returns fileinfo by id
	 * @return array <File>
	 */
	public function getFilesByLinkId($id){		
		$result = [];
		try {
			$this->_isError = false;
			$result = $this->dbconnector->dbFetchAll(
				"fileinfo",
				[],
				["fileinfo.link" => $id]);
		} catch (Exception $e) {
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' );
			$this->affectedRows = -1;
			$result = [];
		}
		$return = [];
		foreach ($result as $line){
			$f = new File();
			$f->id = $line['id'];
			$f->link = $line['link'];
			$f->data = $line['data'];
			$f->size = $line['size'];
			$f->added_on = $line['added_on'];
			$f->hashname = $line['hashname'];
			$f->encoding = $line['encoding'];
			$f->mime = $line['mime'];
			$f->fileextension = $line['fileextension'];
			$f->filename = $line['filename'];
			$return[$line['id']] = $f;
		}
		return $return;
	}
	
	/**
	 * returns fileinfo by filehash
	 * @return File|NULL
	 */
	public function getFileInfoByHash($hash){
		$result = [];
		try {
			$this->_isError = false;
			$result = $this->dbconnector->dbFetchAll(
				"fileinfo",
				[],
				["fileinfo.hash" => $hash]);
		} catch (Exception $e) {
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' );
			$this->affectedRows = -1;
			$result = [];
		}
		$f = NULL;
		foreach ($result as $line){
			$f = new File();
			$f->id = $line['id'];
			$f->link = $line['link'];
			$f->data = $line['data'];
			$f->size = $line['size'];
			$f->added_on = $line['added_on'];
			$f->hashname = $line['hashname'];
			$f->encoding = $line['encoding'];
			$f->mime = $line['mime'];
			$f->fileextension = $line['fileextension'];
			$f->filename = $line['filename'];
			break;
		}
		return $f;
	}
	
	/**
	 * delete filedata by id
	 * @param integer $id
	 * @return integer affected rows
	 */
	public function deleteFiledataById($id){
		try {
			$this->_isError = false;
			$result = $this->dbconnector->dbDelete(
				"filedata",
				["filedata.id" => $id]);
		} catch (Exception $e) {
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' );
			$this->affectedRows = -1;
			$result = [];
		}
		return !$this->isError();
	}
	
	/**
	 * delete filedata by link id
	 * @param integer $id
	 * @return integer affected rows
	 */
	public function deleteFiledataByLinkId($linkid){
		$sql = "DELETE FROM `".$this->TABLE_PREFIX."filedata` WHERE `id` IN ( SELECT F.data FROM `".$this->TABLE_PREFIX."fileinfo` F WHERE F.link = ? );";
		try {
			$this->fileCloseLastGet();
			$this->_isError = false;
			$stmt = $this->db->prepare($sql);
			$response = $stmt->execute(array($linkid));
		} catch (Exception $e) {
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '" ==>   SQL: '.$sql  );
			$this->affectedRows = -1;
			$result = [];
		}
		return !$this->isError();
	}
	
	/**
	 * delete fileinfo by id
	 * @param integer $id
	 * @return integer affected rows
	 */
	public function deleteFileinfoById($id){
		try {
			$this->_isError = false;
			$result = $this->dbconnector->dbDelete(
				"fileinfo",
				["fileinfo.id" => $id]);
		} catch (Exception $e) {
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' );
			$this->affectedRows = -1;
			$result = [];
		}
		return !$this->isError();
	}
	
	/**
	 * delete fileinfo by link id
	 * @param integer $id
	 * @return integer affected rows
	 */
	public function deleteFileinfoByLinkId($linkid){
		try {
			$this->_isError = false;
			$result = $this->dbconnector->dbDelete(
				"fileinfo",
				["link" => $linkid]);
		} catch (Exception $e) {
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' );
			$this->affectedRows = -1;
			$result = [];
		}
		return !$this->isError();
	}
	
	/**
	 * writes file from filesystem to database
	 * @param string $filename path to existing file
	 * @param integer $filesize in bytes
	 * @return false|int error -> false, last inserted id or
	 */
	public function storeFile2Filedata($filename, $filesize = null){
		return $this->_storeFile2Filedata($filename, $filesize, 'filedata', 'data');
	}
	
	/**
	 * return binary data from database
	 * @param integer $id filedata id
	 * @return false|binary error -> false, binary data
	 */
	public function getFiledataBinary($id){
		return $this->_getFiledataBinary($id, $tablename = 'filedata' , $datacolname = 'data');
	}
	
	// =======================================================
	/**
	 * writes file from filesystem to database
	 * @param string $filename path to existing file
	 * @param integer $filesize in bytes
	 * @param string $tablename database table name
	 * @param string $datacolname database data table column name
	 * @return false|int error -> false, last inserted id or
	 */
	protected function _storeFile2Filedata( $filename, $filesize = null, $tablename = 'filedata' , $datacolname = 'data', $id = NULL){
		$this->fileCloseLastGet();
		if ($id){
			$sql = "INSERT INTO `".$this->TABLE_PREFIX."$tablename` (id, $datacolname) VALUES(?, ?)";
		} else {
			$sql = "INSERT INTO `".$this->TABLE_PREFIX."$tablename` ($datacolname) VALUES(?)";
		}
		$stmt = $this->db->prepare($sql);
		$fp = fopen($filename, 'rb');
		if ($id){
			$stmt->bindParam(1, $insert_id);
			$stmt->bindParam(2, $fp, PDO::PARAM_LOB);
		} else {
			$stmt->bindParam(1, $fp, PDO::PARAM_LOB);
		}
		try {
			$last_id = 0;
			$this->db->beginTransaction();
			$stmt->execute();
			$last_id = $this->db->lastInsertId();
			$this->db->commit();
			fclose($fp);
			return $last_id;
		} catch (Exception $e){
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			$this->fileCloseLastGet();
			return false;
		}
	}
	
	/**
	 * last file get statement
	 * @var mysqli_stmt
	 */
	private $lastFileStmt;
	
	/**
	 * close last stmt of getFiledataBinary
	 */
	public function fileCloseLastGet(){
		if ($this->lastFileStmt != NULL){
			$this->lastFileStmt->closeCursor();
			$this->lastFileStmt = NULL;
		}
	}
	
	/**
	 * return binary data from database
	 * @param integer $id filedata id
	 * @param string $tablename database table name
	 * @param string $datacolname database data table column name
	 * @return false|binary error -> false, binary data
	 */
	protected function _getFiledataBinary($id, $tablename = 'filedata' , $datacolname = 'data'){
		$sql = "SELECT FD.$datacolname FROM `".$this->TABLE_PREFIX."$tablename` FD WHERE id=:dataid";
		$stmt = $this->db->prepare($sql);
		try {
			$stmt->execute(array(':dataid' => $id));
			$this->affectedRows = $stmt->rowCount();
			$stmt->bindColumn(1, $file, PDO::PARAM_LOB);
			$stmt->fetch();
			$this->fileCloseLastGet();
			$this->lastFileStmt = $stmt;
			$this->_isError = false;
			return $file;
			
		} catch (Exception $e) {
			$this->_isError = true;
			$this->msgError = $e->getMessage();
			error_log('DB Error: "'. $this->msgError . '"' . " ==> SQL: " . $sql );
			$this->affectedRows = -1;
			$this->fileCloseLastGet();
			return false;
		}
	}
}
?>