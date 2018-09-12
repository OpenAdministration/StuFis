<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 18.07.18
 * Time: 15:18
 */

namespace xmlrpc;

require_once(SYSBASE . '/lib/xmlrpc/guzzle6_3_0.phar');

class hHttpClient{
    //==================================================================================
    // variables
    //==================================================================================
    
    /**
     * full url of last estalished connection
     *
     * @example calendars/johndoe/calendar1
     * @var string
     */
    protected $last_request_url;
    
    /**
     *
     * @see http://docs.guzzlephp.org/en/stable/
     * @var class.guzzle
     */
    protected $http_client;
    
    /**
     *
     * @see http://docs.guzzlephp.org/en/stable/
     * @var \GuzzleHttp\Client Request Result object
     */
    protected $response;
    
    /**
     *
     * @see http://docs.guzzlephp.org/en/stable/
     * @var \GuzzleHttp\Client Request Result object
     */
    protected $status_code;
    
    /**
     * Guzzle Client error message if there is an error
     *
     * @var false|string
     */
    protected $error;
    
    /**
     * append default headers to each request
     *
     * @var unknown
     */
    private $default_headers;
    
    //==================================================================================
    // Constructor, Getter, Setter
    //==================================================================================
    
    /**
     * constructor
     *
     * @param array $headers default headers for each request
     */
    public function __construct($default_header = array()){
        $this->last_request_url = null;
        $this->response = null;
        $this->http_client = new \GuzzleHttp\Client();
        $this->default_headers = $default_header;
    }
    
    /**
     * @return the $last_full_url
     */
    public function getLastRequestUrl(){
        return $this->last_request_url;
    }
    
    /**
     * @return the $http_client
     */
    public function getHttpClient(){
        return $this->http_client;
    }
    
    /**
     * @return the $response
     */
    public function getResponse(){
        return $this->response;
    }
    
    /**
     * get http status code of last request
     *
     * @return false|integer the status_code
     */
    public function getStatusCode(){
        if ($this->last_request_url){
            return $this->status_code;
        }else{
            return false;
        }
    }
    
    /**
     * @return the true if $error isset
     */
    public function isError(){
        return ($this->error !== false);
    }
    
    /**
     * @return the $error
     */
    public function getError(){
        return $this->error;
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
    // Functions
    //==================================================================================
    
    /**
     * run request towards an url
     *
     * @param string $type     Request method like: GET|POST|PUT|PROPFIND|REPORT|...
     * @param string $url      target_url for request
     * @param string $username (optional) login credentials (username) for request
     * @param string $password (optional) login credentials (username) for request
     * @param array  $header   additional request header
     * @param array  $options  additional request options
     *
     * @throws \Exception
     */
    protected function __doRequest($type, $url, $username = null, $password = null, $header = array(), $options = array(), $curl = array()){
        $this->error = false;
        if (!$url){
            $this->last_request_url = '';
            self::setError('No Url given.');
        }else if (!$type){
            $this->last_request_url = '';
            self::setError('No Request Type given.');
        }else{
            $this->last_request_url = $url;
            //options array for guzzle
            $opt = [];
            if (is_array($options) && count($options) > 0){
                $opt = $options;
            }
            if ($username && is_string($username) &&
                $password && is_string($password)){
                $opt['auth'] = [$username, $password];
            }
            //header
            $head = array_merge($header, $this->default_headers);
            if (count($head) > 0){
                $opt['headers'] = $head;
            }
            //additional curl flags
            if (count($curl) > 0){
                $opt['curl'] = $curl;
            }
            //run request
            try{
                if (count($opt) > 0){
                    $this->response = $this->http_client->request($type, $url, $opt);
                }else{
                    $this->response = $this->http_client->request($type, $url);
                }
                $this->status_code = $this->response->getStatusCode();
            }catch (\Exception $e){
                if (method_exists($e, 'hasResponse') && $e->hasResponse()){
                    $this->response = $e->getResponse();
                    $this->status_code = $this->response->getStatusCode();
                    switch ($this->status_code){
                        case '400':
                            {
                                self::setError('Bad Request', false);
                                throw $e;
                            }
                            break;
                        case '401':
                            {
                                self::setError('Login failed', false);
                                throw $e;
                            }
                            break;
                        case '403':
                            {
                                self::setError('Access denied', false);
                                throw $e;
                            }
                            break;
                        default:
                            {
                                self::setError($e->getMessage(), false);
                                throw $e;
                            }
                    }
                }else{
                    $this->response = null;
                    $this->status_code = 0;
                    self::setError($e->getMessage(), false);
                    throw $e;
                }
            }
        }
    }
    
    /**
     * run request towards an url
     *
     * @param string[] $relative_targets relative server path for asnc requests
     * @param string   $type             Request method like: GET|POST|PUT|PROPFIND|REPORT|...
     * @param string   $url              target_url for request
     * @param string   $username         (optional) login credentials (username) for request
     * @param string   $password         (optional) login credentials (username) for request
     * @param array    $header           additional request header
     * @param array    $options          additional request options
     *
     * @throws \Exception
     */
    protected function __doRequestAsync($relative_targets, $type, $url, $username = null, $password = null, $header = array(), $options = array(), $curl = array()){
        $this->error = false;
        if (!$url){
            $this->last_request_url = '';
            self::setError('No Url given.');
        }else if (!$type){
            $this->last_request_url = '';
            self::setError('No Request Type given.');
        }else if (!$relative_targets || !is_array($relative_targets) || count($relative_targets) == 0){
            $this->last_request_url = '';
            self::setError('No Async Urls Path given.');
        }else{
            $this->last_request_url = 'multi_target';
            
            //options array for guzzle
            $opt = [];
            if (is_array($options) && count($options) > 0){
                $opt = $options;
            }
            if ($username && is_string($username) &&
                $password && is_string($password)){
                $opt['auth'] = [$username, $password];
            }
            //header
            $head = array_merge($header, $this->default_headers);
            if (count($head) > 0){
                $opt['headers'] = $head;
            }
            //additional curl flags
            if (count($curl) > 0){
                $opt['curl'] = $curl;
            }
            
            $promises = array();
            $results = null;
            
            try{
                if (count($opt) > 0){
                    foreach ($relative_targets as $key => $value){
                        $promises[$key] = $this->http_client->requestAsync($type, $url . $value, $opt);
                    }
                }else{
                    foreach ($relative_targets as $key => $value){
                        $promises[$key] = $this->http_client->requestAsync($type, $url . $value);
                    }
                }
                // Wait on all of the requests to complete. Throws a ConnectException
                // if any of the requests fail
                $results = \GuzzleHttp\Promise\unwrap($promises);
                
                // Wait for the requests to complete, even if some of them fail
                //$results = \GuzzleHttp\Promise\settle($promises)->wait();
                
                $this->status_code = 200;
                $this->response = $results;
            }catch (\Exception $e){
                if (method_exists($e, 'hasResponse') && $e->hasResponse()){
                    $this->response = $e->getResponse();
                    $this->status_code = $this->response->getStatusCode();
                    switch ($this->status_code){
                        case '400':
                            {
                                self::setError('Bad Request', false);
                                throw $e;
                            }
                            break;
                        case '401':
                            {
                                self::setError('Login failed', false);
                                throw $e;
                            }
                            break;
                        case '403':
                            {
                                self::setError('Access denied', false);
                                throw $e;
                            }
                            break;
                        default:
                            {
                                self::setError($e->getMessage(), false);
                                throw $e;
                            }
                    }
                }else{
                    $this->response = null;
                    $this->status_code = 0;
                    self::setError($e->getMessage(), false);
                    throw $e;
                }
            }
        }
    }
}

?>