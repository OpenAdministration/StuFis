<?php
/**
 * FRAMEWORK ProtocolHelper
 * XMLRPC Client
 * extends
 *
 * for function parameter look at https://www.dokuwiki.org/devel:xmlrpc
 *
 * @package           Stura - Referat IT - ProtocolHelper
 * @category          framework
 * @author            michael gnehr
 * @since             17.02.2018
 * @copyright         Copyright (C) Michael Gnehr 2017, All rights reserved
 * @platform          PHP
 * @requirements      PHP 7.0 or higher
 *
 */

require_once(dirname(__FILE__, 1) . '/class.xrpcClient.php');

class wikiClient extends xrpcClient{
    //==================================================================================
    // variables
    //==================================================================================
    
    
    //==================================================================================
    // Constructor, Getter, Setter
    //==================================================================================
    
    private static $protomap = PROTOMAP;
    
    //==================================================================================
    // wiki Functions
    //==================================================================================
    
    /**
     * constructor
     *
     * @param string $baseUrl
     * @param string $username
     * @param string $password
     * @param string $xrpc_path
     */
    function __construct($baseUrl, $username = "", $password = "", $xrpc_path = '/lib/exe/xmlrpc.php'){
        parent::__construct($baseUrl, $username, $password, $xrpc_path);
    }
    
    /**
     * get docuWiki Version
     *
     * @return string
     */
    public function getVersion(){
        $this->setMethod('dokuwiki.getVersion');
        if ($this->send()){
            return $this->parse_response()[0];
        }else{
            return '';
        }
    }
    
    /**
     * get docuWiki time
     *
     * @return string
     */
    public function getTime(){
        $this->setMethod('dokuwiki.getTime');
        if ($this->send()){
            return $this->parse_response()[0];
        }else{
            return '';
        }
    }
    
    /**
     * get docuWiki XMLRPC API Version
     *
     * @return string
     */
    public function getXMLRPCAPIVersion(){
        $this->setMethod('dokuwiki.getXMLRPCAPIVersion');
        if ($this->send()){
            return $this->parse_response()[0];
        }else{
            return '';
        }
    }
    
    /**
     * get docuWiki title
     *
     * @return string
     */
    public function getTitle(){
        $this->setMethod('dokuwiki.getTitle');
        if ($this->send()){
            return $this->parse_response()[0];
        }else{
            return '';
        }
    }
    
    /**
     * docuWiki append text to wikipage
     *
     * @param string $filename
     * @param string $text
     * @param array  $attr
     *
     * @return boolean
     */
    public function appendPage($filename = '', $text = '', $attr = null){
        $this->setMethod('dokuwiki.appendPage');
        $param = [];
        if ($filename == '' || !is_string($filename)){
            return;
        }
        $param[] = $filename;
        if (!is_string($text)){
            return;
        }
        $param[] = htmlspecialchars($text);
        if ($attr != null){
            $param[2] = ['attr', $attr];
        }
        $this->setParams($param);
        if ($this->send()){
            $this->parse_response();
            return ($this->parsed_result[0] == 1);
        }else{
            return '';
        }
    }
    
    /**
     * docuWiki delete wiki page
     *
     * @param string $filename
     *
     * @return string
     */
    public function deletePage($filename){
        return $this->putPage($filename, '');
    }
    
    /**
     * docuWiki create/overwrite wikipage
     *
     * @param string $filename
     * @param string $text
     * @param array  $attr
     *
     * @return boolean
     */
    public function putPage($filename = '', $text = '', $attr = null){
        $this->setMethod('wiki.putPage');
        $param = [];
        if ($filename == '' || !is_string($filename)){
            return;
        }
        $param[] = $filename;
        if (!is_string($text)){
            return;
        }
        $param[] = htmlspecialchars($text);
        if ($attr != null){
            $param[2] = ['attr', $attr];
        }
        $this->setParams($param);
        if ($this->send()){
            $this->parse_response();
            return (isset($this->parsed_result[0]) && $this->parsed_result[0] == 1);
        }else{
            return '';
        }
    }
    
    /**
     * get docuWiki Page - raw wiki text
     *
     * @param string $filename
     *
     * @return string
     */
    public function getPage($filename = ''){
        $this->setMethod('wiki.getPage');
        $param = [];
        if ($filename != ''){
            $param[] = $filename;
        }
        $this->setParams($param);
        if ($this->send()){
            $this->parse_response();
            return $this->parsed_result[0];
        }else{
            return '';
        }
    }
    
    /**
     * get docuWiki Page - html wiki text
     *
     * @param string $filename
     *
     * @return string
     */
    public function getPageHTML($filename = ''){
        $this->setMethod('wiki.getPageHTML');
        $param = [];
        if ($filename != ''){
            $param[] = $filename;
        }
        $this->setParams($param);
        if ($this->send()){
            $this->parse_response();
            return $this->parsed_result[0];
        }else{
            return '';
        }
    }
    
    /**
     * get dokuWiki listAttachements
     *
     * @param string $namespace
     * @param array  $attr
     *
     * @return string
     */
    public function listAttachements($namespace, $attr = null){
        $this->setMethod('wiki.getAttachments');
        $param = [];
        if ($namespace != '' || $attr != null){
            $param[] = $namespace;
        }
        if ($attr != null){
            $param[1] = ['attr', $attr];
        }
        $this->setParams($param);
        if ($this->send()){
            $this->parse_response();
            return (isset($this->parsed_result['paths'])) ? $this->parsed_result['paths'] : false;
        }else{
            return '';
        }
    }
    
    /**
     * get dokuWiki getAttachement
     *
     * @param string $id file id
     *
     * @return string
     */
    public function getAttachement($id){
        $this->setMethod('wiki.getAttachment');
        $param = [];
        if ($id == '' || !is_string($id)){
            return;
        }
        $param[] = $id;
        
        $this->setParams($param);
        if ($this->send()){
            $this->parse_response();
            return (isset($this->parsed_result[0])) ? $this->parsed_result[0] : '';
        }else{
            return '';
        }
    }
    
    /**
     * get dokuWiki putAttachements
     * attr
     *    ['key' => 'value']
     *
     * @param string $id     file id
     * @param string $base64 file data base64 encoded
     * @param string $attr
     *
     * @return string
     */
    public function putAttachement($id, $base64, $attr = null){
        $this->setMethod('wiki.putAttachment');
        $param = [];
        if ($id == '' || !is_string($id)){
            return;
        }
        $param[] = $id;
        if ($base64 == '' || !is_string($base64)){
            return;
        }
        $param[] = ['base64', $base64];
        if ($attr != null){
            $param[] = ['attr', $attr];
        }
        $this->setParams($param);
        if ($this->send()){
            $this->parse_response();
            return isset($this->parsed_result[0]);
        }else{
            return '';
        }
    }
    
    /**
     * get dokuWiki deleteAttachement
     *
     * @param string $id
     *
     * @return string
     */
    public function deleteAttachement($id){
        $this->setMethod('wiki.deleteAttachment');
        $param = [];
        if ($id == '' || !is_string($id)){
            return;
        }
        $param[] = $id;
        
        $this->setParams($param);
        if ($this->send()){
            $this->parse_response();
            return (isset($this->parsed_result[0]));
        }else{
            return '';
        }
    }
    
    /**
     * get docuWiki Version
     *
     * @return array
     */
    public function getSturaProtokolls(){
        return $this->getPagelistAutoDepth(self::$protomap['stura'][1]);
    }
    
    //==================================================================================
    // protocol helper Functions
    //==================================================================================
    
    /**
     * get dokuWiki Pagelist - automatically set depth attribute
     * this function will only return the next page layer - without recursive subpages
     *
     * @param string $namespace
     * @param array  $attr
     *
     * @return array|string
     */
    public function getPagelistAutoDepth($namespace = ''){
        $attr = ['depth' => substr_count($namespace, ':') + 2];
        return $this->getPagelist($namespace, $attr);
    }
    
    /**
     * get dokuWiki Pagelist
     *
     * @param string $namespace
     * @param array  $attr
     *
     * @return array|string
     */
    public function getPagelist($namespace = '', $attr = null){
        $this->setMethod('dokuwiki.getPagelist');
        $param = [];
        if ($namespace != '' || $attr != null){
            $param[] = $namespace;
        }
        if ($attr != null){
            $param[1] = ['attr', $attr];
        }
        $this->setParams($param);
        if ($this->send()){
            $this->parse_response();
            return (isset($this->parsed_result['paths'])) ? $this->parsed_result['paths'] : '';
        }else{
            return '';
        }
    }
    
    /**
     * get docuWiki Version
     *
     * @return array
     */
    public function getSturaInternProtokolls(){
        return $this->getPagelistAutoDepth(self::$protomap['stura'][0]);
    }
}

?>
