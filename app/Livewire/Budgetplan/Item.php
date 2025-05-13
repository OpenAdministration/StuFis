<?php

namespace App\Livewire\Budgetplan;

use App\Models\BudgetItem;
use Flux;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class Item extends Component
{
    #[Locked]
    public $item_id;

    public $short_name;

    public $name;

    public $value;

    #[Locked]
    public $level;

    public $postion;

    public $is_group;

    public function mount(int $item_id)
    {
        $item = BudgetItem::findOrFail($item_id);

        $this->item_id = $item->id;
        $this->level = $item->level;
        $this->postion = $item->postion;
        $this->is_group = $item->is_group;
        $this->name = $item->name;
        $this->short_name = $item->short_name;
        $this->value = $item->value;
    }

    public function render()
    {
        $item = BudgetItem::findOrFail($this->item_id);

        if ($this->is_group === 0) {
            return view('livewire.budgetplan.item-budget');
        }

        $children = $item->children()->orderBy('position')->get();

        return view('livewire.budgetplan.item-group', ['item' => $item, 'children' => $children]);
    }

    public function updated($property): void
    {
        $value = $this->$property;
        $item = BudgetItem::findOrFail($this->item_id);
        $item->update([
            $property => $value,
        ]);

        if ($property === 'value') {
            $this->dispatch("group-{$item->parent_id}-value-updated");
        }

        Flux::toast('Your changes have been saved.', variant: 'success');

    }

    /**
     * Gets called if a child item changes its value, so the group value has to be re-calculated
     *
     * @return void
     */
    #[On('group-{item_id}-value-updated')]
    public function recalculateValue()
    {
        $item = BudgetItem::findOrFail($this->item_id);
        if ($item->is_group === 0) {
            return;
        }

        $this->value = $item->children()->sum('value');
        $item->update(['value' => $this->value]);

        if ($item->parent_id) {
            $this->dispatch("group-{$item->parent_id}-value-updated");
        }
    }

    public function addBudget(): void
    {
        $parent = BudgetItem::findOrFail($this->item_id);
        $pos = $parent->children()->max('position');
        BudgetItem::create([
            'parent_id' => $this->item_id,
            'budget_plan_id' => $parent->budget_plan_id,
            'budget_type' => $parent->budget_type,
            'is_group' => false,
            'position' => $pos + 1,
        ]);
    }
}
