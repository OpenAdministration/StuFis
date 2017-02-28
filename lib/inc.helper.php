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

function getAntragDisplayTitle(&$antrag, &$revConfig) {
  static $cache = false;
  if ($cache === false) $cache = [];
  if (isset($antrag["id"]) && isset($cache[$antrag["id"]]))
    return $cache[$antrag["id"]];
  $renderOk = true;

  $caption = [ ];
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
        $ret = renderForm($form, ["_values" => $antrag, "render" => ["no-form", "no-form-markup"]] );
        if ($ret === false) $renderOk = false;
        $val = ob_get_contents();
        ob_end_clean();
        $caption[] = $val;
      }
    }
  }
  if (isset($revConfig["caption"]) > 0) {
    $caption[] = $revConfig["caption"];
  }
  if (trim(strip_tags(implode(" ", $caption))) == "")
    array_unshift($caption, htmlspecialchars($antrag["token"]));

  if (isset($antrag["id"]) && $renderOk)
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

