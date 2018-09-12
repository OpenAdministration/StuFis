<?php
/**
 * FRAMEWORK ProtocolHelper
 * wrapper class for HttpClient
 * extended by some client
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


require_once(SYSBASE . '/lib/xmlrpc/hHttpClient.php');

class hServerClient extends hHttpClient{
    //==================================================================================
    // variables
    //==================================================================================
    
    /**
     * Server Url
     * string ends with '/'
     *
     * @example https://domain.org/remote.php/dav/
     * @var string
     */
    protected $server;
    
    /**
     * Server login credentials: username
     *
     * @example user1
     * @var string
     */
    protected $username;
    
    /**
     * Server login credentials: password
     *
     * @example secret_password
     * @var string
     */
    protected $password;
    
    /**
     * CalDav Server protocol
     *
     * @example http|https
     * @var string
     */
    protected $protocol;
    
    /**
     * Server Host
     * string ends with '/'
     *
     * @example domain.org
     * @var string
     */
    protected $host;
    
    /**
     * Server Path
     * Relative Path from Host to Server
     * string ends with '/'
     *
     * @example https://domain.org/remote.php/dav/
     * @var string
     */
    protected $server_path;
    
    //==================================================================================
    // Constructor, Getter, Setter
    //==================================================================================
    
    /**
     * constructor
     */
    function __construct($url, $username, $password, $default_header = array()){
        parent::__construct($default_header);
        if ($url) $this->setServer($url);
        else $this->server = '';
        if (is_string($username)) $this->username = $username;
        else $this->username = '';
        if (is_string($password)) $this->password = $password;
        else $this->password = '';
    }
    
    /**
     * @return the $server
     */
    public function getServer(){
        return $this->server;
    }
    
    /**
     * @param string $server
     */
    public function setServer($server){
        $server = trim(strip_tags($server . ''));
        if ($this->isValidServerUrl($server)){
            $this->server = $server;
            $this->setProtocol($server);
            $this->host = $this->calcServerHost();
            $this->server_path = $this->calcServerPath();
        }else{
            self::setError('Invalid Server Url');
        }
    }
    
    /**
     * @return the $username
     */
    public function getUsername(){
        return $this->username;
    }
    
    /**
     * @param string $username
     */
    public function setUsername($username){
        if (is_string($username)) $this->username = $username;
        else $this->username = '';
    }
    
    /**
     * @return the $password
     */
    public function getPassword(){
        return $this->password;
    }
    
    /**
     * @param string $password
     */
    public function setPassword($password){
        if (is_string($password)) $this->password = $password;
        else $this->password = '';
    }
    
    /**
     * @return the $protocol
     */
    public function getProtocol(){
        return $this->protocol;
    }
    
    /**
     * @return the $host
     */
    public function getServerHost(){
        return $this->host;
    }
    
    /**
     * @return the $server_path
     */
    public function getServerPath(){
        return $this->server_path;
    }
    
    /**
     * test for protocol http|https, for domain, for port, for path , last sign has to a '/'
     *
     * @param server url $in
     */
    public function isValidServerUrl($in){
        //TODO allow url password
        return true;
        $url = $in;
        $re = '/^http[s]?(:|%3A)\/\/(((\w)+((-|\.)(\w+))*)+(\w){0,6}?(:([0-5]?[0-9]{1,4}|6([0-4][0-9]{3}|5([0-4][0-9]{2}|5([0-2][0-9]|3[0-5])))))?\/?)((\w)+((\.|-)(\w)+)*\/?)*$/';
        return !(!preg_match($re, $url) || strlen($url) >= 128);
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
    
    //==================================================================================
    // Validators, Help functions
    //==================================================================================
    
    protected function _doRequest($type, $url, $header = array(), $options = array(), $curl = array()){
        if ($this->username && is_string($this->username) &&
            $this->password && is_string($this->password)){
            parent::__doRequest($type, $url, $this->username, $this->password, $header, $options, $curl);
        }else{
            parent::__doRequest($type, $url, null, null, $header, $options, $curl);
        }
    }
    
    protected function _doRequestAsync($relative_targets, $type, $url, $header = array(), $options = array(), $curl = array()){
        if ($this->username && is_string($this->username) &&
            $this->password && is_string($this->password)){
            parent::__doRequestAsync($relative_targets, $type, $url, $this->username, $this->password, $header, $options, $curl);
        }else{
            parent::__doRequestAsync($relative_targets, $type, $url, null, null, $header, $options, $curl);
        }
    }
    
    /**
     * @param string $protocol
     */
    private function setProtocol($protocol){
        if (mb_substr($protocol, 0, 5) === 'https'){
            $this->protocol = 'https';
        }else{
            $this->protocol = 'http';
        }
    }
    
    //==================================================================================
    // Functions
    //==================================================================================
    
    /* (non-PHPdoc)
     * @see \calendar\hHttpClient::_doRequest()
     */
    
    /**
     * return Hostname of $Server
     *
     * @return false|string
     * @example example.org
     */
    private function calcServerHost(){
        $domain = explode('/', $this->server);
        if (count($domain) > 3){
            return $domain[2];
        }else return false;
    }
    
    /* (non-PHPdoc)
     * @see \calendar\hHttpClient::_doRequest()
     */
    
    /**
     * return second part of cal dav server url ($server without domain)
     *
     * @return string path part of server ($server without domain)
     * @example remote.php/dav
     */
    private function calcServerPath(){
        $dom = explode('/', $this->server);
        $domp = array();
        if ($dom[count($dom) - 1] == '') array_pop($dom);
        if (count($dom) > 3){
            $dom = array_slice($dom, 3 - count($dom));
            $domp = implode('/', $dom);
            return $domp;
        }else return '';
    }
}

?>