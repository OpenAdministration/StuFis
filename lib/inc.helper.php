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
  $comments = dbFetchAll("comments", ["antrag_id" => $antrag["id"]], [ "id" => false]);
  $antrag["_comments"] = $comments;

  return $antrag;
}

function getAntragDisplayTitle(&$antrag, &$revConfig) {
  $caption = [ htmlspecialchars($antrag["token"]) ];
  if (count($revConfig["captionField"]) > 0) {
    if (!isset($antrag["_inhalt"])) {
      $antrag["_inhalt"] = dbFetchAll("inhalt", ["antrag_id" => $antrag["id"] ]);
      $antraege[$type][$revision][$i] = $antrag;
    }
    foreach ($revConfig["captionField"] as $j => $fname) {
      $rows = getFormEntries($fname, null, $antrag["_inhalt"]);
      $row = count($rows) > 0 ? $rows[0] : false;
      if ($row !== false) {
        ob_start();
        $formlayout = [ [ "type" => $row["contenttype"], "id" => $fname ] ];
        $form = [ "layout" => $formlayout, "config" => [] ];
        renderForm($form, ["_values" => $antrag, "render" => ["no-form", "no-form-markup"]] );
        $val = ob_get_contents();
        ob_end_clean();
        $caption[$j] = $val;
      }
    }
  }
  if (trim(strip_tags(implode(" ", $caption))) == "")
    array_unshift($caption, "[ID=".htmlspecialchars($antrag["id"])."]");
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

