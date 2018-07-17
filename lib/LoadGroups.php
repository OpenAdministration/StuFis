<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 26.06.18
 * Time: 02:39
 */

class LoadGroups extends baseclass\Enum{
    const __default = [];
    
    const SELECTPICKER = [
        "js" => ["bootstrap-select.min"],
        "css" => ["bootstrap-select.min"],
    ];
    const DATEPICKER = [
        "js" => ["bootstrap-datepicker.min", "bootstrap-datepicker.de.min"],
        "css" => ["bootstrap-datepicker.min"],
    ];
    const FILEINPUT = [
        "js" => ["fileinput.min", "fileinput.de", "fileinput-themes/gly/theme"],
        "css" => ["fileinput.min"],
    ];
    const IBAN = [
        "js" => ["iban"],
        "css" => [],
    ];
    const AUSLAGEN = [
    	"js" => ["auslagen"],
    	"css" => [],
    ];
    const CHAT = [
    	"js" => ["chat"],
    	"css" => ["chat"],
    ];
}