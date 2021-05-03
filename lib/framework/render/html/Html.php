<?php

namespace framework\render\html;

class Html
{
    protected $tag;

    protected $attributes;
    protected $dataAttributes;
    protected $classes;

    /**
     * @var $body string
     */
    protected $body;

    public static function a(string $href) : self
    {
        return new Html('a', ['href' => $href]);
    }

    protected function __construct(string $tag, array $attributes, array $classes = [], array $dataAttributes = [])
    {
        $this->tag = $tag;
        $this->attributes = $attributes;
        $this->classes = $classes;
        $this->dataAttributes = $dataAttributes;
    }

    /**
     * @param $content string|object object has to be stringable
     */
    public function body($content): self
    {
        if(is_object($content)){
            /** @var $content Html */
            $content = (string) $content;
        }
        $this->body = $content;
        return $this;
    }

    public function id(string $id) : self
    {
        return $this->attr('id', $id);
    }

    public function setClasses(array $classes) : self
    {
        $this->classes = $classes;
        return $this;
    }

    public function addClasses(array $classes) : self
    {
        $this->classes = array_merge($this->classes, $classes);
        return $this;
    }

    public function attr(string $name,string $val) : self{
        $this->attributes[$name] = $val;
        return $this;
    }

    public function dataAttr(string $name,string $val) : self{
        $this->dataAttributes[$name] = $val;
        return $this;
    }

    public function begin() : string {
        return "<$this->tag " . $this->implodeAttrClassesData() . ">";
    }

    private function implodeAttrClassesData() : string
    {
        $ret = [array_reduce($this->classes, static function ($val1, $val2){
            return $val1 . ' ' . $val2;
        }, "class='") . "'"];
        foreach ($this->attributes as $name => $value){
            $name = htmlentities($name);
            $value = htmlentities($value);
            $ret[] = "$name='$value'";
        }
        foreach ($this->dataAttributes as $name => $value){
            $name = htmlentities($name);
            $value = htmlentities($value);
            $ret[] = "data-$name='$value'";
        }
        return implode(' ', $ret);
    }

    public function end() : string {
        return "</{$this->tag}>";
    }

    public function __toString() : string
    {
        $this->body = $this->body ?? '';
        return $this->begin() . $this->body . $this->end();
    }
}