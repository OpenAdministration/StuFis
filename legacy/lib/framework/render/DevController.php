<?php

namespace framework\render;

use booking\konto\tan\FlickerGenerator;
use DateTime;
use framework\auth\AuthHandler;
use framework\DBConnector;
use framework\render\html\Html;

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

    public function actionDates(): void
    {
        $from = '2020-02-01';
        $last = '2021-02-01';
        $until = '';
        $syncFrom = DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $from);
        $lastSync = DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $last);
        $syncUntil = DateTime::createFromFormat(DBConnector::SQL_DATE_FORMAT, $until);

        // set default for lastsync if unset
        if ($lastSync === false) {
            $lastSync = clone $syncFrom;
        }

        // if unset or in the future, cut it down to now - some banks do not like dates in the future
        if ($syncUntil === false || $syncUntil > date_create()) {
            $syncUntil = date_create();
        }

        // find older date
        $startDate = max($lastSync, $syncFrom);

        $this->renderList([
            'From: ' . $syncFrom->format('Y-m-d'),
            'last: ' . $lastSync->format('Y-m-d'),
            'until ' . $syncUntil->format('Y-m-d'),
            'res-start ' . $startDate->format('Y-m-d'),
            'res-end ' . $syncUntil->format('Y-m-d'),
        ]);
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
