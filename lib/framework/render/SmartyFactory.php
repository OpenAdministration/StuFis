<?php


namespace framework\render;

use Smarty;

class SmartyFactory
{

    public static function make() : Smarty
    {
        $smarty = new Smarty();
        $smarty->setTemplateDir(SYSBASE . "/template/smarty");
        $smarty->setCacheDir(SYSBASE . "/runtime/smarty-cache");
        $smarty->setCompileDir(SYSBASE . "/runtime/smarty-compile");
        return $smarty;
    }

}