<?php

/* index_old.php/<token> -> tab=antrag, token=<token> */
/* index_old.php/<token>/xyz -> tab=antrag.xyz, token=<token> */

if (!empty($_SERVER["PATH_INFO"])){
    $p = explode("/", trim($_SERVER["PATH_INFO"], "/"));
    if (!empty($p[0])){
        $_REQUEST["token"] = $p[0];
        if (!isset($_REQUEST["tab"]) && (count($p) > 1)){
            $_REQUEST["tab"] = "antrag." . $p[1];
        }else if (!isset($_REQUEST["tab"])){
            $_REQUEST["tab"] = "antrag";
        }
        $_REQUEST["__args"] = [];
        for ($i = 2; $i < count($p); $i++){
            $_REQUEST["__args"][] = $p[$i];
        }
    }
}

