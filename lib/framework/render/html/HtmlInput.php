<?php


namespace framework\render\html;


class HtmlInput extends AbstractHtmlTag
{
    /** @var Html */
    protected $label;

    public function value($value) : self
    {
        $this->attributes['value'] = $value;
        return $this;
    }

    public function name($name) : self
    {
        $this->attributes['name'] = $name;
        return $this;
    }

    public function placeholder($value) : self
    {
        $this->attributes['placeholder'] = $value;
        return $this;
    }

    public function label($value) : self
    {
        $id = $this->generateId();
        $this->label = Html::tag('label')
            ->body($value)
            ->attr('for', $id);
        return $this;
    }

    public static function make(string $type) : self
    {
        $class = [];
        $wrapStack = [];
        if($type !== 'hidden'){
            $class = ['form-control'];
            $wrapStack[] = Html::tag('div')->addClasses(['form-group']);
        }
        return new self(['type' => $type], $class, [], $wrapStack);
    }

    protected function __construct(array $attributes = [], array $classes = [], array $dataAttributes = [], array $wrapStack = [])
    {
        $this->label = '';
        parent::__construct('input', $attributes, $classes, $dataAttributes, $wrapStack);
    }

    public function __toString() : string
    {
        return $this->beginWrap() . $this->label . $this->begin() . $this->wrapEnd();
    }
}