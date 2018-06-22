<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 28.04.18
 * Time: 22:19
 */

//show errors ---------------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", dirname(__FILE__, 2) . "/logs/error.log");
//auth ----------------------------------
include "../lib/inc.all.php";
AuthHandler::getInstance()->requireAuth();

// routing ------------------------------
include_once dirname(__FILE__, 2) . "/lib/class.Router.php";
include_once dirname(__FILE__, 2) . "/lib/class.ErrorHandler.php";
$router = new Router();
$routeInfo = $router->route();

//TODO ACL on route ? ----------------------------
$idebug = false;

//#######################################################
//backport ----------------------------------------------
if ($routeInfo['controller'] === 'menu'
    || $routeInfo['controller'] === 'projekt'
    || $routeInfo['controller'] === 'auslagen'){
    $type = $routeInfo['controller'];
    $subtype = "";
    $args = [];
    if (trim($routeInfo['path'], '/') != ''){
        $p = explode("/", trim(strtolower($routeInfo['path']), "/"));
        if (!empty($p[0])){
            $type = array_shift($p);
            $args = $p;
        }else{
            $args = ["create"];
        }
    }else{
        $type = "menu";
    }
    $type = $routeInfo['controller'];
}
//#######################################################

// handle route -------------------------
$content = null;
$error = false;
$htmlRenderer = new HTMLPageRenderer();
switch ($routeInfo['controller']){
    case "menu":
        $menuRenderer = new MenuRenderer();
        $htmlRenderer->appendRendererContent($menuRenderer, $args);
        break;
    case "projekt":
        $projektRenderer = new ProjektHandler($args);
        $htmlRenderer->appendRendererContent($projektRenderer);
        break;
    case "auslagen":
        ob_start();
        $auslagenHandler = new AuslagenHandler2($routeInfo);
        $ret = $auslagenHandler->render();
        $content = ob_get_clean();
        if (is_numeric($ret) && $ret < 0) $error = true;
        break;
    case "rest":
        include_once dirname(__FILE__, 2) . '/lib/class.RestHandler.php';
        $restHandler = new RestHandler();
        $restHandler->handlePost($routeInfo);
        break;
    case "files":
        include_once dirname(__FILE__, 2) . '/lib/class.FileController.php';
        $fileController = new FileController();
        $fileController->handle($routeInfo);
        break;
    case 'error':
    default:
        $errorHdl = new ErrorHandler($routeInfo);
        $errorHdl->render();
        break;
}

//header --------------------------------
if (!$error && $content !== null
    && $_SERVER['REQUEST_METHOD'] != 'POST'
    && $routeInfo['controller'] != 'error'){
    include dirname(__FILE__, 2) . "/template/header.tpl";
}

//content -------------------------------
if ($content !== null){
    if ($idebug){
        $i = [
            'type' => $type,
            'subtype' => $subtype,
            'args' => $args,
            'p' => isset($p) ? $p : null,
        ];
        echo '<div class="main container col-xs-12 col-md-10">';
        echo '<div class="info">RouteInfo</div>';
        echo '<pre>';
        var_export($routeInfo);
        echo '</pre>';
        echo '<div class="alert alert-danger">ii - backport</div>';
        echo '<pre>';
        var_export($i);
        echo '</pre>';
        echo '</div>';
    }
    echo $content;
}

//footer --------------------------------
if (!$error && $content !== null
    && $_SERVER['REQUEST_METHOD'] != 'POST'
    && $routeInfo['controller'] != 'error'){
    include dirname(__FILE__, 2) . "/template/footer.tpl";
}

$htmlRenderer->render();
