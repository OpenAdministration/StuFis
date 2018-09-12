<?php
/**
 * FRAMEWORK ProtocolHelper
 * XMLRPC Client
 * extends
 *
 * @package           Stura - Referat IT - ProtocolHelper
 * @category          framework
 * @author            michael gnehr
 * @since             17.02.2018
 * @copyright         Copyright (C) Michael Gnehr 2017, All rights reserved
 * @platform          PHP
 * @requirements      PHP 7.0 or higher
 */

namespace xmlrpc;

require_once(SYSBASE . '/lib/xmlrpc/hServerClient.php');


class xrpcClient extends hServerClient{
    //==================================================================================
    // variables
    //==================================================================================
    
    /**
     * @var array
     */
    protected $parsed_result;
    /**
     * @var string
     */
    private $xrpc_path;
    /**
     * @var string
     */
    private $method;
    /**
     * @var string
     */
    private $params;
    /**
     * @var string
     */
    private $rendered_xml;
    
    //==================================================================================
    // Constructor, Getter, Setter
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
        $this->setXrpcPath($xrpc_path);
        $this->method = '';
        $this->params = [];
        
        if (is_string($baseUrl)){
            $server = explode($this->xrpc_path, $baseUrl)[0] . $this->xrpc_path;
            parent::__construct($server, $username, $password);
        }
    }
    
    /**
     * @param string $fullwebdavurl
     */
    public function setBaseUrl($baseUrl){
        if (is_string($baseUrl)){
            $server = explode($this->xrpc_path, $baseUrl)[0] . $this->webdav_path;
            $this->setServer($server);
        }
    }
    
    // setter ====================================================================
    
    /**
     * @return the $fullwebdavurl
     */
    public function getFullUrl(){
        return $this->server;
    }
    
    /**
     * @return the $webdavpath
     */
    public function getXrpcPath(){
        return $this->xrpc_path;
    }
    
    // getter ====================================================================
    
    /**
     * @param string $webdavpath
     */
    public function setXrpcPath($xrpc_path){
        if (is_string($xrpc_path)){
            // webdav path
            if (is_string($xrpc_path))
                $this->xrpc_path = $xrpc_path;
            else
                $this->xrpc_path = '/lib/exe/xmlrpc.php';
        }
    }
    
    /**
     * @return the $method
     */
    public function getMethod(){
        return $this->method;
    }
    
    //==================================================================================
    // Base XMLRPC Setup
    //==================================================================================
    
    // xrpc method -----
    
    /**
     * @param string $method
     */
    public function setMethod($method){
        $this->method = $method;
    }
    
    /**
     * @return the $params
     */
    public function getParams(){
        return $this->params;
    }
    
    // xrpc params -----
    
    /**
     * method parameter
     * format: [
     *        'value',    //string
     *        2,            //integer
     *        ['type', value]
     * ]
     *
     * type
     *        int, i4                Integer (Datentyp)
     *        double                Gleitkommazahl
     *        boolean            Boolesche Variable
     *        string                Zeichenkette
     *        dateTime.iso8601    Datum und Uhrzeit ähnlich[1] dem ISO-Format: jjjj-mm-ddThh:mm:ss+/-XXXX
     *        base64                Base64 kodierte Binärdatei
     *
     * @param array $params
     */
    public function setParams($params){
        $this->params = $params;
    }
    
    /**
     * @return the $rendered_xml
     */
    public function getRendered_xml(){
        return $this->rendered_xml;
    }
    
    // xrpc render -----
    
    /**
     * run request to xmlrpc
     *
     * @param string  $method overwrite $this->method
     * @param unknown $params overwrite $this->params
     */
    public function send($method = null, $params = null){
        if ($method !== null){
            $this->setMethod($method);
        }
        if ($params !== null){
            $this->setParams($params);
        }
        $this->render();
        
        if (DEBUG >= 2){
            echo '<pre>X: ';
            var_dump(htmlspecialchars($this->rendered_xml));
            echo '</pre>';
        }
        
        try{
            $this->_doRequest('POST', $this->server, array(//'Depth' => 0
            ), array(
                'Content-Type' => 'application/xml; charset=utf-8',
                'body' => $this->rendered_xml
            ));
        }catch (\Exception $e){
        }
        if (DEBUG >= 2){
            echo '<pre>C: ';
            var_dump($this->status_code);
            echo '</pre>';
        }
        return ($this->status_code == 200);
    }
    
    /**
     * render method and params to xml
     */
    public function render(){
        $out = '<?xml version="1.0"?>';
        $out .= "<methodCall><methodName>{$this->method}</methodName>";
        if (is_array($this->params) && count($this->params) > 0){
            $out .= '<params>';
            foreach ($this->params as $value){
                if (is_array($value)){
                    //known type
                    if (in_array($value[0], ['int', 'i4', 'double', 'boolean', 'string', 'dateTime.iso8601', 'base64'])){
                        $out .= "<param><value><{$value[0]}>{$value[1]}</{$value[0]}></value></param>";
                        // attr array
                    }else if ($value[0] == 'attr' && is_array($value[1]) && count($value[1]) > 0){
                        $out .= '<param><value><struct>';
                        foreach ($value[1] as $name => $attr){
                            $out .= "<member><name>{$name}</name>";
                            if (is_array($attr)){
                                //known type
                                if (in_array($value[0], ['int', 'i4', 'double', 'boolean', 'string', 'dateTime.iso8601', 'base64'])){
                                    $out .= "<value><{$attr[0]}>{$attr[1]}</{$attr[0]}></value>";
                                    // attr array
                                }else{
                                    error_log('XRPC Client: unknown data type: ' . addslashes($value));
                                }
                            }else{ //autodetect type: int, double, boolean, string
                                $out .= $this->detect_param_type($attr, '', '');
                            }
                            $out .= "</member>";
                        }
                        $out .= '</struct></value></param>';
                    }else{
                        error_log('XRPC Client: unknown data type: ' . addslashes($value));
                    }
                }else{ //autodetect type: int, double, boolean, string
                    $out .= $this->detect_param_type($value);
                }
            }
            $out .= '</params>';
        }
        $out .= '</methodCall>';
        $this->rendered_xml = $out;
    }
    
    /**
     * return value XML
     *
     * @param mixed  $value value to detect
     * @param string $open
     * @param string $close
     *
     * @return string
     */
    private function detect_param_type($value, $open = '<param>', $close = '</param>'){
        if (is_int($value)){
            return "$open<value><int>{$value}</int></value>$close";
        }else if (is_double($value)){
            return "$open<value><double>{$value}</double></value>$close";
        }
        if (is_bool($value)){
            return "$open<value><boolean>" . (($value) ? 'True' : 'False') . "</boolean></value>$close";
        }else{
            return "$open<value><string>{$value}</string></value>$close";
        }
    }
    
    //==================================================================================
    // send server Functions
    //==================================================================================
    
    /**
     * parse result into array
     * return array result
     */
    public function parse_response($return_raw = false){
        $status = $this->getStatusCode();
        if ($status != 207 && $status != 200 && $status != 100){
            //self::setError('Error on Dav Folder Info');
        }
        $content = $this->response->getBody()->getContents();
        
        if (DEBUG >= 3){
            echo '<pre>';
            var_dump(htmlspecialchars($content));
            echo '</pre>';
        }
        
        $xml = new \SimpleXMLElement($content);
        unset($content);
        foreach ($xml->getDocNamespaces() as $strPrefix => $strNamespace){
            $xml->registerXPathNamespace($strPrefix, $strNamespace);
        }
        $json = json_encode($xml);
        $value = $xml->xpath("//params/param/value/*");
        $val_name = $xml->xpath("//struct/member/name");
        $val_val = $xml->xpath("//struct/member/value/*");
        $val_name2 = $xml->xpath('//struct/member/name[text()="id"]/following-sibling::value/string');
        $raw = null;
        if ($return_raw){
            $raw = json_decode($json, true);
        }
        unset($xml);
        $r = [];
        foreach ($value as $v){
            $r[] = $v->__toString();
        }
        foreach ($val_name as $k => $n){
            $r[$n->__toString()] = $val_val[$k]->__toString();
        }
        foreach ($val_name2 as $n){
            $r['paths'][] = $n->__toString();
        }
        unset($value);
        unset($val_name);
        unset($val_val);
        if ($return_raw){
            $r['raw'] = $raw;
        }
        $this->parsed_result = $r;
        return $r;
    }
    
    /**
     * @return the $parsed_result
     */
    public function getParsed_result(){
        return $this->parsed_result;
    }
    
    /**
     * set and throw error message
     *
     * @param string $message error message
     * @param bool   $throw   throw error as \Exception
     */
    protected function setError($message, $throw = true){
        $this->error = get_class() . ': ' . $message;
        if ($throw) throw new \Exception($this->error);
    }
}

?>