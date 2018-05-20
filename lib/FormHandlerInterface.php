<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 15.05.18
 * Time: 19:45
 */

interface FormHandlerInterface{
    public static function initStaticVars();
    
    public static function getStateString($statename);
    
    public function updateSavedData($data);
    
    public function setState($stateName);
    
    public function getNextPossibleStates();
    
    public function render();
    
    public function getID();
}