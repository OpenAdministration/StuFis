<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 15.05.18
 * Time: 19:45
 */

abstract class FormHandlerInterface extends Renderer{
    
    abstract public static function initStaticVars();
    
    abstract public static function getStateString($statename);
    
    abstract public function updateSavedData($data);
    
    abstract public function setState($stateName);
    
    abstract public function getNextPossibleStates();
    
    abstract public function getID();
    
}