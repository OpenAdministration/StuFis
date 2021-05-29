<?php


namespace framework\render;


class DevController extends Renderer
{
    public function __construct(array $routeInfo)
    {
        $this->routeInfo = $routeInfo;
    }

    public function actionShowSession() : void
    {
        echo "<pre>" . var_export($_SESSION,true) . "</pre>";
    }
}