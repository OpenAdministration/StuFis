<?php
global $attributes, $logoutUrl, $ADMINGROUP, $nonce, $URIBASE, $antrag, $STORAGE;
ob_start('ob_gzhandler');

require_once "../lib/inc.all.php";
requireAuth();
#requireGroup($ADMINGROUP);

function writeFormdata($antrag_id) {
  if (!isset($_REQUEST["formdata"]))
    $_REQUEST["formdata"] = [];

  function storeInhalt($inhalt) {
    if (is_array($inhalt["value"])) {
      $fieldname = $inhalt["fieldname"];
      $ret = true;
      foreach ($inhalt["value"] as $i => $value) {
        $inhalt["fieldname"] = $fieldname . "[{$i}]";
        $inhalt["value"] = $value;
        $ret1 = storeInhalt($inhalt);
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
    $inhalt = [];
    $inhalt["antrag_id"] = $antrag_id;
    $inhalt["contenttype"] = $_REQUEST["formtype"][$fieldname];
    $inhalt["fieldname"] = $fieldname;
    $inhalt["value"] = $value;
    $ret1 = storeInhalt($inhalt);
    $ret = $ret && $ret1;
  } /* formdata */

  return $ret;
}

function writeFormdataFiles($antrag_id, &$msgs, &$filesRemoved, &$filesCreated) {
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
      $oldAnhang = dbGet("anhang", [ "antrag_id" => $anhang["antrag_id"], "fieldname" => $names ]);
      if ($oldAnhang !== false) {
        $ret = dbDelete("anhang", [ "antrag_id" => $oldAnhang["antrag_id"], "id" => $oldAnhang["id"] ]);
        $ret = ($ret === 1);
        $filesRemoved[] = $STORAGE."/".$oldAnhang["path"];
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

    $dbPath = $anhang["antrag_id"]."/".uniqid().".".pathinfo($names, PATHINFO_EXTENSION);
    $path = $STORAGE."/".$dbPath;
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

if (isset($_REQUEST["action"])) {
 global $msgs;
 $msgs = Array();
 $ret = false;
 $target = false;
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
      $antrag = getAntrag();
      // check antrag editability (state == DRAFT or alike) FIXME
      if ("draft" !== $antrag["state"]) die("Antrag ist nicht editierbar");
      // check antrag type and revision, token cannot be altered
      if ($_REQUEST["type"] !== $antrag["type"]) die("Unerlaubter Typ");
      if ($_REQUEST["revision"] !== $antrag["revision"]) die("Unerlaubte Version");
      if ($_REQUEST["version"] !== $antrag["version"]) {
        $ret = false;
        $msgs[] = "Der Antrag wurde von jemanden anderes bearbeitet und kann daher nicht gespeichert werden.";
      } else {
        $ret = true;
      }
      // beginTx
      if (!dbBegin()) {
        $msgs[] = "Cannot start DB transaction";
        $ret = false;
        goto outAntragDelete;
      }
      $filesCreated = []; $filesRemoved = [];

      $anhaenge = dbFetchAll("anhang", [ "antrag_id" => $antrag["id"] ]);
      foreach($anhaenge as $anhang) {
        $msgs[] = "Lösche Anhang ".$anhang["fieldname"]." / ".$anhang["filename"];
        $ret1 = dbDelete("anhang", [ "antrag_id" => $anhang["antrag_id"], "id" => $anhang["id"] ]);
        $ret = $ret && ($ret1 === 1);
        $filesRemoved[] = $anhang["path"];
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
          if (@unlink($STORAGE."/".$f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      } else {
        // delete files from disk after successfull commit
        foreach ($filesRemoved as $f) {
          if (@unlink($STORAGE."/".$f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      }
outAntragDelete:
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
      $antrag = getAntrag();
      // check antrag editability (state == DRAFT or alike) FIXME
      if ("draft" !== $antrag["state"]) die("Antrag ist nicht editierbar");
      // check antrag type and revision, token cannot be altered
      if ($_REQUEST["type"] !== $antrag["type"]) die("Unerlaubter Typ");
      if ($_REQUEST["revision"] !== $antrag["revision"]) die("Unerlaubte Version");
      if ($_REQUEST["version"] !== $antrag["version"]) {
        $ret = false;
        $msgs[] = "Der Antrag wurde von jemanden anderes bearbeitet und kann daher nicht gespeichert werden.";
      } else {
        $ret = true;
      }
      // beginTx
      if (!dbBegin()) {
        $msgs[] = "Cannot start DB transaction";
        $ret = false;
        goto outAntragUpdate;
      }
      $filesCreated = []; $filesRemoved = [];
      // update last-modified timestamp
      dbUpdate("antrag", [ "id" => $antrag["id"] ], ["lastupdated" => date("Y-m-d H:i:s"), "version" => $antrag["version"] + 1 ]);
      // clear all old values (tbl inhalt)
      dbDelete("inhalt", [ "antrag_id" => $antrag["id"] ]);
      // add new values
      $ret1 = writeFormdata($antrag["id"]);
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
        if (!isset($fieldNameMap[$oldFieldName])) {
          $msgs[] = "Lösche Anhang ".$anhang["fieldname"]." / ".$anhang["filename"];
          $ret1 = dbDelete("anhang", [ "antrag_id" => $anhang["antrag_id"], "id" => $anhang["id"] ]);
          $ret = $ret && ($ret1 === 1);
          $filesRemoved[] = $anhang["path"];
        } else {
          $newFieldName = $fieldNameMap[$oldFieldName];
          if ($newFieldName != $oldFieldName) {
            $ret1 = dbUpdate("anhang", [ "antrag_id" => $anhang["antrag_id"], "id" => $anhang["id"] ], [ "fieldname" => $newFieldName ]);
            $ret = $ret && ($ret1 === 1);
          }
        }
      }
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
        $ret1 = renameAnhang($antrag["id"], $fieldname, $value["newFileName"]);
        $ret = $ret && $ret1;
      }
      // add or replace (or delete) new files (tbl anhang) and write files to disk
      $ret1 = writeFormdataFiles($antrag["id"], $msgs, $filesRemoved, $filesCreated);
      $ret = $ret && $ret1;
      // commitTx
      if ($ret)
        $ret = dbCommit();
      if (!$ret) {
        dbRollBack();
        foreach ($filesCreated as $f) {
          if (@unlink($STORAGE."/".$f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      } else {
        // delete files from disk after successfull commit
        foreach ($filesRemoved as $f) {
          if (@unlink($STORAGE."/".$f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      }
outAntragUpdate:
      if ($ret) {
        $forceClose = true;
        $target = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["token"]);
      }
      break;
    case "antrag.create":
      if (false === getForm($_REQUEST["type"], $_REQUEST["revision"]))
        die("Unbekannte Formularversion");
      // FIXME check perm

      if (!dbBegin()) {
        $msgs[] = "Cannot start DB transaction";
        $ret = false;
        goto outAntragCreate;
      }
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
      $ret = dbInsert("antrag", $antrag);
      if ($ret !== false) {
        $target = str_replace("//","/",$URIBASE."/").rawurlencode($token);
        $antrag_id = (int) $ret;
        $msgs[] = "Antrag wurde erstellt.";

        # write formdata
        $ret0 = writeFormdata($antrag_id);

        $ret1 = writeFormdataFiles($antrag_id, $msgs, $filesRemoved, $filesCreated);

        $ret = $ret && $ret0 && $ret1;
      } /* dbInsert(antrag) -> $ret !== false */
      if (count($filesRemoved) > 0) die("ups files removed during antrag.create");
      if ($ret)
        $ret = dbCommit();
      if (!$ret) {
        dbRollBack();
        foreach ($filesCreated as $f) {
          if (@unlink($STORAGE."/".$f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      } else {
        foreach ($filesRemoved as $f) {
          if (@unlink($STORAGE."/".$f) === false) $msgs[] = "Kann Datei nicht löschen: {$f}";
        }
      }
outAntragCreate:

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
 $result["forceClose"] = ($forceClose !== false);
 $result["_REQUEST"] = $_REQUEST;
 $result["_FILES"] = $_FILES;

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
    $filePath = $ah["path"];

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
    require "../template/footer-print.tpl";
    exit;
}

require "../template/header.tpl";

switch($_REQUEST["tab"]) {
  case "antrag.listing":
    $tmp = dbFetchAll("antrag", [], ["type" => true, "revision" => true, "lastupdated" => false]);
    $antraege = [];
    foreach ($tmp as $t) {
      $antraege[$t["type"]][$t["revision"]][$t["id"]] = $t;
    }
    // FIXME extended permission checking
    foreach ($antraege as $type => $l0) {
      foreach ($l0 as $revision => $l1) {
        if (false === getForm($type,$revision))
          unset($antraege[$type][$revision]);
      }
    }
    require "../template/antrag.list.tpl";
  break;
  case "antrag":
    $antrag = getAntrag();
    $form = getForm($antrag["type"],$antrag["revision"]);
    if ($form === false) die("Unbekannter Formulartyp/-revision, kann nicht dargestellt werden.");

    require "../template/antrag.menu.tpl";
    require "../template/antrag.tpl";
  break;
  case "antrag.edit":
    $antrag = getAntrag();
    if ($antrag["state"] != "draft") die("Antrag ist nicht editierbar");

    $form = getForm($antrag["type"],$antrag["revision"]);
    if ($form === false) die("Unbekannter Formulartyp/-revision, kann nicht dargestellt werden.");

    require "../template/antrag.head.tpl";
    require "../template/antrag.edit.tpl";
  break;
  case "antrag.create":
    if (!isset($_REQUEST["type"]) || !isset($_REQUEST["revision"])) {
      header("Location: $URIBASE");
      exit;
    }
    $form = getForm($_REQUEST["type"], $_REQUEST["revision"]);
    if ($form === false) die("Unbekannter Formulartyp oder keine Berechtigung");

    require "../template/antrag.head.tpl";
    require "../template/antrag.create.tpl";
  break;
#  case "antrag.submit":
#    $antrag = getAntrag();
#    require "../template/antrag.submit.tpl";
#  break;
#  case "antrag.submitted":
#    require "../template/antrag.submitted.tpl";
#  break;
  default:
    echo "invalid tab name: ".htmlspecialchars($_REQUEST["tab"]);
}

require "../template/footer.tpl";

exit;

