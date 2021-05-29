<?php

namespace framework\render\html;

class Html extends AbstractHtmlTag
{
    public static function a(string $href) : self
    {
        return new self('a', ['href' => $href]);
    }

    public static function p() : self
    {
        return new self('p');
    }

    public static function headline(int $size) : self
    {
        return new self('h'. $size);
    }

    public static function tag(string $tagname) :self
    {
        return new self($tagname);
    }
}