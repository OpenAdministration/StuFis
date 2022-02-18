<?php

namespace framework\render;

use booking\konto\tan\FlickerGenerator;
use framework\auth\AuthHandler;

class DevController extends Renderer
{
    public function __construct(array $routeInfo)
    {
        $this->routeInfo = $routeInfo;
        parent::__construct($routeInfo);
    }

    public function actionSession(): void
    {
        echo '<pre>' . var_export($_SESSION, true) . '</pre>';
    }

    public function actionGroups(): void
    {
        echo '<pre>' . var_export(AuthHandler::getInstance()->getUserGroups(), true) . '</pre>';
    }

    public function actionGremien() : void
    {
        echo '<pre>' . var_export(AuthHandler::getInstance()->getUserGroups(), true) . '</pre>';
    }

    public function actionLogout()
    {
        AuthHandler::getInstance()->logout();
    }

    public function actionFlicker()
    {
        echo '<pre>';
        //var_dump($hexSol);
        // $challenge = "024 8A 01 2043801998 08 12345678";
        //$challenge = "0388A01239230520422DE12500105170648489890"; // <- works
        //$challenge = "038 8A 01 2392307899 22 DE12500105170648489890"; // <- works
        $challenge = '038 8A 01 2392302069 22 DE12500105170648489890'; // <- works
        $p = new FlickerGenerator($challenge);
        //var_dump($p);
        //var_dump(str_replace(' ', '', $hexSol));
        echo '</pre>';
        $svg = $p->getSVG(10, 300);
        echo PHP_EOL . $svg;
    }
}
