<?php

use baseclass\TextStyle;

function human_filesize($bytes, $decimals = 2): string
{
    $size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / (1024 ** $factor)) . @$size[$factor];
}

function escapeMe($d, $row): string
{
    return htmlspecialchars($d);
}

function trimMe($d){
    if (is_array($d)){
        return array_map("trimMe", $d);
    }

    return trim($d);
}

function hexEscape($string): string
{
    $return = '';
    for ($x = 0, $xMax = strlen($string); $x < $xMax; $x++){
        $return .= '\x' . bin2hex($string[$x]);
    }
    return $return;
}

function sanitizeName($name){
    return preg_replace(Array("#ä#", "#ö#", "#ü#", "#Ä#", "#Ö#", "#Ü#", "#ß#", "#[^A-Za-z0-9\+\?/\-:\(\)\.,' ]#"), Array("ae", "oe", "ue", "Ae", "Oe", "Ue", "sz", "."), $name);
}





function generateLinkFromID($text, $dest, $linkColor = TextStyle::__default): string
{
    return "<a class='$linkColor' href='" . htmlspecialchars(URIBASE . $dest) . "'><i class='fa fa-fw fa-link' aria-hidden='true'></i>&nbsp;$text</a>";
}

/**
 * https://stackoverflow.com/questions/20983339/validate-iban-php
 * @param $iban
 *
 * @return bool
 */
function checkIBAN($iban) : bool
{
    $iban = strtolower(str_replace(' ', '', $iban));
    $countries = array('al' => 28, 'ad' => 24, 'at' => 20, 'az' => 28, 'bh' => 22, 'be' => 16, 'ba' => 20, 'br' => 29, 'bg' => 22, 'cr' => 21, 'hr' => 21, 'cy' => 28, 'cz' => 24, 'dk' => 18, 'do' => 28, 'ee' => 20, 'fo' => 18, 'fi' => 18, 'fr' => 27, 'ge' => 22, 'de' => 22, 'gi' => 23, 'gr' => 27, 'gl' => 18, 'gt' => 28, 'hu' => 28, 'is' => 26, 'ie' => 22, 'il' => 23, 'it' => 27, 'jo' => 30, 'kz' => 20, 'kw' => 30, 'lv' => 21, 'lb' => 28, 'li' => 21, 'lt' => 20, 'lu' => 20, 'mk' => 19, 'mt' => 31, 'mr' => 27, 'mu' => 30, 'mc' => 27, 'md' => 24, 'me' => 22, 'nl' => 18, 'no' => 15, 'pk' => 24, 'ps' => 29, 'pl' => 28, 'pt' => 25, 'qa' => 29, 'ro' => 24, 'sm' => 27, 'sa' => 24, 'rs' => 22, 'sk' => 24, 'si' => 19, 'es' => 24, 'se' => 24, 'ch' => 21, 'tn' => 24, 'tr' => 26, 'ae' => 23, 'gb' => 22, 'vg' => 24);
    $chars = array('a' => 10, 'b' => 11, 'c' => 12, 'd' => 13, 'e' => 14, 'f' => 15, 'g' => 16, 'h' => 17, 'i' => 18, 'j' => 19, 'k' => 20, 'l' => 21, 'm' => 22, 'n' => 23, 'o' => 24, 'p' => 25, 'q' => 26, 'r' => 27, 's' => 28, 't' => 29, 'u' => 30, 'v' => 31, 'w' => 32, 'x' => 33, 'y' => 34, 'z' => 35);
    
    if (strlen($iban) !== $countries[substr($iban, 0, 2)]) {
        return false;
    }
    
    $movedChar = substr($iban, 4) . substr($iban, 0, 4);
    $movedCharArray = str_split($movedChar);
    $newString = "";
    
    foreach ($movedCharArray AS $key => $value){
        if (!is_numeric($movedCharArray[$key])){
            $movedCharArray[$key] = $chars[$movedCharArray[$key]];
        }
        $newString .= $movedCharArray[$key];
    }

    return bcmod($newString, '97') == 1;

}

if (!function_exists('generateRandomString')){
    /**
     * generates secure random hex string of length: 2*$length
     * @param integer $length 0.5 string length
     * @return NULL|string
     * @throws Exception
     */
	function generateRandomString(int $length) : string
    {
        return bin2hex(random_bytes($length));
	}
}

