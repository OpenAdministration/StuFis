<?php

namespace App\Livewire\Budgetplan;

use Livewire\Component;

class Row extends Component
{
    public array $values = [['name' => 'Hornorare', ]];
    public $topic;

    public $position;

    public function render()
    {
        return view('livewire.budgetplan.row');
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
