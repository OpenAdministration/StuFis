<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 28.04.18
 * Time: 22:19
 */

include "../lib/inc.all.php";
AuthHandler::getInstance()->requireAuth();

$type = "menu";
$subtype = "";
$args = [];
if (!empty($_SERVER["PATH_INFO"])){
    $p = explode("/", trim($_SERVER["PATH_INFO"], "/"));
    //var_dump($p);
    if (!empty($p[0])){
        $type = array_shift($p);
        $args = $p;
    }else{
        $args = ["create"];
    }
}else{
    $type = "menu";
}


include "../template/header.tpl";

switch ($type){
    case "menu":
        $menuRenderer = new MenuRenderer();
        $menuRenderer->render($args);
        break;
    case "projekt":
        $projektRenderer = new ProjektHandler($args);
        $projektRenderer->render();
        break;
}

include "../template/footer.tpl";