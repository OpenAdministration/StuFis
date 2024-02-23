<?php
// auth ----------------------------------
use booking\BookingHandler;
use booking\HHPHandler;
use booking\konto\FintsController;
use forms\projekte\auslagen\AuslagenHandler2;
use forms\projekte\ProjektHandler;
use forms\RestHandler;
use framework\auth\AuthHandler;
use framework\auth\AuthSamlHandler;
use framework\file\FileController;
use framework\render\ErrorHandler;
use framework\render\HTMLPageRenderer;
use framework\render\MenuRenderer;
use framework\render\Renderer;
use framework\Router;

define('SYSBASE', dirname(__DIR__));
require_once SYSBASE . '/vendor/autoload.php';
require_once SYSBASE . '/lib/inc.all.php';


// routing ------------------------------
$router = new Router();
$routeInfo = $router->route();
// handle route -------------------------
// print_r($routeInfo);
// print_r($_POST);
$htmlRenderer = new HTMLPageRenderer($routeInfo);
$controllerName = $routeInfo['controller'];

if($controllerName !== 'saml'){
    // lock out everyone with wrong permissions, if not during the auth process
    AuthHandler::getInstance()->requireGroup('login');
}
switch ($controllerName) {
    case 'menu':
        $menuRenderer = new MenuRenderer($routeInfo);
        $htmlRenderer->appendRendererContent($menuRenderer);
        $htmlRenderer->render();
        break;
    case 'projekt':
        $projektRenderer = new ProjektHandler($routeInfo);
        $htmlRenderer->appendRendererContent($projektRenderer);
        $htmlRenderer->render();
        break;
    case 'auslagen':
        $auslagenHandler = new AuslagenHandler2($routeInfo);
        $htmlRenderer->appendRendererContent($auslagenHandler);
        $htmlRenderer->render();
        break;
    case 'hhp':
        $hhpHandler = new HHPHandler($routeInfo);
        $htmlRenderer->appendRendererContent($hhpHandler);
        $htmlRenderer->render();
        break;
    case 'booking':
        $bHandler = new BookingHandler($routeInfo);
        $htmlRenderer->appendRendererContent($bHandler);
        $htmlRenderer->render();
    break;
    case 'fints':
        $fintsHandler = new FintsController($routeInfo);
        $htmlRenderer->appendRendererContent($fintsHandler);
        $htmlRenderer->render();
        break;
    case 'rest':
        $restHandler = new RestHandler();
        $restHandler->handlePost($routeInfo);
        break;
    case 'files':
        $fileController = new FileController();
        $fileController->handle($routeInfo);
        break;
    case 'error':
        ErrorHandler::handleErrorRoute($routeInfo);
        break;
    case 'saml':
    switch ($routeInfo['action']) {
            case 'logout':
                AuthSamlHandler::getInstance()->logout();
                HTMLPageRenderer::redirect(FULL_APP_PATH);
                break;
            case 'login':
                AuthSamlHandler::getInstance()->login();
                break;
            case 'metadata':
                header('Content-Type: text/xml');
                echo AuthSamlHandler::getInstance()->getSpMetaDataXML();
                break;
        }
        break;
    default:
        $className = '\\framework\\render\\' . ucfirst($controllerName) . 'Controller';
        if (class_exists($className)) {
            $controller = new $className($routeInfo);
            if ($controller instanceof Renderer) {
                $htmlRenderer->appendRendererContent($controller);
                $htmlRenderer->render();
                break;
            }
        }
        ErrorHandler::handleErrorRoute($routeInfo);
        break;
}
