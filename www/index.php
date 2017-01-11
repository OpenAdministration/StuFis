<?php

global $attributes, $logoutUrl, $ADMINGROUP, $nonce, $URIBASE, $antrag, $STORAGE, $formconfig;
ob_start('ob_gzhandler');

require_once "../lib/inc.all.php";
requireAuth();
#requireGroup($ADMINGROUP);

$formconfig = $formulare["projekt-intern"]["v1"];

if (isset($_POST["action"])) {
 $msgs = Array();
 $ret = false;
 $target = false;

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
#    case "antrag.create":
#      $_REQUEST["unirzusername"] = getUsername();
#      $_REQUEST["state"] = "WAIT_STUDENT";
#      $_REQUEST["updatetoken"] = $token = substr(sha1(sha1(mt_rand())),0,16);
#      $_REQUEST["createdat"] = date("Y-m-d H:i:s");
#      $_REQUEST["lastupdated"] = date("Y-m-d H:i:s");
#      $ret = dbInsert("antrag", $_REQUEST);
##      $msgs[] = "Umfage wurde erstellt.";
#      if ($ret !== false) {
#        $target = str_replace("//","/",$URIBASE."/").rawurlencode($token)."/anhang";
#        $antrag_id = (int) $ret;
#      }
#      break;
#    case "antrag.update":
#      $antrag = getAntrag();
#
#      $q = $_REQUEST;
#      $f = ["id" => $antrag["id"]];
#      unset($q["id"]);
#      $ret = dbUpdate("antrag", $f, $q);
#
#      $target = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["updatetoken"]);
#
#      break;
#    case "antrag.submit":
#      $antrag = getAntrag();
#      $ahs = dbFetchAll("anhang",["antrag_id" => $antrag["id"]]);
#      if ($ahs === false) die("Reading anhang failed.");
#
#      $missinganhang = array_map("strtolower",$NEEDANHANG[$antrag["reason"]]);
#      foreach ($ahs as $ah) {
#        if ($ah["state"] != "active")
#          continue;
#        if(($key = array_search(strtolower($ah["type"]), $missinganhang)) !== false) {
#          unset($missinganhang[$key]);
#        }
#      }
#
#      if (count($missinganhang) == 0) {
#        $ret = dbUpdate("antrag",["id" => $antrag["id"]], ["state" => "WAIT_STURA"]);
#      } else {
#        $ret = false;
#        $msgs[] = "Der Antrag kann nicht abgesendet werden, da noch Nachweise fehlen.";
#        $target = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["updatetoken"])."/anhang";
#      }
#
#      if ($ret) {
#
#        // build and send email
#        $msg = "Hallo {$antrag["fullname"]},
#
#anbei erhälst du deinen beim StuRa eingereichten Antrag auf Erstattung des Semesterbeitrages in Kopie.
#
#Mit freundlichen Grüßen,
#
#Dein StuRa";
#        sendAntrag($antrag, $ahs, $msg);
#
#        $target = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["updatetoken"])."/submitted";
#      }
#
#      break;
#    case "anhang.create":
#      $antrag = getAntrag();
#      $ret = true;
#      for ($i = 0; $i < (int) $_REQUEST["_numAnhang"]; $i++) {
#        if (!isset($_FILES["datei_$i"])) continue;
#        if (!is_uploaded_file($_FILES["datei_$i"]['tmp_name'])) die("Invalid upload");
#        $f = [];
#        $f["md5sum"] = md5_file($_FILES["datei_$i"]['tmp_name']);
#        $f["mimetype"] = $_FILES["datei_$i"]["type"];
#        $f["size"] = $_FILES["datei_$i"]["size"];
#        $f["name"] = $_FILES["datei_$i"]['name'];
#        $f["state"] = "active";
#        $f["description"] = $_REQUEST["description_$i"];
#        $f["type"] = $_REQUEST["type_$i"];
#        $f["antrag_id"] = $_REQUEST["antrag_id"];
#
#        $path = $STORAGE."/".$antrag["id"]."/".uniqid().".".pathinfo($_FILES["datei_$i"]["name"],PATHINFO_EXTENSION);
#        if (!is_dir(dirname($path)))
#          mkdir(dirname($path),0777,true);
#        $f["path"] = $path;
#        $ret = $ret && move_uploaded_file($_FILES["datei_$i"]['tmp_name'],$path);
#
#        $ret = $ret && dbInsert("anhang", $f);
#      }
#
#      if ($ret !== false) {
#        $target = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["updatetoken"])."/anhang";
#      }
#
#      break;
#    case "anhang.disable":
#    case "anhang.enable":
#      $antrag = getAntrag();
#      if (!isset($_REQUEST["anhang_id"])) die("Missing anhang_id");
#
#      $enabled = $_POST["action"] == "anhang.enable";
#
#      $f = ["antrag_id" => $antrag["id"], "id" => $_REQUEST["anhang_id"]];
#      $ret = dbUpdate("anhang", $f, ["state" => ($enabled ? "active" : "revoked")]);
#
#      if ($ret !== false) {
#        $target = str_replace("//","/",$URIBASE."/").rawurlencode($antrag["updatetoken"])."/anhang";
#      }
#
#      break;
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

 header("Content-Type: text/json; charset=UTF-8");
 echo json_encode($result);
 exit;
}

require "../template/header.tpl";

if (!isset($_REQUEST["tab"])) {
  $_REQUEST["tab"] = "antrag.create";
}

switch($_REQUEST["tab"]) {
#  case "antrag":
#    $antrag = getAntrag();
#    require "../template/antrag.tpl";
#  break;
  case "antrag.create":
    require "../template/antrag.create.tpl";
  break;
#  case "antrag.submit":
#    $antrag = getAntrag();
#    require "../template/antrag.submit.tpl";
#  break;
#  case "antrag.submitted":
#    require "../template/antrag.submitted.tpl";
#  break;
#  case "antrag.anhang":
#    $antrag = getAntrag();
#    if (count($_REQUEST["__args"]) > 0) {
#      $f = ["antrag_id" => $antrag["id"], "id" => $_REQUEST["__args"][0]];
#      $ah = dbGet("anhang", $f);
#      if ($ah === false) die("Antrag nicht gefunden.");
#      header("Content-Type: ".$ah["mimetype"]);
#      header('Content-Disposition: attachment; filename="' . $antrag["id"]."-".$ah["id"]." ".$ah["name"]. '"');
#      header('Content-Transfer-Encoding: binary');
#      header('Accept-Ranges: bytes');
#      header('Cache-Control: private');
#      header('Pragma: private');
#      header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
#
#      $fileSize = $ah["size"];
#      $filePath = $ah["path"];
#
#      // Multipart-Download and Download Resuming Support
#      if(isset($_SERVER['HTTP_RANGE'])) {
#        list($a, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
#        list($range) = explode(',', $range, 2);
#        list($range, $rangeEnd) = explode('-', $range);
#
#        $range = intval($range);
#
#        if(!$rangeEnd) {
#          $rangeEnd = $fileSize - 1;
#        }
#        else {
#          $rangeEnd = intval($rangeEnd);
#        }
#
#        $newLength = $rangeEnd - $range + 1;
#
#        // Send Headers
#        header('HTTP/1.1 206 Partial Content');
#        header('Content-Length: ' . $newLength);
#        header("Content-Range: bytes $range-$rangeEnd/$fileSize");
#      }
#      else {
#        $range = 0;
#        $newLength = $fileSize;
#        header('Content-Length: ' . $fileSize);
#      }
#
#      // Output File
#      $chunkSize = 1 * (1024*1024);
#      $bytesSend = 0;
#
#      if($file = fopen($filePath, 'r')) {
#        if(isset($_SERVER['HTTP_RANGE'])) {
#          fseek($file, $range);
#
#          while(!feof($file) && !connection_aborted() && $bytesSend < $newLength) {
#            $buffer = fread($file, $chunkSize);
#            echo $buffer;
#            flush();
#            $bytesSend += strlen($buffer);
#          }
#
#          fclose($file);
#        }
#      }
#
#    } else {
#      require "../template/antrag.anhang.tpl";
#    }
#  break;
  default:
    die("invalid tab name: ".htmlspecialchars($_REQUEST["tab"]));
}

require "../template/footer.tpl";

exit;

