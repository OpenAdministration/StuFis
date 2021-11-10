<?php

namespace framework\render\html;

class HtmlButton extends AbstractHtmlTag
{
    /**
     * @param string $type button|submit|reset are valid
     */
    public static function make(string $type = 'submit'): self
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
    public function style(string $styleName): self
    {
        return $this->addClasses(["btn-$styleName"]);
    }

    public function asLink($href): self
    {
        $this->tag = 'a';
        $this->attr('type', 'button');
        $this->attr('href', $href);
        return $this;
    }

    /**
     * @param $fa string|FA faName in @see FA::make or an FA object
     * @return HtmlButton
     */
    public function icon($fa): self
    {
        if (!($fa instanceof FA)) {
            $fa = FA::make($fa);
        }
        $this->bodyPrefix($fa . ' ');
        return $this;
    }
}
