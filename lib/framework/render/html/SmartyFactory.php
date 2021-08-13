<?php


namespace framework\render\html;

use Smarty;

class SmartyFactory
{
    public static function make() : Smarty
    {
        $smarty = new Smarty();
        $smarty->setTemplateDir(SYSBASE . "/template/view");
        $smarty->setCacheDir(SYSBASE . "/runtime/smarty-cache");
        $smarty->setCompileDir(SYSBASE . "/runtime/smarty-compile");
        return $smarty;
    }

    public static function mail() : Smarty
    {
        $smarty = new Smarty();
        $smarty->setTemplateDir(SYSBASE . "/template/mail");
        $smarty->setCacheDir(SYSBASE . "/runtime/smarty-cache");
        $smarty->setCompileDir(SYSBASE . "/runtime/smarty-compile");
        return $smarty;
    }

}