<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 26.06.18
 * Time: 02:43
 */

namespace baseclass;


abstract class Enum{
    private static $constCacheArray = null;
    
    protected function __construct(){
        /*
          Preventing instance :)
        */
    }
    
    public static function isValidName($name, $strict = false){
        $constants = self::getConstants();
        
        if ($strict){
            return array_key_exists($name, $constants);
        }
        
        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }
    
    private static function getConstants(){
        if (self::$constCacheArray == null){
            self::$constCacheArray = [];
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$constCacheArray)){
            $reflect = new ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }
    
    public static function isValidValue($value){
        $values = array_values(self::getConstants());
        return in_array($value, $values, $strict = true);
    }
}