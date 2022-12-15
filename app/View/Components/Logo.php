<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Logo extends Component
{

    public $idPrefix;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(public $col1 = "#00c9ab", public $col2 = "#007f8b")
    {
        $this->idPrefix = \Str::random(10);
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.logo');
    }
}
