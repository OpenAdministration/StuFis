<?php

include dirname(__FILE__, 2) . "/config/config.routing.php";

/**
 * @author michael
 *
 */
class Router{
    // member variables ---------------------------------------
    /**
     *
     * @var string;
     */
    private $requested_path;
    
    /**
     *
     * @var array
     */
    private $routes;
    
    /**
     *
     * @var string
     */
    private $not_found_route;
    
    // constructor, getter, setter ----------------------------
    
    /**
     * class constuctor
     */
    function __construct($not_found_route = null){
        $this->requested_path = isset($_SERVER['PATH_INFO']) ? ($_SERVER['PATH_INFO'] != '/' ? $_SERVER['PATH_INFO'] : '/') : '/';
        if (is_string($not_found_route)){
            $this->not_found_route = $not_found_route;
        }else{
            $this->not_found_route = "404";
        }
        global $routing;
        $this->routes = $routing;
        if (isset($this->routes['not_found'])){
            $this->not_found_route = $this->routes['not_found'];
        }
    }
    
    /**
     * @return the $requested_path
     */
    public function getRequested_path(){
        return $this->requested_path;
    }
    
    /**
     * @return the $not_found_route
     */
    public function getNot_found_route(){
        return $this->not_found_route;
    }
    
    /**
     * @param string $not_found_route
     */
    public function setNot_found_route($not_found_route){
        if (is_string($not_found_route)){
            $this->not_found_route = $not_found_route;
        }
    }
    
    // public member functions --------------------------------
    
    public function route($path = null){
        if ($path === null || !is_string($path)){
            $path = $this->requested_path;
        }
        //route request
        $routeInfo = $this->_route($path, [$this->routes]);
        
        //check request method ------------------
        if ($routeInfo['controller'] != 'error'
            && strpos(strtoupper($routeInfo['method']), strtoupper($_SERVER['REQUEST_METHOD'])) === false){
            $routeInfo = [
                'method' => $_SERVER['REQUEST_METHOD'],
                'controller' => 'error',
                'action' => '403',
            ];
        }
        return $routeInfo;
    }
    
    // private member functions -------------------------------
    
    private function _route($path, $routes){
        $current = null;
        $next = null;
        if (mb_strpos($path, '/') !== false){
            $current = mb_substr($path, 0, mb_strpos($path, '/'));
            $next = mb_substr($path, mb_strpos($path, '/') + 1);
        }else{
            $current = $path;
            $next = false;
        }
        
        //remember current default route
        $old_not_found = $this->not_found_route;
        $ret = ['path' => $current];
        //handle current
        $found = false;
        //handle all matches on current level
        foreach ($routes as $route){
            // throw error if path or type not set
            if (!isset($route['path'])){
                echo '<div class="bg-error alert alert-error orange">Falsch konfigurierte Route. Parameter "path" fehlt:</div>';
                echo '<pre>';
                var_export($route);
                echo '</pre>';
                throw new Exception("Router: Error on configuration. Parameter 'path' is missing.");
                return null;
            }
            if (!isset($route['type'])){
                echo '<div class="bg-error alert alert-error orange">Falsch konfigurierte Route. Parameter "type" fehlt:</div>';
                echo '<pre>';
                var_export($route);
                echo '</pre>';
                throw new Exception("Router: Error on configuration. Parameter 'type' is missing.");
                return null;
            }
            //check if current path matches route path
            if (($route['type'] === 'path' && $route['path'] === $current
                    || $route['type'] === 'pattern' && preg_match('/^' . $route['path'] . '$/', $current))
                && !isset($route['is_suffix'])){
                $found = true;
                if (isset($route['controller'])) $ret['controller'] = $route['controller'];
                if (isset($route['action'])) $ret['action'] = $route['action'];
                if (isset($route['method'])) $ret['method'] = $route['method'];
                if (isset($route['load'])) $ret['load'] = $route['load'];
                if (isset($route['not_found'])) $this->not_found_route = $route['not_found'];
                //is pattern match
                $matches = null;
                if ($route['type'] === 'pattern' && preg_match('/^' . $route['path'] . '$/', $current, $matches)){
                    $ret[$route['param']] = $matches[(isset($route['match'])) ? $route['match'] : 0];
                }
            }else if (isset($route['is_suffix']) // suffix match - pattern only
                && $route['type'] === 'pattern'){
                $tmpCurrent = $current . (($next) ? '/' . $next : '');
                $matches = null;
                if (preg_match('/^' . $route['path'] . '$/', $tmpCurrent, $matches)){
                    $found = true;
                    if (isset($route['controller'])) $ret['controller'] = $route['controller'];
                    if (isset($route['action'])) $ret['action'] = $route['action'];
                    if (isset($route['method'])) $ret['method'] = $route['method'];
                    if (isset($route['load'])) $ret['load'] = $route['load'];
                    if (isset($route['not_found'])) $this->not_found_route = $route['not_found'];
                    $ret[$route['param']] = $matches[(isset($route['match'])) ? $route['match'] : 0];
                }
            }
            if ($found && isset($route['value']) && isset($route['param'])){
                $ret[$route['param']] = $route['value'];
            }
            
            //may handle children, if $routes contains children && path is not false or empty
            if ($found && !isset($route['is_suffix']) && isset($route['children']) && $next != false){
                //handle children
                $tmpRet = $this->_route($next, $route['children']);
                //merge children if not null or false or not found flag set
                if ($tmpRet && !isset($tmpRet['not_found'])){
                    $ret['path'] .= '/' . $tmpRet['path'];
                    foreach ($tmpRet as $k => $v){
                        if ($k != 'path'){
                            $ret[$k] = $v;
                        }
                    }
                }else if (isset($tmpRet['not_found'])){
                    $ret['not_found'] = false;
                    $ret['controller'] = $tmpRet['controller'];
                    $ret['action'] = $tmpRet['action'];
                }else{
                    // else not found exception
                    $ret['not_found'] = true;
                    $ret['controller'] = 'error';
                    $ret['action'] = $this->not_found_route;
                }
            }
            
            //reset not found variable
            $this->not_found_route = $old_not_found;
            //break if route matches
            if ($found == true) break;
        }
        // path not found route
        if (!$found && !isset($ret['not_found'])){
            $ret['not_found'] = true;
            $ret['controller'] = 'error';
            $ret['action'] = $this->not_found_route;
        }
        return $ret;
    }
    
}

?>