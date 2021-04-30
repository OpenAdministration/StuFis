<?php

namespace framework;

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
    
    private static $reserved_keywords = 'controller|action|method|not_found|match|path|type|is_suffix|param|children|value';
    
    /**
     * class constructor
     */
    public function __construct($not_found_route = null){
        $this->requested_path = $_SERVER['PATH_INFO'] ?? '/';
        if (is_string($not_found_route)){
            $this->not_found_route = $not_found_route;
        }else{
            $this->not_found_route = "404";
        }
        $this->routes = include SYSBASE . "/config/config.routing.php";
        if (isset($this->routes['not_found'])){
            $this->not_found_route = $this->routes['not_found'];
        }
    }
    
    /**
     * @return string $requested_path
     */
    public function getRequested_path(): string
    {
        return $this->requested_path;
    }
    
    /**
     * @return string $not_found_route
     */
    public function getNot_found_route(): string
    {
        return $this->not_found_route;
    }
    
    /**
     * @param string $not_found_route
     */
    public function setNot_found_route(string $not_found_route): void
    {
        if (is_string($not_found_route)){
            $this->not_found_route = $not_found_route;
        }
    }
    
    // public member functions --------------------------------
    
    public function route($path = null): array
    {
        if ($path === null || !is_string($path)){
            $path = $this->requested_path;
        }
        //route request
        $routeInfo = $this->_route($path, [$this->routes]);
        
        //check request method ------------------
        if ($routeInfo['controller'] !== 'error'
            && stripos($routeInfo['method'], $_SERVER['REQUEST_METHOD']) === false){
            $routeInfo = [
                'method' => $_SERVER['REQUEST_METHOD'],
                'controller' => 'error',
                'action' => '403',
            ];
        }
        return $routeInfo;
    }
    
    // private member functions -------------------------------
    
    /**
     * @param $path
     * @param $routes
     *
     * @return array
     * @throws Exception
     */
    private function _route($path, $routes): array
    {
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
            }
            if (!isset($route['type'])){
                echo '<div class="bg-error alert alert-error orange">Falsch konfigurierte Route. Parameter "type" fehlt:</div>';
                echo '<pre>';
                var_export($route);
                echo '</pre>';
                throw new Exception("Router: Error on configuration. Parameter 'type' is missing.");
            }
            //check if current path matches route path
            if ((($route['type'] === 'path' && $route['path'] === $current)
                    || ($route['type'] === 'pattern' && preg_match('/^' . $route['path'] . '$/', $current)))
                && !isset($route['is_suffix'])){
                $found = true;
                if (isset($route['controller'])) $ret['controller'] = $route['controller'];
                if (isset($route['action'])) $ret['action'] = $route['action'];
                if (isset($route['method'])) $ret['method'] = $route['method'];
                if (isset($route['not_found'])) $this->not_found_route = $route['not_found'];
                
                foreach ($route as $k => $v){
                	if (!preg_match('/^('.self::$reserved_keywords.')$/', $k)){
                		$ret[$k] = $v;
                	}
                }
                //is pattern match
                $matches = null;
                if ($route['type'] === 'pattern' && preg_match('/^' . $route['path'] . '$/', $current, $matches)){
                    $ret[$route['param']] = $matches[$route['match'] ?? 0];
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
                    if (isset($route['not_found'])) $this->not_found_route = $route['not_found'];
                    foreach ($route as $k => $v){
                    	if (!preg_match('/^('.self::$reserved_keywords.')$/', $k)){
                    		$ret[$k] = $v;
                    	}
                    }
                    $ret[$route['param']] = $matches[$route['match'] ?? 0];
                }
            }
            if (isset($route['value'], $route['param']) && $found){
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
                        if ($k !== 'path'){
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
            if ($found === true) {
                break;
            }
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