<?php


namespace framework\render\html;


use framework\ArrayHelper;

abstract class AbstractHtmlTag
{
    protected string $tag;
    protected string $id;

    protected array $attributes;
    protected array $dataAttributes;
    protected array $classes;
    protected array $unaryAttributes;

    protected $bodyPrefix;
    protected $body;
    protected $bodySuffix;

    /**
     * @var AbstractHtmlTag[] $wrapStack
     */
    protected array $wrapStack;

    /**
     * AbstractHtmlTag constructor.
     * @param string $tag
     * @param array $attributes
     * @param array $classes
     * @param array $dataAttributes
     * @param array $wrapStack
     */
    protected function __construct(string $tag, array $attributes = [], array $classes = [], array $dataAttributes = [], array $wrapStack = [])
    {
        $this->tag = $tag;
        $this->attributes = $attributes;
        $this->classes = $classes;
        $this->dataAttributes = $dataAttributes;
        $this->unaryAttributes = [];
        $this->body = '';
        $this->bodyPrefix = '';
        $this->bodySuffix = '';
        $this->wrapStack = $wrapStack;
    }

    /**
     * @param $content object|string object has to be stringable
     */
    public function body(object|string $content, bool $escape = true): self
    {
        if(is_object($content)){
            /** @var $content Html */
            $content = (string) $content;
        }
        if($escape){
            $this->body = htmlentities($content);
        }else{
            $this->body = $content;
        }
        return $this;
    }

    public function appendBody(AbstractHtmlTag|string $content, bool $escape = true) : self
    {
        if($content instanceof self){
            $content = $content->__toString();
        }
        if($escape){
            $this->body .= htmlentities($content);
        }else{
            $this->body .= $content;
        }
        return $this;
    }

    protected function generateId() : string
    {
        if(isset($this->id)){
            return $this->id;
        }
        if(isset($this->attributes['name'])){
            $uniqueId = $this->attributes['name'] . '-' . uniqid('', true);
        }else{
            $uniqueId = uniqid('', true);
        }
        $this->id($uniqueId);
        return $this->id;
    }

    public function id(string $id) : self
    {
        $this->id = $id;
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

    public function attr(string $name,string $val) : self
    {
        $this->attributes[$name] = $val;
        return $this;
    }

    public function dataAttr(string $name,string $val) : self
    {
        $this->dataAttributes[$name] = $val;
        return $this;
    }

    public function begin() : string
    {
        return $this->beginWrap() . "<$this->tag " . $this->implodeAttrClassesData() . ">";
    }

    protected function beginWrap() : string
    {
        $wrap = '';
        foreach ($this->wrapStack as $wrapper){
            $wrap .= $wrapper->begin();
        }
        return $wrap;
    }

    protected function implodeAttrClassesData() : string
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
        $ret[] = implode(' ', $this->unaryAttributes);
        return implode(' ', $ret);
    }

    public function end() : string {
        return "</{$this->tag}>" . $this->wrapEnd();
    }

    protected function wrapEnd() : string {
        $wrap = '';
        foreach ($this->wrapStack as $wrapper){
            $wrap = $wrapper->end() . $wrap;
        }
        return $wrap;
    }

    public function readOnly() : self
    {
        $this->unaryAttributes[] = 'readonly';
        $this->attr('onclick', "='return false;'");
        return $this;
    }

    public function disable(bool $disable = true) : self {
        if($disable){
            $this->unaryAttributes[] = 'disabled';
        }else{
            $this->unaryAttributes = array_diff($this->unaryAttributes, ['disabled']);
        }
        return $this;
    }

    public function required() : self {
        $this->unaryAttributes[] = 'require';
        return $this;
    }

    public function title(string $title) : self
    {
        $this->attributes['title'] = $title;
        return $this;
    }

    protected function appendBodyPrefix(string $prefix, bool $escape = false) : self
    {
        if($escape){
            $prefix = htmlentities($prefix);
        }
        $this->bodyPrefix .= $prefix;
        return $this;
    }

    /**
     * @param string $prefix
     * @param bool $escape
     * @return $this
     */
    protected function bodyPrefix(string $prefix, bool $escape = false) : self
    {
        if($escape){
            $prefix = htmlentities($prefix);
        }
        $this->bodyPrefix = $prefix;
        return $this;
    }

    protected function bodySuffix(string $suffix, bool $escape = false) : self
    {
        if($escape){
            $suffix = htmlentities($suffix);
        }
        $this->bodySuffix = $suffix;
        return $this;
    }

    protected function appendBodySuffix(string $suffix, bool $escape = false) : self
    {
        if($escape){
            $suffix = htmlentities($suffix);
        }
        $this->bodySuffix .= $suffix;
        return $this;
    }

    public function __toString() : string
    {
        $pre = $this->bodyPrefix ?? '';
        $text = $this->body ?? '';
        $suf = $this->bodySuffix ?? '';
        return  $this->begin() . $pre . $text . $suf . $this->end();
    }

}