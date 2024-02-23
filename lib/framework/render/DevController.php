<?php

namespace framework\render;

use booking\konto\tan\FlickerGenerator;
use framework\auth\AuthHandler;
use const http\Client\Curl\AUTH_ANY;

class DevController extends Renderer
{
    public function __construct(array $routeInfo)
    {
        $this->routeInfo = $routeInfo;
        parent::__construct($routeInfo);
    }

    public function actionKillSession(): void
    {
        session_destroy();
        session_unset();
        exit('Session Killed');
    }

    public function actionSession(): void
    {
        echo '<pre>' . var_export($_SESSION, true) . '</pre>';
    }

    public function actionGroups(): void
    {
        echo '<pre>';
        echo "\n---RAW GROUPS---\n";
        echo var_export(AuthHandler::getInstance()->getRawUserGroups(), true);
        echo "\n---MAPPED GROUPS---\n";
        echo var_export(AuthHandler::getInstance()->getUserGroups(), true);
        echo "\n---PERMISSIONS---\n";
        foreach (['login', 'ref-finanzen', 'ref-finanzen-hv', 'ref-finanzen-kv', 'ref-finanzen-belege'] as $group){
            echo "$group: " . var_export(AuthHandler::getInstance()->hasGroup($group), true) . "\n";
        }
        echo "admin: " . var_export(AuthHandler::getInstance()->isAdmin(), true) . "\n";
        echo '</pre>';
    }

    public function actionGremien(): void
    {
        echo '<pre>' . var_export(AuthHandler::getInstance()->getUserGremien(), true) . '</pre>';
    }

    public function actionAttributes(): void
    {
        echo '<pre>' . var_export(AuthHandler::getInstance()->debugInfo(), true) . '</pre>';
    }

    public function actionLogout()
    {
        AuthHandler::getInstance()->logout();
    }

    public function actionFlicker()
    {
        echo '<pre>';
        // var_dump($hexSol);
        // $challenge = "024 8A 01 2043801998 08 12345678";
        // $challenge = "0388A01239230520422DE12500105170648489890"; // <- works
        // $challenge = "038 8A 01 2392307899 22 DE12500105170648489890"; // <- works
        $challenge = '038 8A 01 2392302069 22 DE12500105170648489890'; // <- works
        $p = new FlickerGenerator($challenge);
        // var_dump($p);
        // var_dump(str_replace(' ', '', $hexSol));
        echo '</pre>';
        $svg = $p->getSVG(10, 300);
        echo PHP_EOL . $svg;
    }
}
