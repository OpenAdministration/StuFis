<?php


namespace framework\render\html;


class HtmlButton extends Html
{
    /**
     * @param string $type button|submit|reset are valid
     * @return self
     */
    public static function make(string $type = 'button') : self
    {
        return new self(['type' => $type], ['btn']);
    }

    public function __construct(array $attributes, array $classes = [], array $dataAttributes = [])
    {
        parent::__construct('button', $attributes, $classes, $dataAttributes);
    }

    /**
     * @param string $styleName default|primary|success|info|warning|danger|link
     */
    public function style(string $styleName) : self
    {
        return $this->addClasses(["btn-$styleName"]);
    }

    public function asLink($href) : self
    {
        $this->tag = 'a';
        $this->attr('href', $href);
        return $this;
    }


}