<?php

namespace framework\baseclass;


use ReflectionClass;

abstract class Enum{
    private static $constCacheArray = null;
    
    protected function __construct(){
        /*
          Preventing instance :)
        */
    }
    
    public static function isValidName($name, $strict = false): bool
    {
        $constants = self::getConstants();
        
        if ($strict){
            return array_key_exists($name, $constants);
        }
        
        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys, true);
    }
    
    private static function getConstants()
    {
        if (self::$constCacheArray === null){
            self::$constCacheArray = [];
        }
        $calledClass = static::class;
        if (!array_key_exists($calledClass, self::$constCacheArray)){
            $reflect = new ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }
    
    public static function isValidValue($value): bool
    {
        $values = array_values(self::getConstants());
        return in_array($value, $values, $strict = true);
    }
}