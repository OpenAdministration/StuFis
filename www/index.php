<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 28.04.18
 * Time: 22:19
 */

//auth ----------------------------------
include "../lib/inc.all.php";
AuthHandler::getInstance()->requireAuth();

// routing ------------------------------
$router = new Router();
$routeInfo = $router->route();

//TODO ACL on route ? ----------------------------
$idebug = false;

// handle route -------------------------
$content = null;
$error = false;
//print_r($_POST);
$htmlRenderer = new HTMLPageRenderer($routeInfo);
switch ($routeInfo['controller']){
    case "menu":
        $menuRenderer = new MenuRenderer($routeInfo);
        $htmlRenderer->appendRendererContent($menuRenderer);
        $htmlRenderer->render();
        break;
    case "projekt":
        $projektRenderer = new ProjektHandler($routeInfo);
        $htmlRenderer->appendRendererContent($projektRenderer);
        $htmlRenderer->render();
        break;
    case "auslagen":
        $auslagenHandler = new AuslagenHandler2($routeInfo);
        $htmlRenderer->appendRendererContent($auslagenHandler);
        $htmlRenderer->render();
        break;
    case "rest":
        $restHandler = new RestHandler();
        $restHandler->handlePost($routeInfo);
        break;
    case "files":
        $fileController = new FileController();
        $fileController->handle($routeInfo);
        break;
    case 'error':
    default:
        $errorHdl = new ErrorHandler($routeInfo);
        $htmlRenderer->appendRendererContent($errorHdl);
        break;
}


