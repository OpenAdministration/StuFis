<?php

function getAntrag() {
  $antrag = dbGet("antrag", ["updatetoken" => $_REQUEST["updatetoken"]]);
  if ($antrag === false) die("Unknown antrag.");
  if ($antrag["unirzusername"] != getUsername() && !hasGroup("admin"))
		die("Permission denied");
  if (strtolower($antrag["state"]) != "wait_student")
		die("Permission denied - Antrag wird bereits bearbeitet.");
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

