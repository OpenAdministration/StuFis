<?php

namespace framework\render\html;

class HtmlCard extends AbstractHtmlTag
{
    private AbstractHtmlTag $headerDiv;

    private AbstractHtmlTag $bodyDiv;

    protected function __construct(array $attributes = [], array $classes = [], array $dataAttributes = [], array $wrapStack = [])
    {
        parent::__construct('div', $attributes, $classes, $dataAttributes, $wrapStack);
        $this->headerDiv = Html::div()->addClasses(['panel-heading']);
        $this->bodyDiv = Html::div()->addClasses(['panel-body']);
    }

    public static function make(string $type = 'default'): static
    {
        $card = new self([], ['panel', "panel-$type"]);

        return $card;
    }

    public function body(object|string $content, bool $escape = true): static
    {
        $this->bodyDiv->body($content, $escape);

        return $this;
    }

    public function appendBody(object|string $content, bool $escape = true): AbstractHtmlTag
    {
        $this->bodyDiv->appendBody($content, $escape);

        return $this;
    }

    public function cardHeadline(object|string $content, bool $escape = true): static
    {
        $this->headerDiv->body($content, $escape);

        return $this;
    }

    public function __toString(): string
    {
        $header = (string) $this->headerDiv;
        $body = (string) $this->bodyDiv;

        return $this->beginWrap().$this->begin().$header.$body.$this->end().$this->wrapEnd();
    }
}
