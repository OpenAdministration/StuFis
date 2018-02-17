<?php

/**
 * @param int $id - Returns Antrag via _REQUEST["token"] if null
 *
 * @return bool|mixed
 */
function getAntrag($id = null){
    if ($id === null){
        $antrag = dbGet("antrag", ["token" => $_REQUEST["token"]]);
    }else{
        $antrag = dbGet("antrag", ["id" => $id]);
    }
    if ($antrag === false){
        if ($id === null) die("Unknown antrag.");
        return false;
    }
    $inhalt = dbFetchAll("inhalt", ["antrag_id" => $antrag["id"]]);
    $antrag["_inhalt"] = $inhalt;
    
    $form = getForm($antrag["type"], $antrag["revision"]);
    $readPermitted = hasPermission($form, $antrag, "canRead");
    if (!$readPermitted){
        if ($id === null) die("Permission denied");
        return false;
    }
    
    $anhang = dbFetchAll("anhang", ["antrag_id" => $antrag["id"]]);
    $antrag["_anhang"] = $anhang;
    $comments = dbFetchAll("comments", ["antrag_id" => $antrag["id"]], ["id" => false]);
    $antrag["_comments"] = $comments;
    
    return $antrag;
}

function getAntragDisplayTitle(&$antrag, &$revConfig, $captionField = false){
    global $antraege;
    static $cache = false;
    if ($cache === false) $cache = [];
    $cacheMe = ($captionField === false);
    if (isset($antrag["id"]) && isset($cache[$antrag["id"]]) && $cacheMe)
        return $cache[$antrag["id"]];
    $renderOk = true;
    
    $caption = [];
    if ($captionField === false && isset($revConfig["captionField"]) && count($revConfig["captionField"]) > 0){
        $captionField = $revConfig["captionField"];
    }
    if ($captionField !== false){
        if (!isset($antrag["_inhalt"])){
            $antrag["_inhalt"] = dbFetchAll("inhalt", ["antrag_id" => $antrag["id"]]);
            $antraege[$antrag['type']][$antrag['revision']][$antrag['id']] = $antrag;
        }
        foreach ($captionField as $j => $fdesc){
            $fdesc = explode("|", $fdesc);
            $fname = $fdesc[0];
            $rows = getFormEntries($fname, null, $antrag["_inhalt"]);
            $row = count($rows) > 0 ? $rows[0] : false;
            if ($row !== false){
                ob_start();
                $formlayout = [["type" => $row["contenttype"], "id" => $fname]];
                for ($k = 1; $k < count($fdesc); $k++){
                    list($fdk, $fdv) = explode("=", $fdesc[$k], 2);
                    $formlayout[0][$fdk] = $fdv;
                }
                $form = ["layout" => $formlayout, "config" => []];
                $ret = renderForm($form, ["_values" => $antrag, "render" => ["no-form", "no-form-markup"]]);
                if ($ret === false) $renderOk = false;
                $val = ob_get_contents();
                ob_end_clean();
                $caption[] = strip_tags($val);
            }
        }
    }
    if (isset($revConfig["caption"]) > 0 && $cacheMe){
        $caption[] = $revConfig["caption"];
    }
    if (trim(strip_tags(implode(" ", $caption))) == "")
        array_unshift($caption, htmlspecialchars($antrag["token"]));
    
    if (isset($antrag["id"]) && $renderOk && $cacheMe)
        $cache[$antrag["id"]] = $caption;
    return $caption;
}

function human_filesize($bytes, $decimals = 2){
    $size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

function escapeMe($d, $row){
    return htmlspecialchars($d);
}

function trimMe($d){
    if (is_array($d)){
        return array_map("trimMe", $d);
    }else{
        return trim($d);
    }
}

function add_message($msg){
    global $msgs;
    $msgs[] = $msg;
}

function hexEscape($string){
    $return = '';
    for ($x = 0; $x < strlen($string); $x++){
        $return .= '\x' . bin2hex($string[$x]);
    }
    return $return;
}

function sanitizeName($name){
    return preg_replace(Array("#ä#", "#ö#", "#ü#", "#Ä#", "#Ö#", "#Ü#", "#ß#", "#[^A-Za-z0-9\+\?/\-:\(\)\.,' ]#"), Array("ae", "oe", "ue", "Ae", "Oe", "Ue", "sz", "."), $name);
}

function storeInhalt($inhalt, $isPartiell){
    
    if (is_array($inhalt["value"])){
        $fieldname = $inhalt["fieldname"];
        $ret = true;
        foreach ($inhalt["value"] as $i => $value){
            $inhalt["fieldname"] = $fieldname . "[{$i}]";
            $inhalt["value"] = $value;
            $ret1 = storeInhalt($inhalt, $isPartiell);
            $ret = $ret && $ret1;
        }
        return $ret;
    }
    
    if (is_object($inhalt["value"])){
        $fieldname = $inhalt["fieldname"];
        $ret = true;
        foreach (get_object_vars($inhalt["value"]) as $i => $value){
            $inhalt["fieldname"] = $fieldname . "[{$i}]";
            $inhalt["value"] = $value;
            $ret1 = storeInhalt($inhalt);
            $ret = $ret && $ret1;
        }
        return $ret;
    }
    if ($isPartiell)
        dbDelete("inhalt", ["antrag_id" => $inhalt["antrag_id"], "fieldname" => $inhalt["fieldname"]]);
    $inhalt["value"] = convertUserValueToDBValue($inhalt["value"], $inhalt["contenttype"]);
    $ret = dbInsert("inhalt", $inhalt);
    if (!$ret){
        $msgs[] = "Eintrag im Formular konnte nicht gespeichert werden: " . print_r($inhalt, true);
    }
    return $ret;
}

function writeState($newState, $antrag, $form, &$msgs, &$filesCreated, &$filesRemoved, &$target, $checkPermissions = true){
    if ($antrag["state"] == $newState) return true;
    
    $transition = "from.{$antrag["state"]}.to.{$newState}";
    $perm = "canStateChange.{$transition}";
    if ($checkPermissions && !hasPermission($form, $antrag, $perm)){
        $msgs[] = "Der gewünschte Zustandsübergang kann nicht eingetragen werden (keine Berechtigung): {$antrag["state"]} -> {$newState}";
        return false;
    }
    
    $ret = dbUpdate("antrag", ["id" => $antrag["id"]], ["lastupdated" => date("Y-m-d H:i:s"), "version" => $antrag["version"] + 1, "state" => $newState, "stateCreator" => AuthHandler::getInstance()->getUsername()]);
    
    if ($ret !== 1)
        return false;
    
    $preNewStateActions = [];
    if (isset($form["config"]["preNewStateActions"]) && isset($form["config"]["preNewStateActions"]["from.{$antrag["state"]}.to.{$newState}"]))
        $preNewStateActions = array_merge($preNewStateActions, $form["config"]["preNewStateActions"]["from.{$antrag["state"]}.to.{$newState}"]);
    if (isset($form["config"]["preNewStateActions"]) && isset($form["config"]["preNewStateActions"]["to.{$newState}"]))
        $preNewStateActions = array_merge($preNewStateActions, $form["config"]["preNewStateActions"]["to.{$newState}"]);
    foreach ($preNewStateActions as $action){
        if (!isset($action["writeField"])) continue;
        
        $antrag = getAntrag($antrag["id"]);
        $value = getFormValueInt($action["name"], $action["type"], $antrag["_inhalt"], "");
        
        switch ($action["writeField"]){
            case "always":
                break;
            case "ifEmpty":
                if ($value != "") continue 2;
                break;
            default:
                die("preNewStateActions writeField={$action["writeField"]} invalid value");
        }
        
        if (isset($action["value"])){
            $newValue = $action["value"];
        }else if ($action["type"] == "signbox"){
            $newValue = AuthHandler::getInstance()->getUserFullName() . " am " . date("Y-m-d");
        }else
            die("cannot autogenerate value for preNewStateActions");
        
        dbDelete("inhalt", ["antrag_id" => $antrag["id"], "fieldname" => $action["name"]]);
        $ret = dbInsert("inhalt", ["fieldname" => $action["name"], "contenttype" => $action["type"], "antrag_id" => $antrag["id"], "value" => $newValue]);
        if ($ret === false)
            return false;
    }
    
    $comment = [];
    $comment["antrag_id"] = $antrag["id"];
    $comment["creator"] = AuthHandler::getInstance()->getUsername();
    $comment["creatorFullName"] = AuthHandler::getInstance()->getUserFullName();
    $comment["timestamp"] = date("Y-m-d H:i:s");
    $txt = $newState;
    if (isset($form["_class"]["state"][$newState]))
        $txt = $form["_class"]["state"][$newState][0];
    $comment["text"] = "Status nach [$newState] " . $txt . " geändert";
    $ret = dbInsert("comments", $comment);
    if ($ret === false)
        return false;
    
    if (!isValid($antrag["id"], "postEdit", $msgs))
        return false;
    
    $antrag = getAntrag($antrag["id"]);
    $ret = doNewStateActions($form, $transition, $antrag, $newState, $msgs, $filesCreated, $filesRemoved, $target);
    if ($ret === false)
        return false;
    
    return true;
}


/**
 * @param        $inhalt   Array welches vereinfacht werden soll
 * @param string $newKey   Value dieses Keys wird als neuer Key verwendet; falls String leer ist wird 0 ... n als key
 *                         verwendet
 * @param string $newValue Value wird als zugehöriges Value gesetzt
 *
 * @return array Vereinfachtes Array
 */
function betterValues($inhalt, $newKey = "fieldname", $newValue = "value"){
    global $inhalt_better;
    $inhalt_better = [];
    $params = array($newKey, $newValue);
    if (!is_array(end($inhalt))){
        return betterValues(array($inhalt), $newKey, $newValue);
    }
    array_walk($inhalt, function(& $item, $k, $params){
        global $inhalt_better;
        $newKey = $params[0];
        $newValue = $params[1];
        $val = $item[$newValue];
        if ($newKey !== "")
            $inhalt_better[$item[$newKey]] = $val;
        else
            $inhalt_better[] = $val;
    }, $params);
    return $inhalt_better;
}

/**
 * @param $str Name des Profiling Flags
 */
function prof_flag($str){
    global $prof_timing, $prof_names;
    $prof_timing[] = microtime(true);
    $prof_names[] = $str;
}

/**
 * Print all Profiling Flags from prof_flag()
 */
function prof_print(){
    global $prof_timing, $prof_names;
    $sum = 0;
    $size = count($prof_timing);
    $out = "";
    for ($i = 0; $i < $size - 1; $i++){
        $out .= "<b>{$prof_names[$i]}</b><br>";
        $sum += $prof_timing[$i + 1] - $prof_timing[$i];
        $out .= sprintf("&nbsp;&nbsp;&nbsp;%f<br>", $prof_timing[$i + 1] - $prof_timing[$i]);
    }
    $out .= "<b>{$prof_names[$size-1]}</b><br>";
    $out = '<div class="profiling-output"><h3><i class="fa fw fa-angle-toggle"></i> Ladezeit: ' . sprintf("%f", $sum) . '</h3>' . $out;
    $out .= "</div>";
    echo $out;
}

function generateLinkFromID($text, $token){
    global $URIBASE;
    return "<a href='" . htmlspecialchars($URIBASE . $token) . "'><i class='fa fw fa-link'></i> $text </a>";
}


/**
 * https://stackoverflow.com/questions/20983339/validate-iban-php
 * @param $iban
 *
 * @return bool
 */
function checkIBAN($iban){
    $iban = strtolower(str_replace(' ', '', $iban));
    $countries = array('al' => 28, 'ad' => 24, 'at' => 20, 'az' => 28, 'bh' => 22, 'be' => 16, 'ba' => 20, 'br' => 29, 'bg' => 22, 'cr' => 21, 'hr' => 21, 'cy' => 28, 'cz' => 24, 'dk' => 18, 'do' => 28, 'ee' => 20, 'fo' => 18, 'fi' => 18, 'fr' => 27, 'ge' => 22, 'de' => 22, 'gi' => 23, 'gr' => 27, 'gl' => 18, 'gt' => 28, 'hu' => 28, 'is' => 26, 'ie' => 22, 'il' => 23, 'it' => 27, 'jo' => 30, 'kz' => 20, 'kw' => 30, 'lv' => 21, 'lb' => 28, 'li' => 21, 'lt' => 20, 'lu' => 20, 'mk' => 19, 'mt' => 31, 'mr' => 27, 'mu' => 30, 'mc' => 27, 'md' => 24, 'me' => 22, 'nl' => 18, 'no' => 15, 'pk' => 24, 'ps' => 29, 'pl' => 28, 'pt' => 25, 'qa' => 29, 'ro' => 24, 'sm' => 27, 'sa' => 24, 'rs' => 22, 'sk' => 24, 'si' => 19, 'es' => 24, 'se' => 24, 'ch' => 21, 'tn' => 24, 'tr' => 26, 'ae' => 23, 'gb' => 22, 'vg' => 24);
    $chars = array('a' => 10, 'b' => 11, 'c' => 12, 'd' => 13, 'e' => 14, 'f' => 15, 'g' => 16, 'h' => 17, 'i' => 18, 'j' => 19, 'k' => 20, 'l' => 21, 'm' => 22, 'n' => 23, 'o' => 24, 'p' => 25, 'q' => 26, 'r' => 27, 's' => 28, 't' => 29, 'u' => 30, 'v' => 31, 'w' => 32, 'x' => 33, 'y' => 34, 'z' => 35);
    
    if (strlen($iban) !== $countries[substr($iban, 0, 2)])
        return false;
    
    $movedChar = substr($iban, 4) . substr($iban, 0, 4);
    $movedCharArray = str_split($movedChar);
    $newString = "";
    
    foreach ($movedCharArray AS $key => $value){
        if (!is_numeric($movedCharArray[$key])){
            $movedCharArray[$key] = $chars[$movedCharArray[$key]];
        }
        $newString .= $movedCharArray[$key];
    }
    
    if (bcmod($newString, '97') == 1){
        return true;
    }else{
        return false;
    }
    
}

