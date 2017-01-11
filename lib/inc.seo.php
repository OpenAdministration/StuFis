<?php

#header("Content-Type: text/plain");
#var_dump($_SERVER);exit;

if (!empty($_SERVER["PATH_INFO"])) {
  $p = explode("/", trim($_SERVER["PATH_INFO"],"/"));
  if (!empty($p[0])) {
    $_REQUEST["updatetoken"] = $p[0];
    if (!isset($_REQUEST["tab"]) && (count($p) > 1)) {
      $_REQUEST["tab"] = "antrag.".$p[1];
    } elseif (!isset($_REQUEST["tab"])) {
      $_REQUEST["tab"] = "antrag";
    }
    $_REQUEST["__args"] = [];
    for ($i = 2; $i < count($p); $i++) {
      $_REQUEST["__args"][] = $p[$i];
    }
  }
}

