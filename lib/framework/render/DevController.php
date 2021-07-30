<?php


namespace framework\render;


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
        echo "<pre>" . var_export((AUTH_HANDLER)::getInstance()->getAttributes(),true) . "</pre>";
    }

    public function actionLogout(){
        (AUTH_HANDLER)::getInstance()->logout();
    }

}