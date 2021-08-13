<?php


namespace framework\render;


use framework\auth\AuthHandler;

class DevController extends Renderer
{
    public function __construct(array $routeInfo)
    {
        $this->routeInfo = $routeInfo;
    }

    public function actionSession() : void
    {
        echo "<pre>" . var_export($_SESSION,true) . "</pre>";
    }

    public function actionAttributes() : void
    {
        echo "<pre>" . var_export(AuthHandler::getInstance()->getAttributes(),true) . "</pre>";
    }

    public function actionLogout(){
        AuthHandler::getInstance()->logout();
    }

    public function actionPhpInfo(){
        echo "hi";
        phpinfo();
    }

}