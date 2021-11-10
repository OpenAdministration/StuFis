<?php

namespace framework\render\html;

class HtmlForm extends AbstractHtmlTag
{
    public static function make(string $method = 'POST', bool $isAjax = true, bool $nonce = true): self
    {
        $ajax = $isAjax ? 'ajax-form' : '';
        return new self(['method' => $method], [$ajax], [], $nonce);
    }

    protected function __construct(array $attributes = [], array $classes = [], array $dataAttributes = [], bool $nonce = true)
    {
        parent::__construct('form', $attributes, $classes, $dataAttributes);
        if ($nonce) {
            $this->nonce();
        }
    }

    private function nonce(): void
    {
        $this->hiddenInput('nonce', $GLOBALS['nonce']);
        $this->hiddenInput('nononce', $GLOBALS['nonce']);
    }

    public function urlTarget(string $url): self
    {
        $this->attr('action', $url);
        return $this;
    }

    public function hiddenInput(string $name, string $value): self
    {
        $this->appendBodyPrefix(
            HtmlInput::make('hidden')
                ->name($name)
                ->value($value)
        );
        return $this;
    }

    public function addHtmlEntity(AbstractHtmlTag $htmlTag): self
    {
        $this->appendBody($htmlTag, false);
        return $this;
    }

    public function addSubmitButton(string $text = 'Absenden')
    {
        $btn = HtmlButton::make('submit')
            ->style('primary')
            ->body($text);
        $this->appendBody($btn, false);
        return $this;
    }
}
