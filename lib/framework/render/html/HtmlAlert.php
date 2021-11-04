<?php


namespace framework\render\html;


class HtmlAlert extends AbstractHtmlTag
{
    public static function make(string $btType)
    {
        return new self('div', [], ['alert', "alert-$btType"]);
    }

    public function strongMsg(string $msg) : self
    {
        $strong = Html::tag('strong')->body($msg);
        $this->bodyPrefix($strong. " ", false);
        return $this;
    }
}