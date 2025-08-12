<?php

namespace App\Livewire\BudgetPlan;

use App\Models\BudgetItem;
use Livewire\Component;
use Livewire\Form;

class ItemForm extends Form
{
    public $id = 0;

    public $short_name = '';

    public $name = '';

    public int $value = 0;

    public $postion = 0;

    public $is_group = false;

    public function __construct(Component $component, $propertyName, ?BudgetItem $item = null)
    {
        parent::__construct($component, $propertyName);
        if ($item !== null) {
            $this->setItem($item);
        }
    }

    public function setItem(BudgetItem $item): void
    {
        $this->id = $item->id;
        $this->postion = $item->postion;
        $this->is_group = $item->is_group;
        $this->name = $item->name;
        $this->short_name = $item->short_name;
        $this->value = $item->value ?? 0;
    }
}
