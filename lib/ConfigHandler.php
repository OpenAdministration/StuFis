<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 04.03.18
 * Time: 01:53
 */

class ConfigHandler{
    private static $CONFIGPATH = "../config/config.php";
    
    
    /**
     * @param class  $className Name of the Singelton extending Class as String
     * @param string $classPath Filepath, if not already included
     *
     * The config file @ $CONFIGPATH defines an $conf array. The Keys are the Names of Singelton-Childs and inside there
     * there will be a key-value pair with Name and Value of <u>private static</u> variable from Child.
     *
     * @throws Exception
     */
    public static function configure($className, $classPath = ""){
        
        if (!is_subclass_of($className, "Singelton"))
            throw new Exception("$className is not a Child of Singelton!");
        if (isset($classPath) && !empty($classPath))
            require_once $classPath;
        include self::$CONFIGPATH;
        try{
            if (isset($conf[$className])){
                $className::setCredentials($conf[$className]);
            }
        }catch (Exception $e){
            die($e->getMessage());
        }
        
    }
}

abstract class Singelton{
    private static $hasCredentials = false;
    private static $__instance;
    
    
    /**
     * @param array ...$pars can be multiple or 0 Parameters which will be passed to child::__construct()
     *
     * @return Singelton instance of child
     * @throws Exception if setCredentials() was not done before or child is not correct configured
     */
    final public static function getInstance(...$pars){
        if (!self::$hasCredentials)
            throw new Exception("Credentials not set!");
        if (!isset(self::$__instance)){
            $c = get_called_class();
            self::$__instance = new $c(...$pars);
            return self::$__instance;
        }else{
            return self::$__instance;
        }
    }
    
    final public static function setCredentials($confArray){
        $visVars = get_class_vars(static::class);
        
        foreach ($confArray as $varName => $value){
            if (property_exists(static::class, $varName))
                if (!in_array($varName, array_keys($visVars)))
                    static::__static_set($varName, $value);
                else
                    throw new Exception("static \$$varName ist nicht private in Klasse " . static::class);
            else
                throw new Exception("static \$$varName existiert nicht in Klasse " . static::class);
        }
        self::$hasCredentials = true;
        
    }
    
    abstract static protected function __static_set($name, $value);
    
    final public function __clone(){
        //No cloning possible
    }
    
    final public function __debugInfo(){
        //No debug Info
    }
    /* wanted Implementation in child*/
    /*
    final static protected function  __static_set($name, $value){
        if(property_exists(get_class(), $name))
            self::$$name = $value;
        else
            throw new Exception("$name ist keine Variable in ".get_class());
    }
    */
}
/*
class Test extends Singelton {
    private static $test;
    final static protected function  __static_set($name, $value){
        if(property_exists(get_class(), $name))
            self::$$name = $value;
        else
            throw new Exception("$name ist keine Variable in ".get_class());
    }
}

ConfigHandler::configure("Test");
*/


