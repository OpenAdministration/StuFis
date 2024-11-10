<?php

namespace framework\render\html;

class FA extends Html
{
    /**
     * @param  string  $faName  can but has not to start with 'fa-'
     */
    public static function make(string $faName): self
    {
        if (! str_starts_with($faName, 'fa-')) {
            $faName = 'fa-'.$faName;
        }

        return new self(['aria-hidden' => true], ['fa', $faName], []);
    }

    protected function __construct(array $attributes, array $classes, array $dataAttributes)
    {
        parent::__construct('i', $attributes, $classes, $dataAttributes);
    }

    public function href(string $link)
    {
        $this->wrapStack[] = Html::a($link);

        return $this;
    }

    /**
     * @param  int  $degree  90, 180 or 270  are valid
     * @return $this
     */
    public function rotate(int $degree): self
    {
        return $this->addClasses(['fa-rotate-'.$degree]);
    }

    /**
     * @param  int  $size  between 2 and 5
     * @return $this
     */
    public function size(int $size): self
    {
        return $this->addClasses(['fa-'.$size.'x']);
    }

    public function spin(): self
    {
        $this->addClasses(['icon-spin']);

        return $this;
    }
}
