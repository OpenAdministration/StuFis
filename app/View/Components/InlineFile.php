<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Component;

class InlineFile extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public string $mimeType, public string $file64)
    {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.inlineFile');
    }

}
