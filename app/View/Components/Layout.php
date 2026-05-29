<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Layout extends Component
{
    /**
     * Get the view / contents that represent the component.
     *
     * @return View|\Closure|string
     */
    #[\Override]
    public function render()
    {
        return view('components.layouts.index');
    }
}
