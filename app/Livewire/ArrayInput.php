<?php

namespace App\Livewire;

use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\View\View;
use Livewire\Component;

class ArrayInput extends Component
{
    public array $values;

    public $name;

    public $label;

    public function render(): Factory|Application|\Illuminate\Contracts\View\View|View|\Illuminate\Contracts\Foundation\Application
    {
        return view('livewire.array-input');
    }

    public function addValue(): void
    {
        $this->values[] = '';
    }

    public function removeValue($index): void
    {
        unset($this->values[$index]);
        $this->values = array_values($this->values);
    }
}
