<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 28.04.18
 * Time: 22:19
 */
//auth ----------------------------------
include "../lib/inc.all.php";
// routing ------------------------------
$router = new Router();
$routeInfo = $router->route();

if (!SAML){
    require_once(SYSBASE . "/lib/AuthDummyHandler.php");
    define('AUTH_HANDLER', 'AuthDummyHandler');
    (AUTH_HANDLER)::getInstance()->requireAuth();
}else if (isset($routeInfo['auth']) && ($routeInfo['auth'] == 'Basic' || $routeInfo['auth'] == 'basic')){
    define('AUTH_HANDLER', 'AuthBasicHandler');
    require_once SYSBASE . '/lib/class.AuthBasicHandler.php';
    (AUTH_HANDLER)::getInstance()->requireAuth();
    (AUTH_HANDLER)::getInstance()->requireGroup('basic');
    if (!isset($routeInfo['groups'])){
        $routeInfo['action'] = '403';
        $routeInfo['controller'] = 'error';
    }
}else{
    define('AUTH_HANDLER', 'AuthSamlHandler');
    require_once SYSBASE . '/lib/AuthSamlHandler.php';
    (AUTH_HANDLER)::getInstance()->requireAuth();
}
if (isset($routeInfo['groups']) && !(AUTH_HANDLER)::getInstance()->hasGroup($routeInfo['groups'])){
    $routeInfo['action'] = '403';
    $routeInfo['controller'] = 'error';
}

if (MAIL_INSTALL){
    require_once SYSBASE . '/lib/mail_init.php';
}

//TODO ACL on route ? ----------------------------
$idebug = false;

// handle route -------------------------
//print_r($routeInfo);
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
    case "hhp":
        $hhpHandler = new HHPHandler($routeInfo);
        $htmlRenderer->appendRendererContent($hhpHandler);
        $htmlRenderer->render();
        break;
	case "booking":
		$bHandler = new BookingHandler($routeInfo);
		$htmlRenderer->appendRendererContent($bHandler);
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
        $htmlRenderer->render();
        break;
}


