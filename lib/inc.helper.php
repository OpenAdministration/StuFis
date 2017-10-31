<?php

function getAntrag($id = null) {
    if ($id === null) {
        $antrag = dbGet("antrag", ["token" => $_REQUEST["token"]]);
    } else {
        $antrag = dbGet("antrag", ["id" => $id]);
    }
    if ($antrag === false) {
        if ($id === null) die("Unknown antrag.");
        return false;
    }
    $inhalt = dbFetchAll("inhalt", ["antrag_id" => $antrag["id"]]);
    $antrag["_inhalt"] = $inhalt;

    $form = getForm($antrag["type"], $antrag["revision"]);
    $readPermitted = hasPermission($form, $antrag, "canRead");
    if (!$readPermitted) {
        if ($id === null) die("Permission denied");
        return false;
    }

    $anhang = dbFetchAll("anhang", ["antrag_id" => $antrag["id"]]);
    $antrag["_anhang"] = $anhang;
    $comments = dbFetchAll("comments", ["antrag_id" => $antrag["id"]], [ "id" => false]);
    $antrag["_comments"] = $comments;

    return $antrag;
}

function getAntragDisplayTitle(&$antrag, &$revConfig, $captionField = false) {
    static $cache = false;
    if ($cache === false) $cache = [];
    $cacheMe = ($captionField === false);
    if (isset($antrag["id"]) && isset($cache[$antrag["id"]]) && $cacheMe)
        return $cache[$antrag["id"]];
    $renderOk = true;

    $caption = [ ];
    if ($captionField === false && isset($revConfig["captionField"]) && count($revConfig["captionField"]) > 0) {
        $captionField = $revConfig["captionField"];
    }
    if ($captionField !== false) {
        if (!isset($antrag["_inhalt"])) {
            $antrag["_inhalt"] = dbFetchAll("inhalt", ["antrag_id" => $antrag["id"] ]);
            $antraege[$type][$revision][$i] = $antrag;
        }
        foreach ($captionField as $j => $fdesc) {
            $fdesc = explode("|", $fdesc);
            $fname = $fdesc[0];
            $rows = getFormEntries($fname, null, $antrag["_inhalt"]);
            $row = count($rows) > 0 ? $rows[0] : false;
            if ($row !== false) {
                ob_start();
                $formlayout = [ [ "type" => $row["contenttype"], "id" => $fname ] ];
                for($k = 1; $k < count($fdesc); $k++) {
                    list($fdk, $fdv) = explode("=", $fdesc[$k], 2);
                    $formlayout[0][$fdk] = $fdv;
                }
                $form = [ "layout" => $formlayout, "config" => [] ];
                $ret = renderForm($form, ["_values" => $antrag, "render" => ["no-form", "no-form-markup"]] );
                if ($ret === false) $renderOk = false;
                $val = ob_get_contents();
                ob_end_clean();
                $caption[] = strip_tags($val);
            }
        }
    }
    if (isset($revConfig["caption"]) > 0 && $cacheMe) {
        $caption[] = $revConfig["caption"];
    }
    if (trim(strip_tags(implode(" ", $caption))) == "")
        array_unshift($caption, htmlspecialchars($antrag["token"]));

    if (isset($antrag["id"]) && $renderOk && $cacheMe)
        $cache[$antrag["id"]] = $caption;
    return $caption;
}

function human_filesize($bytes, $decimals = 2) {
    $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

function escapeMe($d, $row) {
    return htmlspecialchars($d);
}

function trimMe($d) {
    if (is_array($d)) {
        return array_map("trimMe", $d);
    } else {
        return trim($d);
    }
}

function add_message($msg) {
    global $msgs;
    $msgs[] = $msg;
}

function hexEscape($string) {
    $return = '';
    for ($x=0; $x < strlen($string); $x++) {
        $return .= '\x' . bin2hex($string[$x]);
    }
    return $return;
}

function sanitizeName($name) {
    return preg_replace(Array("#ä#","#ö#","#ü#","#Ä#","#Ö#","#Ü#","#ß#", "#[^A-Za-z0-9\+\?/\-:\(\)\.,' ]#"), Array("ae","oe","ue","Ae","Oe","Ue","sz","."), $name);
}
function storeInhalt($inhalt, $isPartiell) {

    if (is_array($inhalt["value"])) {
        $fieldname = $inhalt["fieldname"];
        $ret = true;
        foreach ($inhalt["value"] as $i => $value) {
            $inhalt["fieldname"] = $fieldname . "[{$i}]";
            $inhalt["value"] = $value;
            $ret1 = storeInhalt($inhalt, $isPartiell);
            $ret = $ret && $ret1;
        }
        return $ret;
    }

    if (is_object($inhalt["value"])) {
        $fieldname = $inhalt["fieldname"];
        $ret = true;
        foreach (get_object_vars($inhalt["value"]) as $i => $value) {
            $inhalt["fieldname"] = $fieldname . "[{$i}]";
            $inhalt["value"] = $value;
            $ret1 = storeInhalt($inhalt);
            $ret = $ret && $ret1;
        }
        return $ret;
    }
    if ($isPartiell)
        dbDelete("inhalt", ["antrag_id" => $inhalt["antrag_id"], "fieldname" => $inhalt["fieldname"] ]);
    $inhalt["value"] = convertUserValueToDBValue($inhalt["value"], $inhalt["contenttype"]);
    $ret = dbInsert("inhalt", $inhalt);
    if (!$ret) {
        $msgs[] = "Eintrag im Formular konnte nicht gespeichert werden: ".print_r($inhalt,true);
    }
    return $ret;
}
function writeState($newState, $antrag, $form, &$msgs, &$filesCreated, &$filesRemoved, &$target,$checkPermissions = true) {
    if ($antrag["state"] == $newState) return true;

    $transition = "from.{$antrag["state"]}.to.{$newState}";
    $perm = "canStateChange.{$transition}";
    if ($checkPermissions && !hasPermission($form, $antrag, $perm)) {
        $msgs[] = "Der gewünschte Zustandsübergang kann nicht eingetragen werden (keine Berechtigung): {$antrag["state"]} -> {$newState}";
        return false;
    }

    $ret = dbUpdate("antrag", [ "id" => $antrag["id"] ], ["lastupdated" => date("Y-m-d H:i:s"), "version" => $antrag["version"] + 1, "state" => $newState, "stateCreator" => getUsername()]);

    if ($ret !== 1)
        return false;

    $preNewStateActions = [];
    if (isset($form["config"]["preNewStateActions"]) && isset($form["config"]["preNewStateActions"]["from.{$antrag["state"]}.to.{$newState}"]))
        $preNewStateActions = array_merge($preNewStateActions, $form["config"]["preNewStateActions"]["from.{$antrag["state"]}.to.{$newState}"]);
    if (isset($form["config"]["preNewStateActions"]) && isset($form["config"]["preNewStateActions"]["to.{$newState}"]))
        $preNewStateActions = array_merge($preNewStateActions, $form["config"]["preNewStateActions"]["to.{$newState}"]);
    foreach ($preNewStateActions as $action) {
        if (!isset($action["writeField"])) continue;

        $antrag = getAntrag($antrag["id"]);
        $value = getFormValueInt($action["name"], $action["type"], $antrag["_inhalt"], "");

        switch ($action["writeField"]) {
            case "always":
                break;
            case "ifEmpty":
                if ($value != "") continue 2;
                break;
            default:
                die("preNewStateActions writeField={$action["writeField"]} invalid value");
        }

        if (isset($action["value"])) {
            $newValue = $action["value"];
        } elseif ($action["type"] == "signbox") {
            $newValue = getUserFullName()." am ".date("Y-m-d");
        } else
            die("cannot autogenerate value for preNewStateActions");

        dbDelete("inhalt", ["antrag_id" => $antrag["id"], "fieldname" => $action["name"] ]);
        $ret = dbInsert("inhalt", [ "fieldname" => $action["name"], "contenttype" => $action["type"], "antrag_id" => $antrag["id"], "value" => $newValue ] );
        if ($ret === false)
            return false;
    }

    $comment = [];
    $comment["antrag_id"] = $antrag["id"];
    $comment["creator"] = getUsername();
    $comment["creatorFullName"] = getUserFullName();
    $comment["timestamp"] = date("Y-m-d H:i:s");
    $txt = $newState;
    if (isset($form["_class"]["state"][$newState]))
        $txt = $form["_class"]["state"][$newState][0];
    $comment["text"] = "Status nach [$newState] ".$txt." geändert";
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


function betterValues($inhalt,$newValueKey = "fieldname",$oldValueKey = "value"){
    global $inhalt_better;
    $inhalt_better = [];
    $params = array("0"=>$newValueKey,"1"=>$oldValueKey);
    array_walk($inhalt, function (& $item,$k, $params) {
        global $inhalt_better;
        $newValueKey = $params["0"];
        $oldValueKey = $params["1"];
        $val =  $item[$oldValueKey];
        $inhalt_better[$item[$newValueKey]] = $val;
    },$params);
    return $inhalt_better;
}

// Die Funktion muss aufgerufen werden, wenn man ein Flag (Zeitpunkt) setzen will. $str ist der Name des Flags.
function prof_flag($str)
{
    global $prof_timing, $prof_names;
    $prof_timing[] = microtime(true);
    $prof_names[] = $str;
}

// Am Ende muss diese Funktion zum printen verwendet werden
function prof_print()
{
    global $prof_timing, $prof_names;
    $size = count($prof_timing);
    for($i=0;$i<$size - 1; $i++)
    {
        echo "<b>{$prof_names[$i]}</b><br>";
        echo sprintf("&nbsp;&nbsp;&nbsp;%f<br>", $prof_timing[$i+1]-$prof_timing[$i]);
    }
    echo "<b>{$prof_names[$size-1]}</b><br>";
}