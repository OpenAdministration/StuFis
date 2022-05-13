<?php

namespace framework\render\html;

class HtmlDropdown extends HtmlInput
{
    private string $items;

    /**
     * @param string $type has to be there bc of inheritance, otherwise it would not be there
     * @return static
     */
    public static function make(string $type = 'select'): self
    {
        $wrapStack[] = Html::tag('div')->addClasses(['form-group']);
        return new self(
            tag: 'select',
            classes: ['selectpicker', 'form-control'],
            wrapStack: $wrapStack
        );
    }

    /**
     * @param array $items value => [text =>, subtext =>, titel =>, disabled =>] (with or without keys possible)
     * @return $this
     */
    public function setItems(array $items): self
    {
        $itemString = '';
        foreach ($items as $value => $conf) {
            $text = $conf[0] ?? $conf['text'] ?? '';
            $subtext = $conf[1] ?? $conf['subtext'] ?? '';
            $titel = $conf[2] ?? $conf['titel'] ?? $text;
            $disabled = $conf[3] ?? false;
            $itemString .= Html::tag('option')
                ->dataAttr('subtext', $subtext)
                ->title($titel)
                ->disable($disabled)
                ->body($text)
                ->attr('value', $value);
        }
        $this->body($itemString, false);
        return $this;
    }

    public function style(string $btType = BT::TYPE_PRIMARY): self
    {
        $this->dataAttr('style', "btn-$btType");
        return $this;
    }

    public function liveSearch(bool $active = true): self
    {
        $this->dataAttr('live-search', $active);
        return $this;
    }

    /**
     * @param int $max 0 equals infinite
     * @return $this
     */
    public function multiSelect(int $max = 0): self
    {
        if ($max > 0) {
            $this->dataAttr('max-options', $max);
        }
        $this->unaryAttributes[] = 'multiple';
        return $this;
    }

    public function showTick(): self
    {
        $this->unaryAttributes[] = 'show-tick';
        return $this;
    }

    public function end(): string
    {
        return "</{$this->tag}>" . $this->wrapEnd();
    }
}
