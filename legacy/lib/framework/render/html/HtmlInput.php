<?php

namespace framework\render\html;

use JetBrains\PhpStorm\Pure;

class HtmlInput extends AbstractHtmlTag
{
    public const TYPE_STRING = 'string';

    public const TYPE_PASSWORD = 'password';

    public const TYPE_HIDDEN = 'hidden';

    /** @var Html */
    protected $label;

    public function value($value): self
    {
        $this->attributes['value'] = $value;

        return $this;
    }

    public function name($name): self
    {
        $this->attributes['name'] = $name;

        return $this;
    }

    public function placeholder($value): self
    {
        $this->attributes['placeholder'] = $value;

        return $this;
    }

    public function label($value): self
    {
        $id = $this->generateId();
        $this->label = Html::tag('label')
            ->body($value)
            ->attr('for', $id);

        return $this;
    }

    public static function make(string $type = 'text'): self
    {
        $class = [];
        $wrapStack = [];
        if ($type !== 'hidden') {
            $class = ['form-control'];
            $wrapStack[] = Html::tag('div')->addClasses(['form-group']);
        }

        return new self(
            attributes: ['type' => $type],
            classes: $class,
            wrapStack: $wrapStack
        );
    }

    #[Pure]
    protected function __construct(string $tag = 'input', array $attributes = [], array $classes = [], array $dataAttributes = [], array $wrapStack = [])
    {
        $this->label = '';
        parent::__construct($tag, $attributes, $classes, $dataAttributes, $wrapStack);
    }

    public function begin(): string
    {
        return $this->beginWrap().$this->label."<$this->tag ".$this->implodeAttrClassesData().'>';
    }

    public function end(): string
    {
        // no closing tag
        return $this->wrapEnd();
    }
}
