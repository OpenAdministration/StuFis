<?php


namespace framework\render\html;


class HtmlInput extends Html
{
    public function value($value) : self
    {
        $this->attributes['value'] = $value;
        return $this;
    }

    public function name($value) : self
    {
        $this->attributes['name'] = $value;
        return $this;
    }

    public static function make(string $type) : self
    {
        return new self(['type' => $type], ['form-control']);
    }

    protected function __construct(array $attributes = [], array $classes = [], array $dataAttributes = [])
    {
        parent::__construct('input', $attributes, $classes, $dataAttributes);
    }

    public function __toString() : string
    {
        return $this->begin();
    }
}