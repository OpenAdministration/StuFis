<?php
global $attributes, $logoutUrl, $ADMINGROUP, $nonce, $URIBASE, $antrag, $STORAGE, $HIBISCUSGROUP;
ob_start('ob_gzhandler');

require_once "../lib/inc.all.php";
requireGroup($AUTHGROUP);

function writeFormdata($antrag_id, $isPartiell, $form, $antrag) {
  if (!isset($_REQUEST["formdata"]))
    $_REQUEST["formdata"] = [];

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

  $ret = true;
  foreach($_REQUEST["formdata"] as $fieldname => $value) {
    $fieldtype = $_REQUEST["formtype"][$fieldname];
    if ($fieldtype == "file" || $fieldtype == "multifile") continue;
    if ($isPartiell) {
      $perm = "canEditPartiell.field.{$fieldname}";
      if (!hasPermission($form, $antrag, $perm)) continue;
    }
    $inhalt = [];
    $inhalt["antrag_id"] = $antrag_id;
    $inhalt["contenttype"] = $_REQUEST["formtype"][$fieldname];
    $inhalt["fieldname"] = $fieldname;
    $inhalt["value"] = $value;
    $ret1 = storeInhalt($inhalt, $isPartiell);
    $ret = $ret && $ret1;
  } /* formdata */

  return $ret;
}

function writeFormdataFiles($antrag_id, &$msgs, &$filesRemoved, &$filesCreated, $isPartiell, $form, $antrag) {
  if (!isset($_FILES["formdata"]))
    return true;

  function storeAnhang($anhang, $names, $types, $tmp_names, $errors, $sizes, &$msgs, &$filesRemoved, &$filesCreated) {
    global $STORAGE;
    $ret = true;

    if (is_array($names)) {
      $fieldname = $anhang["fieldname"];
      foreach (array_keys($names) as $key) {
        $anhang["fieldname"] = "${fieldname}[${key}]";
        $ret1 = storeAnhang($anhang, $names[$key], $types[$key], $tmp_names[$key], $errors[$key], $sizes[$key], $msgs, $filesRemoved, $filesCreated);
        $ret = $ret && $ret1;
      }
      return $ret;
    }
    if ($errors == UPLOAD_ERR_NO_FILE || $errors == UPLOAD_ERR_OK) {
      // try to delete file
      // do not try to replace file, as it might be hard-linked to another antrag
      $oldAnhang = dbGet("anhang", [ "antrag_id" => $anhang["antrag_id"], "fieldname" => $names ]);
      if ($oldAnhang !== false) {
        $ret = dbDelete("anhang", [ "antrag_id" => $oldAnhang["antrag_id"], "id" => $oldAnhang["id"] ]);
        $ret = ($ret === 1);
        $filesRemoved[] = $STORAGE."/".$oldAnhang["antrag_id"]."/".$oldAnhang["path"];
      }
    } else {
      $msgs[] = uploadCodeToMessage($errors);
      $ret = false;
    }
    if ($errors != UPLOAD_ERR_OK) {
      return $ret;
    }
    $anhang["size"] = $sizes;
    $anhang["mimetype"] = $types;
    $anhang["md5sum"] = md5_file($tmp_names);
    $anhang["state"] = "active";
    $anhang["filename"] = $names;

    $dbPath = uniqid().".".pathinfo($names, PATHINFO_EXTENSION);
    $path = $STORAGE."/".$anhang["antrag_id"]."/".$dbPath;
    if (!is_dir(dirname($path)))
      mkdir(dirname($path),0777,true);
    $anhang["path"] = $dbPath;

    $ret = move_uploaded_file($tmp_names, $path);
    $filesCreated[] = $path;

    $ret = $ret && dbInsert("anhang", $anhang);
    if (!$ret) {
      $msgs[]="failed $names";
    }

    return $ret;
  }

  $anhang = [];
  $anhang["antrag_id"] = $antrag_id;
  $fd = $_FILES["formdata"];
  $ret = true;
  foreach (array_keys($fd["name"]) as $key) {
    if ($isPartiell) {
      $perm = "canEditPartiell.field.{$key}";
      if (!hasPermission($form, $antrag, $perm)) continue;
    }
    $anhang["fieldname"] = $key;
    $fieldtype = $_REQUEST["formtype"][$key];
    if ($fieldtype != "file" && $fieldtype != "multifile") {
      $msgs[] = "Invalid field type: \"$fieldtype\" for \"$key\"";
      $ret = false;
      continue;
    }
    $ret1 = storeAnhang($anhang, $fd["name"][$key], $fd["type"][$key], $fd["tmp_name"][$key], $fd["error"][$key], $fd["size"][$key], $msgs, $filesRemoved, $filesCreated);
    $ret = $ret && $ret1;
  }

  return $ret;
}

function doNewStateActions(&$form, $transition, &$antrag, $newState, &$msgs, &$filesCreated, &$filesRemoved, &$target) {
  if (!isset($form["_class"]["newStateActions"]))
    return true;
  if (!isset($form["_class"]["newStateActions"][$transition]))
    return true;

  $actions = $form["_class"]["newStateActions"][$transition];
  foreach ($actions as $action) {
    if (isset($action["sendMail"]) && $action["sendMail"]) {
      notifyStateTransition($antrag, $newState, getUsername(), $action);
    }
    if (isset($action["copy"]) && $action["copy"]) {
      $newTarget = "";
      $ret = copyAntrag($antrag["id"], $antrag["version"], false, $action["type"], $action["revision"], $msgs, $filesCreated, $filesRemoved, $newTarget);
      if ($ret === false)
        return false;
      if (isset($action["redirect"]) && $action["redirect"]) {
        $target = $newTarget;
      }
    }
  }
  return true;
}

function writeState($newState, $antrag, $form, &$msgs, &$filesCreated, &$filesRemoved, &$target) {
  if ($antrag["state"] == $newState) return true;

  $transition = "from.{$antrag["state"]}.to.{$newState}";
  $perm = "canStateChange.{$transition}";
  if (!hasPermission($form, $antrag, $perm)) {
    $msgs[] = "Der gewünschte Zustandsübergang kann nicht eingetragen werden (keine Berechtigung): {$antrag["state"]} -> {$newState}";
    return false;
  }

  $ret = dbUpdate("antrag", [ "id" => $antrag["id"] ], ["lastupdated" => date("Y-m-d H:i:s"), "version" => $antrag["version"] + 1, "state" => $newState, "stateCreator" => getUsername() ]);
  if ($ret !== 1)
    return false;

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

  $antrag = getAntrag($antrag["id"]);
  $ret = doNewStateActions($form, $transition, $antrag, $newState, $msgs, $filesCreated, $filesRemoved, $target);
  if ($ret === false)
    return false;

  return true;
}

function copyAntrag($oldAntragId, $oldAntragVersion, $oldAntragNewState, $newType, $newRevision, &$msgs, &$filesCreated, &$filesRemoved, &$target) {
  global $URIBASE;

  $form = getForm($newType, $newRevision);
  if ($form === false) {
    $msgs[] = "Unbekannte Formularversion: $newType#$newRevision";
    return false;
  }
  if (!hasPermission($form, null, "canCreate")) {
    $msgs[] = "Antrag ist nicht erstellbar";
    return false;
  }

  $oldAntrag = getAntrag($oldAntragId);
  if ($oldAntrag === false) {
    $msgs[] = "Unknown / unreadable source antrag.";
    return false;
  }
  if ($oldAntragVersion !== $oldAntrag["version"]) {
    $msgs[] = "Der Antrag wurde von jemanden anderes bearbeitet und kann daher nicht gespeichert werden. (oldAntrag during copy)";
    return false;
  }

  $oldForm = getForm($oldAntrag["type"], $oldAntrag["revision"]);

  $antrag = [];
  $antrag["type"] = $newType;
  $antrag["revision"] = $newRevision;
  $antrag["creator"] = getUsername();
  $antrag["creatorFullName"] = getUserFullName();
  $antrag["token"] = $token = substr(sha1(sha1(mt_rand())),0,16);
  $antrag["createdat"] = date("Y-m-d H:i:s");
  $antrag["lastupdated"] = date("Y-m-d H:i:s");
  $createState = "draft";
  if (isset($form["_class"]["createState"]))
    $createState = $form["_class"]["createState"];
  $antrag["state"] = $createState;
  $antrag["stateCreator"] = getUsername();
  $antrag_id = dbInsert("antrag", $antrag);
  if ($antrag_id === false)
    return false;

  $target = str_replace("//","/",$URIBASE."/").rawurlencode($token);
  $antrag_id = (int) $antrag_id;
  $msgs[] = "Antrag wurde erstellt.";

  # füge alle Felder ein, überflüssige Felder werden beim nächsten Speichern entfernt.
  foreach($oldAntrag["_inhalt"] as $row) {
    $row["antrag_id"] = $antrag_id;
    $ret0 = dbInsert("inhalt", $row);
    if ($ret0 === false)
      return false;
  }

  $foundBuildFrom = false;
  if (isset($form["_class"]["buildFrom"])) {
    foreach($form["_class"]["buildFrom"] as $tmp) {
      if (is_array($tmp) && $tmp[0] != $oldForm["type"])
        continue;
      elseif (!is_array($tmp) && $tmp != $oldForm["type"])
        continue;
      $foundBuildFrom = true;
      break;
    }
  }

  if ($oldForm["type"] != $form["type"] &&
      $foundBuildFrom &&
      isset($form["config"]["referenceField"]))
  {
     $row = Array();
     $row["antrag_id"] = $antrag_id;
     $row["contenttype"] = $form["config"]["referenceField"]["type"];
     $row["fieldname"] = $form["config"]["referenceField"]["name"];
     $row["value"] = $oldAntrag["id"];
     $ret0 = dbInsert("inhalt", $row);
     if ($ret0 === false)
       return false;
  }

  $fillOnCopy = [];
  if (isset($form["config"]["fillOnCopy"]))
    $fillOnCopy = $form["config"]["fillOnCopy"];
  foreach ($fillOnCopy as $rec) {
     $row = Array();
     $row["antrag_id"] = $antrag_id;
     $row["contenttype"] = $rec["type"];
     $row["fieldname"] = $rec["name"];
     $value = "";
     switch ($rec["prefill"]) {
       case "user:mail":
         $value = getUserMail();
       break;
       case "otherForm":
         $fieldValue = false;
         $fieldName = false;
         if ($rec["otherForm"][0] == "referenceField" && isset($form["config"]["referenceField"])) {
           $fieldName = $form["config"]["referenceField"]["name"];
         } elseif (substr($rec["otherForm"][0],0,6) == "field:") {
           $fieldName = substr($rec["otherForm"][0],6);
         } else {
           die("Unknown otherForm reference in fillOnCopy: {$rec["otherForm"][0]}");
         }
         if ($fieldValue === false && $fieldName !== false) {
           $f = dbGet("inhalt", [ "antrag_id" => $antrag_id, "fieldname" => $fieldName, "type" => "otherForm" ] );
           if ($f !== false)
             $fieldValue = $f["value"];
         }
         if ($fieldValue === false || $fieldValue == "") {
           // no other form provided
           break;
         }
         $otherAntrag = dbGet("antrag", ["id" => (int) $fieldValue]);
         if ($otherAntrag === false) {
           // invalid value
           break;
         }

         $otherInhalt = dbFetchAll("inhalt", ["antrag_id" => $otherAntrag["id"]]);
         $otherAntrag["_inhalt"] = $otherInhalt;

         $otherForm = getForm($otherAntrag["type"], $otherAntrag["revision"]);
         $readPermitted = hasPermission($otherForm, $otherAntrag, "canRead");

         if (!$readPermitted) {
           break;
         }

         $f = dbGet("inhalt", [ "antrag_id" => $otherAntrag["id"], "fieldname" => $rec["otherForm"][1], "type" => "otherForm" ] );
         if ($f === false)
           // other field not in other form
           break;

         $value = $f["value"];

       break;
       default:
         if (substr($rec["prefill"],0,6) == "value:") {
           $value = substr($rec["prefill"],6);
         } else {
           $msgs[] = "FillOnCopy fehlgeschlagen: prefill={$rec["prefill"]} nicht implementiert.";
           return false;
           break 2; # abort foreach fillOnCopy
         }
     }
     $row["value"] = $value;
     dbDelete("inhalt", ["antrag_id" => $row["antrag_id"], "fieldname" => $row["fieldname"] ]);
     $ret0 = dbInsert("inhalt", $row);
     if ($ret0 === false)
       return false;
  }
  # füge alle Felder ein, überflüssige Felder werden beim nächsten Speichern entfernt.
  foreach($oldAntrag["_anhang"] as $row) {

    $dbPath = uniqid().".".pathinfo($row["filename"], PATHINFO_EXTENSION);
    $destPath = $STORAGE."/".$antrag_id."/".$dbPath;
    $srcPath = $STORAGE."/".$row["antrag_id"]."/".$row["path"];
    if (!is_dir(dirname($destPath)))
      mkdir(dirname($destPath),0777,true);

    $row["antrag_id"] = $antrag_id;
    $row["path"] = $dbPath;

    $ret0 = link($srcPath, $destPath);
    if ($ret0 === false)
      $ret0 = copy($srcPath, $destPath);
    $filesCreated[] = $destPath;
    $msgs[] = "Created $destPath";

    $ret1 = dbInsert("anhang", $row);
    if (($ret0 === false) || ($ret1 === false))
      return false;
  }

  if ($oldAntragNewState !== false && $oldAntragNewState != "") {
    if (false === writeState($oldAntragNewState, $oldAntrag, $oldForm, $msgs, $filesCreated, $filesRemoved, $target))
      return false;
  }

  $newAntrag = getAntrag($antrag_id);
  $ret = doNewStateActions($form, "create.$createState", $newAntrag, $createState, $msgs, $filesCreated, $filesRemoved, $target);
  if ($ret === false)
    return false;

  return $antrag_id;
}

if (isset($_REQUEST["action"])) {
 global $msgs;
 $msgs = Array();
 $ret = false;
 $target = false;
 $altTarget = false;
 $forceClose = false;

 if (!isset($_REQUEST["nonce"]) || $_REQUEST["nonce"] !== $nonce) {
  $msgs[] = "Formular veraltet - CSRF Schutz aktiviert.";
  $logId = false;
 } else {
  $logId = logThisAction();
  if (strpos($_POST["action"],"insert") !== false ||
      strpos($_POST["action"],"update") !== false ||
      strpos($_POST["action"],"delete") !== false) {
    foreach ($_REQUEST as $k => $v) {
      $_REQUEST[$k] = trimMe($v);
    }
  }

  switch ($_POST["action"]):
    case "antrag.delete":
      // beginTx
      if (!dbBegin()) {
        $msgs[] = "Cannot start DB transaction";
        $ret = false;
        break;
      }

      $antrag = getAntrag();
      // check antrag type and revision
      if ($_REQUEST["type"] !== $antrag["type"]) die("Unerlaubter Typ");
      if ($_REQUEST["revision"] !== $antrag["revision"]) die("Unerlaubte Version");

      $form = getForm($antrag["type"], $antrag["revision"]);
      if (!hasPermission($form, $antrag, "canEdit")) die("Antrag ist nicht editierbar");

      if ($_REQUEST["version"] !== $antrag["version"]) {
        $ret = false;
        $msgs[] = "Der Antrag wurde von jemanden anderes bearbeitet und kann daher nicht gespeichert werden.";
      } else {
        $ret = true;
      }
      $filesCreated = []; $filesRemoved = [];

      $anhaenge = dbFetchAll("anhang", [ "antrag_id" => $antrag["id"] ]);
      foreach($anhaenge as $anhang) {
        $msgs[] = "Lösche Anhang ".$anhang["fieldname"]." / ".$anhang["filename"];
        $ret1 = dbDelete("anhang", [ "antrag_id" => $anhang["antrag_id"], "id" => $anhang["id"] ]);
        $ret = $ret && ($ret1 === 1);
        $filesRemoved[] = $STORAGE."/".$anhang["antrag_id"]."/".$anhang["path"];
      }
      dbDelete("inhalt", [ "antrag_id" => $antrag["id"] ]);
      dbDelete("comments", [ "antrag_id" => $antrag["id"] ]);
      dbDelete("anhang", [ "antrag_id" => $antrag["id"] ]);
      dbDelete("antrag", [ "id" => $antrag["id"] ]);

      if (count($filesCreated) > 0) die("ups files created during antrag.delete");
      // commitTx
      if ($ret)
        $ret = dbCommit();
      if (!$ret) {
        dbRollBack();
        foreach ($filesCreated as $f) {
          if (@unlink($f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      } else {
        // delete files from disk after successfull commit
        foreach ($filesRemoved as $f) {
          if (@unlink($f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      }
      if ($ret) {
        if (file_exists($STORAGE."/".$antrag["id"])) {
          if (@rmdir($STORAGE."/".$antrag["id"]) === false)
            $msgs[] = "Kann Order nicht löschen: {$antrag["id"]}";
        }
        $forceClose = true;
        $target = $URIBASE;
      }
      break;
    case "antrag.update":
    case "antrag.updatePartiell":
      $isPartiell = ($_POST["action"] == "antrag.updatePartiell");

      // beginTx
      if (!dbBegin()) {
        $msgs[] = "Cannot start DB transaction";
        $ret = false;
        break;
      }
      $antrag = getAntrag();
      // check antrag type and revision, token cannot be altered
      if ($_REQUEST["type"] !== $antrag["type"]) die("Unerlaubter Typ");
      if ($_REQUEST["revision"] !== $antrag["revision"]) die("Unerlaubte Version");

      $form = getForm($antrag["type"], $antrag["revision"]);
      if ($isPartiell) {
        if (!hasPermission($form, $antrag, "canEditPartiell")) die("Antrag ist nicht editierbar");
      } else {
        if (!hasPermission($form, $antrag, "canEdit")) die("Antrag ist nicht editierbar");
      }

      if ($_REQUEST["version"] !== $antrag["version"]) {
        $ret = false;
        $msgs[] = "Der Antrag wurde von jemanden anderes bearbeitet und kann daher nicht gespeichert werden.";
      } else {
        $ret = true;
      }
      $filesCreated = []; $filesRemoved = [];
      // update last-modified timestamp
      dbUpdate("antrag", [ "id" => $antrag["id"] ], ["lastupdated" => date("Y-m-d H:i:s"), "version" => $antrag["version"] + 1 ]);
      // clear all old values (tbl inhalt)
      if (!$isPartiell)
        dbDelete("inhalt", [ "antrag_id" => $antrag["id"] ]);
      // add new values
      $ret1 = writeFormdata($antrag["id"], $isPartiell, $form, $antrag);
      $ret = $ret && $ret1;
      // delete files (tbl anhang) and change fieldname
      function buildAnhangRenameMap($antrag_id, $newFieldName, $formdata, &$fieldNameMap) {
        global $msgs;

        if (is_array($formdata)) {
          $ret = true;
          foreach (array_keys($formdata) as $key) {
            $ret1 = buildAnhangRenameMap($antrag_id, "${newFieldName}[${key}]", $formdata[$key], $fieldNameMap);
            $ret = $ret && $ret1;
          }
          return $ret;
        }

        if ($formdata == "") {
          $msgs[] = "old field name empty for $newFieldName";
          return true; // no empty name
        }

        $fieldNameMap[$formdata /* aka oldFieldName */] = $newFieldName;
        return true;
      }

      if (!$isPartiell) { // dynamic tables cannot be edited with editPartiell
        $fieldNameMap = [];
        foreach($_REQUEST["formdata"] as $fieldname => $value) {
          $fieldtype = $_REQUEST["formtype"][$fieldname];
          if ($fieldtype != "file" && $fieldtype != "multifile") continue;
          if (!is_array($value)) continue;
          if (!isset($value["oldFieldName"])) continue;
          $ret1 = buildAnhangRenameMap($antrag["id"], $fieldname, $value["oldFieldName"], $fieldNameMap);
          $ret = $ret && $ret1;
        }

        $anhaenge = dbFetchAll("anhang", [ "antrag_id" => $antrag["id"] ]);
        foreach($anhaenge as $anhang) {
          $oldFieldName = $anhang["fieldname"];
          if (isset($fieldNameMap[$oldFieldName])) {
            $newFieldName = $fieldNameMap[$oldFieldName];
            if ($newFieldName != $oldFieldName) {
              $ret1 = dbUpdate("anhang", [ "antrag_id" => $anhang["antrag_id"], "id" => $anhang["id"] ], [ "fieldname" => $newFieldName ]);
              $ret = $ret && ($ret1 === 1);
            }
          } else {
            $msgs[] = "Lösche Anhang ".$anhang["fieldname"]." / ".$anhang["filename"];
            $ret1 = dbDelete("anhang", [ "antrag_id" => $anhang["antrag_id"], "id" => $anhang["id"] ]);
            $ret = $ret && ($ret1 === 1);
            $filesRemoved[] = $STORAGE."/".$anhang["antrag_id"]."/".$anhang["path"];
          }
        }
      } /* isPartiell */
      // rename files (aka filename) (tbl anhang)
      function renameAnhang($antrag_id, $fieldname, $formdata) {
        global $msgs;

        if (is_array($formdata)) {
          $ret = true;
          foreach (array_keys($formdata) as $key) {
            $ret1 = renameAnhang($antrag_id, "${fieldname}[${key}]", $formdata[$key]);
            $ret = $ret && $ret1;
          }
          return $ret;
        }

        if ($formdata == "") return true; // no empty name

        $anhang = dbGet("anhang", [ "antrag_id" => $antrag_id, "fieldname" => $fieldname ]);
        if ($anhang === false) {
          $msgs[] = "Anhang $fieldname not found for rename.";
          return false;
        }

        $ret = dbUpdate("anhang", [ "antrag_id" => $antrag_id, "id" => $anhang["id"] ], [ "filename" => $formdata ] );
        $ret = ($ret === 1);
        if (!$ret) {
          $msgs[]="failed field $fieldname => filename $formdata";
        }

        return $ret;
      }
      foreach($_REQUEST["formdata"] as $fieldname => $value) {
        $fieldtype = $_REQUEST["formtype"][$fieldname];
        if ($fieldtype != "file" && $fieldtype != "multifile") continue;
        if (!is_array($value)) continue;
        if (!isset($value["newFileName"])) continue;
        if ($isPartiell) {
          $perm = "canEditPartiell.field.{$fieldname}";
          if (!hasPermission($form, $antrag, $perm)) continue;
        }
        $ret1 = renameAnhang($antrag["id"], $fieldname, $value["newFileName"]);
        $ret = $ret && $ret1;
      }
      // add or replace (or delete) new files (tbl anhang) and write files to disk
      $ret1 = writeFormdataFiles($antrag["id"], $msgs, $filesRemoved, $filesCreated, $isPartiell, $form, $antrag);
      $ret = $ret && $ret1;
      // commitTx
      if ($ret && isset($_REQUEST["state"]) && $_REQUEST["state"] != "") {
        $newState = $_REQUEST["state"];
        $antrag = getAntrag(); // report new version to user
        $ret = writeState($newState, $antrag, $form, $msgs, $filesCreated, $filesRemoved, $altTarget);
      }
      if ($ret && !isValid($antrag["id"], "postEdit", $msgs))
        $ret = false;
      if ($ret)
        $ret = dbCommit();
      if (!$ret) {
        dbRollBack();
        foreach ($filesCreated as $f) {
          if (@unlink($f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      } else {
        // delete files from disk after successfull commit
        foreach ($filesRemoved as $f) {
          if (@unlink($f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      }
      if ($ret) {
        $forceClose = true;
        $target = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"]);
        if (isset($_REQUEST["subaction"]) && $_REQUEST["subaction"] == "resumeEdit") {
          switch ($_POST["action"]) {
            case "antrag.update":
              $target .= "/edit";
              break;
            case "antrag.updatePartiell":
              $target .= "/editPartiell";
              break;
          }
          if (isset($_REQUEST["overrideOnNextEdit"])) {
            $target .= "?".http_build_query([ "override" => $_REQUEST["overrideOnNextEdit"] ] );
          }
        } elseif ($altTarget !== false) {
          # nothing special here, we can safely redirect to new form
          $target = $altTarget;
          $altTarget = false;
        }
      }
      break;
    case "antrag.create":
      if (!dbBegin()) {
        $msgs[] = "Cannot start DB transaction";
        $ret = false;
        break;
      }

      $form = getForm($_REQUEST["type"], $_REQUEST["revision"]);
      if ($form === false)
        die("Unbekannte Formularversion");

      if (!hasPermission($form, null, "canCreate")) die("Antrag ist nicht erstellbar");

      $filesCreated = []; $filesRemoved = [];

      $antrag = [];
      $antrag["type"] = $_REQUEST["type"];
      $antrag["revision"] = $_REQUEST["revision"];
      $antrag["creator"] = getUsername();
      $antrag["creatorFullName"] = getUserFullName();
      $antrag["token"] = $token = substr(sha1(sha1(mt_rand())),0,16);
      $antrag["createdat"] = date("Y-m-d H:i:s");
      $antrag["lastupdated"] = date("Y-m-d H:i:s");
      $createState = "draft";
      if (isset($form["_class"]["createState"]))
        $createState = $form["_class"]["createState"];
      $antrag["state"] = $createState; // FIXME custom default state
      $antrag["stateCreator"] = getUsername();
      $ret = dbInsert("antrag", $antrag);
      if ($ret !== false) {
        $target = str_replace("//","/",$URIBASE."/").rawurlencode($token);
        $antrag_id = (int) $ret;
        #$msgs[] = "Antrag wurde erstellt.";

        $ret0 = writeFormdata($antrag_id, false, null, null);

        $ret1 = writeFormdataFiles($antrag_id, $msgs, $filesRemoved, $filesCreated, false, null, null);

        $ret = $ret && $ret0 && $ret1;
      } /* dbInsert(antrag) -> $ret !== false */
      if (count($filesRemoved) > 0) die("ups files removed during antrag.create");
      if ($ret) {
        $antrag = getAntrag($antrag_id); // report new version to user
        $ret = doNewStateActions($form, "create.$createState", $antrag, $createState, $msgs, $filesCreated, $filesRemoved, $target);
        if ($ret === false) $msgs[] = "Mit Status verknüpfte Aktion fehlgeschlagen.";
      }
      if (isset($_REQUEST["state"]) && $ret && $_REQUEST["state"] != "") {
        $antrag = getAntrag($antrag_id); // report new version to user
        if ($antrag === false) die("Ups failed to read antrag just created");
        $newState = $_REQUEST["state"];
        $ret = writeState($newState, $antrag, $form, $msgs, $filesCreated, $filesRemoved, $target);
        if ($ret === false) $msgs[] = "Mit Status verknüpfte Aktion fehlgeschlagen.";
      }
      if ($ret && !isValid($antrag["id"], "postEdit", $msgs))
        $ret = false;

      if ($ret)
        $ret = dbCommit();
      if (!$ret) {
        dbRollBack();
        foreach ($filesCreated as $f) {
          if (@unlink($f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      } else {
        foreach ($filesRemoved as $f) {
          if (@unlink($f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      }

      if ($ret && isset($_REQUEST["subaction"]) && $_REQUEST["subaction"] == "resumeEdit") {
        $target .= "/edit";
      }
      break;
    case "antrag.copy":
      if (!dbBegin()) {
        $msgs[] = "Cannot start DB transaction";
        $ret = false;
        break;
      }
      $ret = true;

      $filesCreated = []; $filesRemoved = [];
      $oldAntragNewState = false;
      if (isset($_REQUEST["copy_from_state"]))
        $oldAntragNewState = $_REQUEST["copy_from_state"];
      $antrag_id = copyAntrag($_REQUEST["copy_from"], $_REQUEST["copy_from_version"], $oldAntragNewState, $_REQUEST["type"], $_REQUEST["revision"], $msgs, $filesCreated, $filesRemoved, $target);
      if ($antrag_id === false)
        $ret = false;
      else
        $target .= "/edit";

      if (count($filesRemoved) > 0) die("ups files removed during antrag.copy");
      if ($ret && !isValid($antrag_id, "postEdit", $msgs))
        $ret = false;
      if ($ret)
        $ret = dbCommit();
      if (!$ret) {
        dbRollBack();
        foreach ($filesCreated as $f) {
          if (@unlink($f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      } else {
        foreach ($filesRemoved as $f) {
          if (@unlink($f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      }

      break;
    case "antrag.state":
      // beginTx
      if (!dbBegin()) {
        $msgs[] = "Cannot start DB transaction";
        $ret = false;
        break;
      }
      $filesCreated = []; $filesRemoved = [];

      $antrag = getAntrag();
      // check antrag type and revision, token cannot be altered
      if ($_REQUEST["type"] !== $antrag["type"]) die("Unerlaubter Typ");
      if ($_REQUEST["revision"] !== $antrag["revision"]) die("Unerlaubte Version");

      if ($_REQUEST["version"] !== $antrag["version"]) {
        $ret = false;
        $msgs[] = "Der Antrag wurde von jemanden anderes bearbeitet und kann daher nicht gespeichert werden.";
      } else {
        $ret = true;
      }

      $newState = $_REQUEST["state"];
      if ($ret) {
        $form = getForm($antrag["type"], $antrag["revision"]);
        $ret = writeState($newState, $antrag, $form, $msgs, $filesCreated, $filesRemoved, $target);
      }

      // commitTx
      if ($ret && !isValid($antrag["id"], "postEdit", $msgs))
        $ret = false;
      if ($ret)
        $ret = dbCommit();
      if (!$ret) {
        dbRollBack();
        foreach ($filesCreated as $f) {
          if (@unlink($f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      } else {
        // delete files from disk after successfull commit
        foreach ($filesRemoved as $f) {
          if (@unlink($f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      }
      if ($ret) {
        $forceClose = true;
        if ($target === false)
          $target = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"]);
      }
      break;
    case "hibiscus":
      requireGroup($HIBISCUSGROUP);

      $ret = true;  
      if (!dbBegin()) {
        $msgs[] = "Cannot start DB transaction";
        $ret = false;
        break;
      }
  
      $newFormAnfangsbestand = fetchFromHibiscusAnfangsbestand();
      foreach ($newFormAnfangsbestand as $inhalte) {
        $antrag = [];
        $antrag["type"] = "zahlung";
        $antrag["revision"] = "v1-anfangsbestand";
        $antrag["creator"] = getUsername();
        $antrag["creatorFullName"] = getUserFullName();
        $antrag["token"] = $token = substr(sha1(sha1(mt_rand())),0,16);
        $antrag["createdat"] = date("Y-m-d H:i:s");
        $antrag["lastupdated"] = date("Y-m-d H:i:s");
        $createState = "draft";
        $form = getForm($antrag["type"], $antrag["revision"]);
        if (isset($form["_class"]["createState"]))
          $createState = $form["_class"]["createState"];
        $antrag["state"] = $createState; // FIXME custom default state
        $antrag["stateCreator"] = getUsername();
        $ret0 = dbInsert("antrag", $antrag);
        if ($ret0 === false) {
          $msgs[] = "antrag.create failed";
          $ret = false;
          continue;
        }
        $antrag_id = (int) $ret0;
  
        foreach ($inhalte as $inhalt) {
          $inhalt["antrag_id"] = $antrag_id;
          $ret0 = dbInsert("inhalt", $inhalt);
          $ret = $ret && $ret0;
        }

        if ($ret && !isValid($antrag_id, "postEdit", $msgs))
          $ret = false;
      }

      $newFormZahlungen = fetchFromHibiscus();
  
      foreach ($newFormZahlungen as $inhalte) {
        $antrag = [];
        $antrag["type"] = "zahlung";
        $antrag["revision"] = "v1-giro-hibiscus";
        $antrag["creator"] = getUsername();
        $antrag["creatorFullName"] = getUserFullName();
        $antrag["token"] = $token = substr(sha1(sha1(mt_rand())),0,16);
        $antrag["createdat"] = date("Y-m-d H:i:s");
        $antrag["lastupdated"] = date("Y-m-d H:i:s");
        $createState = "draft";
        $form = getForm($antrag["type"], $antrag["revision"]);
        if (isset($form["_class"]["createState"]))
          $createState = $form["_class"]["createState"];
        $antrag["state"] = $createState; // FIXME custom default state
        $antrag["stateCreator"] = getUsername();
        $ret0 = dbInsert("antrag", $antrag);
        if ($ret0 === false) {
          $msgs[] = "antrag.create failed";
          $ret = false;
          continue;
        }
        $antrag_id = (int) $ret0;
  
        foreach ($inhalte as $inhalt) {
          $inhalt["antrag_id"] = $antrag_id;
          $ret0 = dbInsert("inhalt", $inhalt);
          $ret = $ret && $ret0;
        }

        if ($ret && !isValid($antrag_id, "postEdit", $msgs))
          $ret = false;
      }
      if ($ret)
        $ret = dbCommit();
      if (!$ret)
        dbRollBack();
      if ($ret) {
        $forceClose = true;
        $target = "$URIBASE?tab=booking";
      }
    break;
    case "booking":
      requireGroup($HIBISCUSGROUP);
      $zahlungSum = 0.00;
      $grundSum = 0.00;

      if (!isset($_REQUEST["zahlungId"])) $_REQUEST["zahlungId"] = [];
      if (!isset($_REQUEST["grundId"])) $_REQUEST["grundId"] = [];
      if (count($_REQUEST["grundId"]) != 1 && count($_REQUEST["zahlungId"]) != 1 ) {
        $msgs[] = "Nur 1:n oder n:1 Zuordnungen erlaubt. Zu viele oder keine Buchungen ausgewählt.";
        $ret = false;
        break;
      }

      foreach($_REQUEST["zahlungId"] as $aId)
        $zahlungSum += $_REQUEST["zahlungValue"][$aId];
      foreach($_REQUEST["grundId"] as $aId)
        $grundSum += $_REQUEST["grundValue"][$aId];

      if ($zahlungSum != $grundSum) {
        $msgs[] = "Die Beträge stimmen nicht überein: $zahlungSum != $grundSum.";
        $ret = false;
        break;
      }

      if (!dbBegin()) {
        $msgs[] = "Cannot start DB transaction";
        $ret = false;
        break;
      } else {
        $ret = true;
      }

      $filesCreated = []; $filesRemoved = [];

      foreach($_REQUEST["zahlungId"] as $zId) {
        $appendGrund = [];
        foreach($_REQUEST["grundId"] as $gId) {
          if (count($_REQUEST["zahlungId"]) == 1) {
            # alle Gründe zusammen addieren auf diese Zahlung
            $appendGrund[] = [ $gId, $_REQUEST["grundValue"][$gId] ];
          } elseif (count($_REQUEST["grundId"]) == 1) {
            # alle Zahlungen zusammen addieren auf diesen Grund
            $appendGrund[] = [ $gId, $_REQUEST["zahlungValue"][$zId] ];
          } else {
            die("ups cannot compute this");
          }
        }
        $z = getAntrag($zId);
        if ($z === false) {
          $msgs[] = "Zahlung $zId war nicht lesbar.";
          $ret = false;
          break;
        }
        $form = getForm($z["type"], $z["revision"]);
        if (!hasPermission($form, $z, "canEdit")) die("Antrag ist nicht editierbar");

        $rowCountFieldName =  "zahlung.grund.table[rowCount]";
        $rowNumber = getFormValueInt($rowCountFieldName, null, $z["_inhalt"], false);
        $rowNumberPresent = true;
        if ($rowNumber === false) {
          $rowNumberPresent = false;
          $rowNumber = 0;
         }
        $rowIdNumberPresent = true;
        $rowIdCountFieldName =  "zahlung.grund.table[rowIdCount]";
        $rowIdNumber = getFormValueInt($rowIdCountFieldName, null, $z["_inhalt"], false);
        if ($rowIdNumber === false) {
          $rowIdNumberPresent = false;
          $rowIdNumber = 0;
         }
        foreach ($appendGrund as $i => $a) {

          $rowIdFieldName = "zahlung.grund.table[rowId][{$rowNumber}]";
          $inhalt = [ "fieldname" => $rowIdFieldName, "contenttype" => "table", "antrag_id" => $zId ];
          $inhalt["value"] = $rowIdNumber;
          $ret0 = dbInsert("inhalt", $inhalt);
          if ($ret0 === false) $ret = false;

          $rowBelegFieldName = "zahlung.grund.beleg[{$rowNumber}]";
          $inhalt = [ "fieldname" => $rowBelegFieldName, "contenttype" => "otherForm", "antrag_id" => $zId ];
          $inhalt["value"] = $a[0];
          $ret0 = dbInsert("inhalt", $inhalt);
          if ($ret0 === false) $ret = false;

          $value = $a[1];
          if ($value < 0) {
            $value *= -1.0;
            $rowGeldFieldName = "zahlung.grund.ausgaben[{$rowNumber}]";
          } else {
            $rowGeldFieldName = "zahlung.grund.einnahmen[{$rowNumber}]";
          }
          $inhalt = [ "fieldname" => $rowGeldFieldName, "contenttype" => "money", "antrag_id" => $zId ];
          $inhalt["value"] = convertUserValueToDBValue($value, $inhalt["contenttype"]);
          $ret0 = dbInsert("inhalt", $inhalt);
          if ($ret0 === false) $ret = false;

          $rowNumber++;
          $rowIdNumber++;
        }
        if ($rowNumberPresent) {
          $ret0 = dbUpdate("inhalt", ["fieldname" => $rowCountFieldName, "contenttype" => "table", "antrag_id" => $zId ], [ "value" => $rowNumber ] );
        } else {
          $ret0 = dbInsert("inhalt", [ "fieldname" => $rowCountFieldName, "contenttype" => "table", "antrag_id" => $zId, "value" => $rowNumber ] );
        }
        if ($ret0 === false) $ret = false;

        if ($rowIdNumberPresent) {
          $ret0 = dbUpdate("inhalt", ["fieldname" => $rowIdCountFieldName, "contenttype" => "table", "antrag_id" => $zId ], [ "value" => $rowIdNumber ] );
        } else {
          $ret0 = dbInsert("inhalt", [ "fieldname" => $rowIdCountFieldName, "contenttype" => "table", "antrag_id" => $zId, "value" => $rowIdNumber ] );
        }
        if ($ret0 === false) $ret = false;

        if ($ret && !isValid($zId, "postEdit", $msgs))
          $ret = false;
      }

      // alle Zahlungen wechseln nach state payed
      foreach($_REQUEST["zahlungId"] as $aId) {
        $a = dbGet("antrag", ["id" => $aId]);
        $a["_inhalt"] = dbFetchAll("inhalt", ["antrag_id" => $aId]);
        $form = getForm($a["type"], $a["revision"]);
        $ret0 = writeState("booked", $a, $form, $msgs, $filesCreated, $filesRemoved, $target);
        $ret = $ret && $ret0;
      }

      // alle Buchungen wechseln nach state booked
      foreach($_REQUEST["grundId"] as $aId) {
        $a = dbGet("antrag", ["id" => $aId]);
        $a["_inhalt"] = dbFetchAll("inhalt", ["antrag_id" => $aId]);
        $form = getForm($a["type"], $a["revision"]);
        $ret0 = writeState("payed", $a, $form, $msgs, $filesCreated, $filesRemoved, $target);
        $ret = $ret && $ret0;
      }

      if ($ret)
        $ret = dbCommit();
      if (!$ret) {
        dbRollBack();
        foreach ($filesCreated as $f) {
          if (@unlink($f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      } else {
        // delete files from disk after successfull commit
        foreach ($filesRemoved as $f) {
          if (@unlink($f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      }
      if ($ret) {
        $forceClose = true;
        $target = "$URIBASE?tab=booking";
      }
    break;
    default:
      logAppend($logId, "__result", "invalid action");
      die("Aktion nicht bekannt.");
  endswitch;
 } /* switch */

 if ($logId !== false) {
   logAppend($logId, "__result", ($ret !== false) ? "ok" : "failed");
   logAppend($logId, "__result_msg", $msgs);
 }

 $result = Array();
 $result["msgs"] = $msgs;
 $result["ret"] = ($ret !== false);
 if ($target !== false)
   $result["target"] = $target;
 if ($altTarget !== false)
   $result["altTarget"] = $altTarget;
 $result["forceClose"] = ($forceClose !== false);
# $result["_REQUEST"] = $_REQUEST;
# $result["_FILES"] = $_FILES;

 header("Content-Type: text/json; charset=UTF-8");
 echo json_encode($result);
 exit;
}

if (!isset($_REQUEST["tab"])) {
  $_REQUEST["tab"] = "antrag.listing";
}

switch($_REQUEST["tab"]) {
  case "antrag.anhang":
    if (count($_REQUEST["__args"]) == 0)
      break;

    $antrag = getAntrag();
    $f = ["antrag_id" => $antrag["id"], "id" => $_REQUEST["__args"][0]];
    $ah = dbGet("anhang", $f);
    if ($ah === false) die("Antrag nicht gefunden.");
    header("Content-Type: ".$ah["mimetype"]);
    header('Content-Disposition: attachment; filename="' . $antrag["id"]."-".$ah["id"]." ".$ah["filename"]. '"');
    header('Content-Transfer-Encoding: binary');
    header('Accept-Ranges: bytes');
    header('Cache-Control: private');
    header('Pragma: private');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    $fileSize = $ah["size"];
    $filePath = $STORAGE."/".$ah["antrag_id"]."/".$ah["path"];

    // Multipart-Download and Download Resuming Support
    if(isset($_SERVER['HTTP_RANGE'])) {
      list($a, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
      list($range) = explode(',', $range, 2);
      list($range, $rangeEnd) = explode('-', $range);

      $range = intval($range);

      if(!$rangeEnd) {
        $rangeEnd = $fileSize - 1;
      }
      else {
        $rangeEnd = intval($rangeEnd);
      }

      $newLength = $rangeEnd - $range + 1;

      // Send Headers
      header('HTTP/1.1 206 Partial Content');
      header('Content-Length: ' . $newLength);
      header("Content-Range: bytes $range-$rangeEnd/$fileSize");
    }
    else {
      $range = 0;
      $newLength = $fileSize;
      header('Content-Length: ' . $fileSize);
    }

    // Output File
    $chunkSize = 1 * (1024*1024);
    $bytesSend = 0;

    if($file = fopen($filePath, 'r')) {
      if(isset($_SERVER['HTTP_RANGE']))
        fseek($file, $range);

      while(!feof($file) && !connection_aborted() && $bytesSend < $newLength) {
        $buffer = fread($file, $chunkSize);
        echo $buffer;
        flush();
          $bytesSend += strlen($buffer);
      }

      fclose($file);
    }

    exit;
  case "antrag.print":
    global $inlineCSS;
    $inlineCSS = true;
    require "../template/header-print.tpl";

    $antrag = getAntrag();
    $form = getForm($antrag["type"],$antrag["revision"]);
    if ($form === false) die("Unbekannter Formulartyp/-revision, kann nicht dargestellt werden.");

    require "../template/antrag.head.tpl";
    require "../template/antrag.tpl";
    require "../template/antrag.foot-print.tpl";
    require "../template/footer-print.tpl";
    exit;
}

require "../template/header.tpl";

switch($_REQUEST["tab"]) {
  case "antrag.listing":
    $tmp = dbFetchAll("antrag", [], ["type" => true, "revision" => true, "lastupdated" => false]);
    $antraege = [];
    foreach ($tmp as $t) {
      $form = getForm($t["type"],$t["revision"]);
      if (false === $form) continue;
      $t["_inhalt"] = dbFetchAll("inhalt", ["antrag_id" => $t["id"] ]);
      if (!hasPermission($form, $t, "canRead")) continue;
      $antraege["all"][$t["type"]][$t["revision"]][$t["id"]] = $t;
      foreach (array_keys($form["_categories"]) as $cat) {
        if (!hasCategory($form, $t, $cat)) continue;
        $antraege[$cat][$t["type"]][$t["revision"]][$t["id"]] = $t;
      }
    }
    require "../template/antrag.createpanel.tpl";
    require "../template/antrag.list.tpl";
    require "../template/antrag.actions.tpl";
  break;
  case "antrag":
    $antrag = getAntrag();
    $form = getForm($antrag["type"],$antrag["revision"]);
    if ($form === false) die("Unbekannter Formulartyp/-revision, kann nicht dargestellt werden.");

    $tmp = dbFetchAll("inhalt", ["contenttype" => "otherForm", "value" => $antrag["id"]], ["antrag_id" => true]);
    $idx = [];
    $antraegeRef = [];
    foreach ($tmp as $t) {
      $antrag_id = $t["antrag_id"];

      if (isset($idx[$antrag_id])) continue;
      $idx[$antrag_id] = true;

      $a = dbGet("antrag", ["id" => $antrag_id]);
      $f = getForm($a["type"],$a["revision"]);
      if (false === $f) continue;

      $a["_inhalt"] = dbFetchAll("inhalt", ["antrag_id" => $a["id"] ]);
      if (!hasPermission($f, $a, "canRead")) continue;

      $antraegeRef[$a["type"]][$a["revision"]][$a["id"]] = $a;
    }

    require "../template/antrag.menu.tpl";
    require "../template/antrag.state.tpl";
    require "../template/antrag.subcreate.tpl";
    require "../template/antrag.copy.tpl";
    require "../template/antrag.tpl";
    require "../template/antrag.ref.tpl";
    require "../template/antrag.comments.tpl";
  break;
  case "antrag.edit":
    $antrag = getAntrag();
    $form = getForm($antrag["type"], $antrag["revision"]);
    if (!hasPermission($form, $antrag, "canEdit")) die("Antrag ist nicht editierbar");

    $form = getForm($antrag["type"],$antrag["revision"]);
    if ($form === false) die("Unbekannter Formulartyp/-revision, kann nicht dargestellt werden.");

    require "../template/antrag.head.tpl";
    require "../template/antrag.edit.tpl";
  break;
  case "antrag.editPartiell":
    $antrag = getAntrag();
    $form = getForm($antrag["type"], $antrag["revision"]);
    if (!hasPermission($form, $antrag, "canEditPartiell")) die("Antrag ist nicht partiell editierbar");

    $form = getForm($antrag["type"],$antrag["revision"]);
    if ($form === false) die("Unbekannter Formulartyp/-revision, kann nicht dargestellt werden.");

    require "../template/antrag.head.tpl";
    require "../template/antrag.editPartiell.tpl";
  break;
  case "antrag.create":
    if (!isset($_REQUEST["type"]) || !isset($_REQUEST["revision"])) {
      header("Location: $URIBASE");
      exit;
    }
    $form = getForm($_REQUEST["type"], $_REQUEST["revision"]);
    if ($form === false) die("Unbekannter Formulartyp oder keine Berechtigung");
    if (!hasPermission($form, null, "canCreate")) die("Antrag ist nicht erstellbar");

    require "../template/antrag.head.tpl";
    require "../template/antrag.create.tpl";
  break;
  case "booking":
    requireGroup($HIBISCUSGROUP);
    $alGrund = dbFetchAll("antrag", [ "type" => "auslagenerstattung-genehmigung", "state" => "ok" ] );
    foreach(array_keys($alGrund) as $i) {
      $antrag = $alGrund[$i];
      $inhalt = dbFetchAll("inhalt", ["antrag_id" => $antrag["id"]]);
      $antrag["_inhalt"] = $inhalt;
      $ctrl = ["_values" => $antrag, "render" => [ "no-form"] ];
      $form = getForm($antrag["type"], $antrag["revision"]);
      ob_start();
      $success = renderFormImpl($form, $ctrl);
      ob_end_clean();

      $value = 0.00;
      if (isset($ctrl["_render"]) && isset($ctrl["_render"]->addToSumValue["ausgaben"])) {
        $value -= $ctrl["_render"]->addToSumValue["ausgaben"];
      }
      if (isset($ctrl["_render"]) && isset($ctrl["_render"]->addToSumValue["einnahmen"])) {
        $value += $ctrl["_render"]->addToSumValue["einnahmen"];
      }
      if (isset($ctrl["_render"]) && isset($ctrl["_render"]->addToSumValue["ausgaben.zahlung"])) {
        $value += $ctrl["_render"]->addToSumValue["ausgaben.zahlung"];
      }
      if (isset($ctrl["_render"]) && isset($ctrl["_render"]->addToSumValue["einnahmen.zahlung"])) {
        $value -= $ctrl["_render"]->addToSumValue["einnahmen.zahlung"];
      }

      $antrag["_value"] = $value;
      $antrag["_ctrl"] = $ctrl;
      $antrag["_form"] = $form;
      $alGrund[$i] = $antrag;
    }
    $alZahlung = dbFetchAll("antrag", [ "type" => "zahlung", "state" => "payed" ] );
    foreach(array_keys($alZahlung) as $i) {
      $antrag = $alZahlung[$i];
      $inhalt = dbFetchAll("inhalt", ["antrag_id" => $antrag["id"]]);
      $antrag["_inhalt"] = $inhalt;
      $ctrl = ["_values" => $antrag, "render" => [ "no-form"] ];
      $form = getForm($antrag["type"], $antrag["revision"]);
      ob_start();
      $success = renderFormImpl($form, $ctrl);
      ob_end_clean();

      $value = 0.00;
      if (isset($ctrl["_render"]) && isset($ctrl["_render"]->addToSumValue["ausgaben"])) {
        $value -= $ctrl["_render"]->addToSumValue["ausgaben"];
      }
      if (isset($ctrl["_render"]) && isset($ctrl["_render"]->addToSumValue["einnahmen"])) {
        $value += $ctrl["_render"]->addToSumValue["einnahmen"];
      }
      if (isset($ctrl["_render"]) && isset($ctrl["_render"]->addToSumValue["ausgaben.beleg"])) {
        $value += $ctrl["_render"]->addToSumValue["ausgaben.beleg"];
      }
      if (isset($ctrl["_render"]) && isset($ctrl["_render"]->addToSumValue["einnahmen.beleg"])) {
        $value -= $ctrl["_render"]->addToSumValue["einnahmen.beleg"];
      }

      $antrag["_value"] = $value;
      $antrag["_ctrl"] = $ctrl;
      $antrag["_form"] = $form;
      $alZahlung[$i] = $antrag;
    }
    usort($alGrund, function ($a, $b) {
      if ($a["_value"] < $b["_value"]) return -1;
      if ($a["_value"] > $b["_value"]) return 1;
      return 0;
    });
    usort($alZahlung, function ($a, $b) {
      if ($a["_value"] < $b["_value"]) return -1;
      if ($a["_value"] > $b["_value"]) return 1;
      return 0;
    });

    require "../template/booking.tpl";
  break;
#  case "antrag.submitted":
#    require "../template/antrag.submitted.tpl";
#  break;
  default:
    echo "invalid tab name: ".htmlspecialchars($_REQUEST["tab"]);
}

require "../template/footer.tpl";

exit;

