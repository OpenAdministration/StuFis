<?php

/**
 * FRAMEWORK ProtocolHelper
 * database connection
 * implements framework database functions
 *
 * @package           Stura - Referat IT - ProtocolHelper
 * @category          framework
 * @author            michael g
 * @author            Stura - Referat IT <ref-it@tu-ilmenau.de>
 * @since             17.02.2018
 * @copyright         Copyright (C) 2018 - All rights reserved
 * @platform          PHP
 * @requirements      PHP 7.0 or higher
 */

namespace framework\file;

use Exception;
use framework\DBConnector;
use framework\render\ErrorHandler;
use PDO;

/**
 *
 * @author  Michael Gnehr <michael@gnehr.de>
 * @since   01.03.2017
 * @package SILMPH_framework
 */
class DbFilePDO{
    /**
     * database member
     *
     * @var PDO
     */
    public $db;
    
    /**
     * database connector
     *
     * @var DBConnector
     * @see DBConnector.php
     */
    public $dbconnector;
    
    private $TABLE_PREFIX;
    
    /**
     * db error state: last request was error or not
     *
     * @var bool
     */
    private $_isError = false;
    
    /**
     * last error message
     *
     * @var $string
     */
    private $msgError = '';
    
    /**
     * db state: db was closed or not
     *
     * @var bool
     */
    private $_isClose = false;
    
    /**
     * Contains affected rows after update, delete and insert requests
     * set by memberfunction: protectedInsert
     *
     * @var integer
     */
    private $affectedRows = 0;

    
    // ======================== HELPER FUNCTIONS ======================================================
    
    /**
     * class constructor
     *
     * @param DBConnector $dbconnector
     */
    public function __construct(DBConnector $dbconnector){
        $this->db = $dbconnector->getPdo();
        $this->dbconnector = $dbconnector;
        $this->TABLE_PREFIX = $dbconnector->getDbPrefix();
        if (!$this->db){
            $this->_isError = true;
            $this->msgError = "Connect failed: No PDO object\n";
            ErrorHandler::_renderError($this->msgError);
            exit();
        }
    }

    /**
     * generate reference array of array
     *
     * @param array $arr
     *
     * @return array
     */
    public function refValues(array $arr): array
    {
        if (strnatcmp(PHP_VERSION, '5.3') >= 0) //Reference is required for PHP 5.3+
        {
            $refs = array();
            foreach ($arr as $key => $value) {
                $refs[$key] = &$value;
            }
            return $refs;
        }
        return $arr;
    }

    // ======================== BASE FUNCTIONS ========================================================
    
    // ====================================================
    
    /**
     * db: return affected rows
     *
     * @return int affected rows
     */
    public function affectedRows(): int
    {
        return $this->affectedRows;
    }
    
    /**
     * @return bool $this->_isClose
     */
    public function isClose(): bool
    {
        return $this->_isClose;
    }
    
    /**
     * @retun string last error message
     */
    public function getError(): string
    {
        return $this->msgError;
    }
    
    /**
     * only delete reference to pdo object
     * you need to remove all other references on your own
     * connection stay alive for the lifetime of that PDO object
     */
    public function close(): void
    {
        if (!$this->_isClose){
            $this->_isClose = true;
            if ($this->db){
                $this->db = null;
                $this->dbconnector = null;
            }
        }
    }

    /**
     * create filedata entry, set datablob null, set diskpath to file
     *
     * @param string $filepath
     * @return false|int new inserted id or false
     */
    public function createFileDataPath(string $filepath){
        if ($filepath === '') {
            return false;
        }
        try{
            $this->dbconnector->dbInsert('filedata', ['diskpath' => $filepath]);
            $this->_isError = false;
            return $this->lastInsertId();
        }catch (Exception $e){
            $this->_isError = true;
            $this->msgError = $e->getMessage();
            error_log('DB Error: "' . $this->msgError . '"');
            $this->affectedRows = -1;
            return false;
        }
    }
    
    // database file related functions ========================================================
    
    /**
     * db: return las inserted id
     *
     * @return int last inserted id
     */
    public function lastInsertId(): int
    {
        return $this->db->lastInsertId();
    }
    
    /**
     * create fileentry on table fileinfo
     *
     * @param File $f
     *
     * @return false|int new inserted id or false
     */
    public function createFile(File $f){
        try{
            $this->dbconnector->dbInsert('fileinfo', [
                'link' => $f->link,
                'hashname' => $f->hashname,
                'filename' => $f->filename,
                'size' => $f->size,
                'fileextension' => $f->fileextension,
                'mime' => $f->mime,
                'encoding' => $f->encoding,
                'data' => $f->data,
                'added_on' => ($f->added_on) ?: date_create()->format('Y-m-d H:i:s')
            ]);
            $this->_isError = false;
            return $this->lastInsertId();
        }catch (Exception $e){
            $this->_isError = true;
            $this->msgError = $e->getMessage();
            error_log('DB Error: "' . $this->msgError . '"');
            $this->affectedRows = -1;
            return false;
        }
    }
    
    /**
     * update file column 'data' of fileinfo entry
     *
     * @param File $f
     *
     * @return boolean success
     */
    public function updateFile_DataId(File $f): bool
    {
        try{
            $this->_isError = false;
            $this->dbconnector->dbUpdate(
                'fileinfo',
                ['id' => $f->id],
                ['data' => $f->data]
            );
            return true;
        }catch (Exception $e){
            $this->_isError = true;
            $this->msgError = $e->getMessage();
            error_log('DB Error: "' . $this->msgError . '"');
            $this->affectedRows = -1;
            return false;
        }
    }

    /**
     * prevent duplicate files for one link/directory
     *
     * @param string $linkId link or directory name - hier beleg name/id
     * @param string $filename
     * @param string $extension
     * @return bool
     */
    public function checkFileExists(string $linkId, string $filename, string $extension): bool
    {
        $res = null;
        try{
            $this->_isError = false;
            $res = $this->dbconnector->dbFetchAll(
                "fileinfo",
                [DBConnector::FETCH_ASSOC],
                [],
                ["fileinfo.link" => $linkId, "fileinfo.filename" => $filename, "fileinfo.fileextension" => $extension]);
        }catch (Exception $e){
            $this->_isError = true;
            $this->msgError = $e->getMessage();
            error_log('DB Error: "' . $this->msgError . '"');
            $this->affectedRows = -1;
        }
        return $res;
    }
    
    /**
     * return list of all existing links
     *
     * @return array
     */
    public function getAllFileLinkIds(): array
    {
        $result = [];
        try{
            $this->_isError = false;
            $stmt = $this->db->query("SELECT DISTINCT F.link FROM `" . $this->TABLE_PREFIX . "fileinfo` F");
            $this->affectedRows = $stmt->rowCount();
            $result = $stmt->fetchAll();
        }catch (Exception $e){
            $this->_isError = true;
            $this->msgError = $e->getMessage();
            error_log('DB Error: "' . $this->msgError . '"');
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
     *
     * @param int $id
     * @return File|NULL
     */
    public function getFileInfoById(int $id): ?File
    {
        try{
            $this->_isError = false;
            $result = $this->dbconnector->dbFetchAll(
                "fileinfo",
                [DBConnector::FETCH_ASSOC],
                [],
                ["fileinfo.id" => $id]);
        }catch (Exception $e){
            $this->_isError = true;
            $this->msgError = $e->getMessage();
            error_log('DB Error: "' . $this->msgError . '"');
            $this->affectedRows = -1;
            return null;
        }
        if(count($result) === 0){
            return null;
        }
        $line = $result[0];
        return $this->generateFileFromDbLine($line);
    }

    private function generateFilesFromDbResult(array $dbRes) : array
    {
        $res = [];
        foreach ($dbRes as $line){
            $res[] = $this->generateFileFromDbLine($line);
        }
        return $res;
    }

    private function generateFileFromDbLine(array $line) : ?File
    {
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
        return $f;
    }
    
    /**
     * returns fileinfo by id
     *
     * @return array <File>
     */
    public function getFilesByLinkId($id): array
    {
        $result = [];
        try{
            $this->_isError = false;
            $result = $this->dbconnector->dbFetchAll(
                "fileinfo",
                [DBConnector::FETCH_ASSOC],
                [],
                ["fileinfo.link" => $id]);
        }catch (Exception $e){
            $this->_isError = true;
            $this->msgError = $e->getMessage();
            error_log('DB Error: "' . $this->msgError . '"');
            $this->affectedRows = -1;
            $result = [];
        }
        return $this->generateFilesFromDbResult($result);
    }

    /**
     * returns fileinfo by filehash
     *
     * @param $hash
     * @return File|NULL
     */
    public function getFileInfoByHash($hash): ?File
    {
        $result = [];
        try{
            $this->_isError = false;
            $result = $this->dbconnector->dbFetchAll(
                "fileinfo",
                [DBConnector::FETCH_ASSOC],
                [],
                ["hashname" => $hash]);
        }catch (Exception $e){
            $this->_isError = true;
            $this->msgError = $e->getMessage();
            error_log('DB Error: "' . $this->msgError . '"');
            $this->affectedRows = -1;
            return null;
        }
        if(count($result) === 0){
            return null;
        }
        $line = $result[0];
        return $this->generateFileFromDbLine($line);
    }
    
    /**
     * delete filedata by id
     *
     * @param integer $id
     *
     * @return integer affected rows
     */
    public function deleteFiledataById($id){
        try{
            $this->_isError = false;
            $result = $this->dbconnector->dbDelete(
                "filedata",
                ["filedata.id" => $id]);
        }catch (Exception $e){
            $this->_isError = true;
            $this->msgError = $e->getMessage();
            error_log('DB Error: "' . $this->msgError . '"');
            $this->affectedRows = -1;
            $result = [];
        }
        return $result;
    }
    
    /**
     * @return int $this->_isError
     */
    public function isError(){
        return $this->_isError;
    }

    /**
     * delete filedata by link id
     *
     * @param int $linkId
     * @return bool affected rows
     */
    public function deleteFiledataByLinkId(int $linkId): bool
    {
        $sql = "DELETE FROM `" . $this->TABLE_PREFIX . "filedata` WHERE `id` IN ( SELECT F.data FROM `" . $this->TABLE_PREFIX . "fileinfo` F WHERE F.link = ? );";
        try{
            $this->_isError = false;
            $stmt = $this->db->prepare($sql);
            $response = $stmt->execute(array($linkId));
        }catch (Exception $e){
            $this->_isError = true;
            $this->msgError = $e->getMessage();
            error_log('DB Error: "' . $this->msgError . '" ==>   SQL: ' . $sql);
            $this->affectedRows = -1;
            $result = [];
        }
        return !$this->isError();
    }
    
    /**
     * delete fileinfo by id
     *
     * @param integer $id
     *
     * @return integer affected rows
     */
    public function deleteFileinfoById($id){
        try{
            $this->_isError = false;
            $result = $this->dbconnector->dbDelete(
                "fileinfo",
                ["fileinfo.id" => $id]);
        }catch (Exception $e){
            $this->_isError = true;
            $this->msgError = $e->getMessage();
            error_log('DB Error: "' . $this->msgError . '"');
            $this->affectedRows = -1;
            $result = [];
        }
        return !$this->isError();
    }

    /**
     * delete fileinfo by link id
     *
     * @param int $linkId
     * @return bool affected rows
     */
    public function deleteFileinfoByLinkId(int $linkId): bool
    {
        try{
            $this->_isError = false;
            $result = $this->dbconnector->dbDelete(
                "fileinfo",
                ["link" => $linkId]);
        }catch (Exception $e){
            $this->_isError = true;
            $this->msgError = $e->getMessage();
            error_log('DB Error: "' . $this->msgError . '"');
            $this->affectedRows = -1;
            $result = [];
        }
        return !$this->isError();
    }
    
    // =======================================================
    
    /**
     * writes file from filesystem to database
     *
     * @param string  $filename path to existing file
     * @param integer $filesize in bytes
     *
     * @return false|int error -> false, last inserted id or
     */
    public function storeFile2Filedata($filename, $filesize = null){
        return $this->_storeFile2Filedata($filename, $filesize, 'filedata', 'data');
    }

    /**
     * writes file from filesystem to database
     *
     * @param string $filename path to existing file
     * @param null $filesize in bytes
     * @param string $tablename database table name
     * @param string $datacolname database data table column name
     * @param bool $id
     * @return false error -> false, last inserted id or
     */
    protected function _storeFile2Filedata(string $filename, $filesize = null, string $tablename = 'filedata', string $datacolname = 'data', $id = false): bool
    {
        if ($id){
            $sql = "INSERT INTO `" . $this->TABLE_PREFIX . "$tablename` (id, $datacolname) VALUES(?, ?)";
        }else{
            $sql = "INSERT INTO `" . $this->TABLE_PREFIX . "$tablename` ($datacolname) VALUES(?)";
        }
        $stmt = $this->db->prepare($sql);
        $fp = fopen($filename, 'rb');
        if ($id){
            $stmt->bindParam(1, $insert_id);
            $stmt->bindParam(2, $fp, PDO::PARAM_LOB);
        }else{
            $stmt->bindParam(1, $fp, PDO::PARAM_LOB);
        }
        try{
            $last_id = 0;
            $this->db->beginTransaction();
            $stmt->execute();
            $last_id = $this->db->lastInsertId();
            $this->db->commit();
            fclose($fp);
            return $last_id;
        }catch (Exception $e){
            $this->_isError = true;
            $this->msgError = $e->getMessage();
            error_log('DB Error: "' . $this->msgError . '"' . " ==> SQL: " . $sql);
            $this->affectedRows = -1;
            return false;
        }
    }
    
    /**
     * return binary data from database
     *
     * @param integer $id filedata id
     *
     * @return bool|string error -> false, binary data
     */
    public function getFiledataBinary(int $id){
        return $this->_getFiledataBinary($id);
    }
    
    /**
     * return binary data from database
     *
     * @param integer $id          filedata id
     * @param string  $tablename   database table name
     * @param string  $datacolname database data table column name
     *
     * @return bool|string error -> false, binary data
     */
    protected function _getFiledataBinary(int $id, $tablename = 'filedata', $datacolname = 'data'){
        $sql = "SELECT FD.$datacolname FROM `" . $this->TABLE_PREFIX . "$tablename` FD WHERE id=:dataid";
        $stmt = $this->db->prepare($sql);
        try{
            $stmt->execute(array(':dataid' => $id));
            $this->affectedRows = $stmt->rowCount();
            $stmt->bindColumn(1, $file, PDO::PARAM_LOB);
            $stmt->fetch();
            $this->_isError = false;
            return $file;
            
        }catch (Exception $e){
            $this->_isError = true;
            $this->msgError = $e->getMessage();
            error_log('DB Error: "' . $this->msgError . '"' . " ==> SQL: " . $sql);
            $this->affectedRows = -1;
            return false;
        }
    }
}
