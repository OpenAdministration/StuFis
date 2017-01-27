<?php

function getAntrag() {
  $antrag = dbGet("antrag", ["token" => $_REQUEST["token"]]);
  if ($antrag === false) die("Unknown antrag.");
  $inhalt = dbFetchAll("inhalt", ["antrag_id" => $antrag["id"]]);
  $antrag["_inhalt"] = $inhalt;

  $form = getForm($antrag["type"], $antrag["revision"]);
  $readPermitted = hasPermission($form, $antrag, "canRead");
  if (!$readPermitted)
		die("Permission denied");

  $anhang = dbFetchAll("anhang", ["antrag_id" => $antrag["id"]]);
  $antrag["_anhang"] = $anhang;

  return $antrag;
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

