<?php
/**
 * Created by PhpStorm.
 * User: lukas
 * Date: 04.03.18
 * Time: 01:53
 */


abstract class Singleton{
    private static $hasCredentialsSet = [];
    private static $instances = [];
    
    /**
     * @param array ...$pars can be multiple or 0 Parameters which will be passed to child::__construct()
     *
     * @return mixed Singleton instance of child, if setCredentials() was not done before or child is not correct configured
     */
    public static function getInstance(...$pars){
        $c = static::class;
        if (!isset(self::$hasCredentialsSet[$c]))
            die("Credentials of " . static::class . " not set!");
        if (!isset(self::$instances[static::class])){
            self::$instances[static::class] = new $c(...$pars);
            return self::$instances[static::class];
        }else{
            if (count($pars) > 0){
                die("Instance allready initialized, no Parameter Allowed after first init");
            }
            return self::$instances[static::class];
        }
    }
    
    /**
     * @param $confArray array with variablename => variablevalue where variablename is a private static Variable in
     *                   child Classes of Singleton
     *
     * @throws Exception
     */
    final private static function setCredentials($confArray){
        
        $visVars = get_class_vars(static::class); // gets all public and protected vars
        foreach ($confArray as $varName => $value){
            if (property_exists(static::class, $varName))
                if (!in_array($varName, array_keys($visVars)))
                    static::static__set($varName, $value);
                else
                    die("static \$$varName ist nicht private in Klasse " . static::class);
            else
                die("static \$$varName existiert nicht in Klasse " . static::class);
        }
    }
    
    abstract static protected function static__set($name, $value);
    
    /**
     * @param array $conf Keys are the Names of Singleton-Childs and inside there will be a key-value pair with Name
     *                    and Value of <u>private static</u> variable from Child.
     */
    public static function configureAll($conf){
        if (!is_array($conf)){
            die("No array passed in Singelton::configureAll()");
        }
        foreach ($conf as $className => $variables){
            if (!is_subclass_of($className, "Singleton"))
                die("$className is not a Child of Singleton!");
            $className::setCredentials($variables);
            self::$hasCredentialsSet[$className] = true;
        }
    }
    
    final public function __clone(){
        //No cloning possible
    }
    
    final public function __debugInfo(){
        //No debug Info
    }
    
    /* wanted Implementation in child to access (and set) PRIVATE static variables*/
    /*
    final static protected function  static__set($name, $value){
        if(property_exists(get_class(), $name))
            self::$$name = $value;
        else
            throw new Exception("$name ist keine Variable in ".get_class());
    }
    */
}


/*
 * Example for Implementation:
class Test extends Singleton {
    private static $test;
    final static protected function  static__set($name, $value){
        if(property_exists(get_class(), $name))
            self::$$name = $value;
        else
            throw new Exception("$name ist keine Variable in ".get_class());
    }
}

ConfigHandler::configure("Test");
*/


