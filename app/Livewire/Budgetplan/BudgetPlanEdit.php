<?php

namespace App\Livewire\Budgetplan;

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\FiscalYear;
use DB;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Spatie\Regex\Regex;

class BudgetPlanEdit extends Component
{
    public $organization;

    public $fiscal_year_id;

    #[Url(as: 'plan_id')]
    public $plan_id;

    public $resolution_date;

    public $approval_date;

    /**
     * @var array an array which holds Livewire ItemForm objects.
     *            $items[]
     */
    public $items;

    public function mount(int $plan_id): void
    {
        BudgetItem::eagerLoadRelations(['orderedChildren']);
        $plan = BudgetPlan::findOrFail($plan_id);
        $this->organization = $plan->organization;
        $this->fiscal_year_id = $plan->fiscal_year_id;
        $this->resolution_date = $plan->resolution_date;
        $this->approval_date = $plan->approval_date;

        // we don't want to have the models as public properties
        $this->items = [];

        $all_items = $this->query()
            ->orderBy('parent_id')
            ->orderBy('position')
            ->get()->keyBy('id');
        foreach ($all_items as $item) {
            $form = new ItemForm($this, 'items.'.$item->id);
            $form->setItem($item);
            $this->items[$item->id] = $form;
        }

    }

    public function query(BudgetType|int|null $budget_type = null): Builder
    {
        $query = BudgetItem::with('children')
            ->where('budget_plan_id', $this->plan_id);
        if ($budget_type) {
            $query = $query->where('budget_type', $budget_type);
        }

        return $query->orderBy('position');
    }

    public function render()
    {
        $fiscal_years = FiscalYear::all();
        $item_models = $this->query()
            ->whereIn('id', array_keys($this->items))
            ->get()->keyBy('id');

        $in_ids = $this->query(1)->whereNull('parent_id')->pluck('id');
        $out_ids = $this->query(-1)->whereNull('parent_id')->pluck('id');

        return view('livewire.budgetplan.plan-edit', [
            'fiscal_years' => $fiscal_years,
            'all_items' => $item_models,
            'root_items' => [
                'in' => $in_ids,
                'out' => $out_ids,
            ],
        ]);
    }

    public function updated($property): void
    {
        $item_regex = Regex::match('/^items\.(\d+)\.([a-z_]+)$/', $property);
        if ($item_regex->hasMatch()) {
            $item_id = $item_regex->group(1);
            $item_prop = $item_regex->group(2);
            $value = $this->items[$item_id]->$item_prop;
            $item = BudgetItem::findOrFail($item_id);
            $item->update([$item_prop => $value]);
            if ($item_prop === 'value') {
                $this->dispatch('value-updated', $item->id);
            }
            Flux::toast('Your changes have been saved.', variant: 'success');

            return;
        }
        if (in_array($property, ['organization', 'fiscal_year_id', 'resolution_date', 'approval_date'])) {
            $value = $this->$property;
            $item = BudgetPlan::findOrFail($this->plan_id);
            $item->update([
                $property => $value,
            ]);
            Flux::toast(text: "$property -> $value", heading: 'Your changes have been saved.', variant: 'success');

            return;
        }

    }

    public function sort($item_id, $new_position): void
    {
        $item = BudgetItem::findOrFail($item_id);

        $current_position = $item->position;

        if ($current_position === $new_position) {
            return;
        }

        // pickup all items between old and new position
        $block = BudgetItem::whereBetween('position', [
            min($current_position, $new_position),
            max($current_position, $new_position),
        ]);

        DB::transaction(static function () use ($block, $item, $current_position, $new_position) {
            if ($current_position < $new_position) {
                // if item is shifted down then shift everything up
                $block->decrement('position');
            } else {
                $block->increment('position');
            }

            $item->update(['position' => $new_position]);
        });

        Flux::toast('Dragging and dropping', variant: 'success');
    }

    public function save(): void
    {
        // check if saveable
        // $this->validate();

        $plan = BudgetPlan::findOrFail($this->plan_id);
        $plan->update([
            'resolution_date' => $this->resolution_date,
            'approval_date' => $this->approval_date,
            'organisation' => $this->organization,
        ]);
    }

    public function addGroup(BudgetType $budget_type): void
    {
        $newPos = $this->query($budget_type)->whereNull('parent_id')->max('position') + 1;
        $new_item = BudgetItem::create([
            'parent_id' => null,
            'budget_plan_id' => $this->plan_id,
            'budget_type' => $budget_type,
            'is_group' => true,
            'position' => $newPos,
        ]);
        $form = new ItemForm($this, 'items.'.$new_item->budget_type->slug().'.'.$new_item->id);
        $form->setItem($new_item);
        $this->items[$new_item->id] = $form;
    }

    public function addBudget($parent_id): void
    {
        $parent = BudgetItem::findOrFail($parent_id);
        if ($parent->is_group === 0) {
            return;
        }

        $pos = $parent->children()->max('position');
        $new_item = BudgetItem::create([
            'parent_id' => $parent_id,
            'budget_plan_id' => $parent->budget_plan_id,
            'budget_type' => $parent->budget_type,
            'is_group' => false,
            'position' => $pos + 1,
        ]);
        $form = new ItemForm($this, 'items.'.$new_item->budget_type->slug().'.'.$new_item->id);
        $form->setItem($new_item);
        $this->items[$new_item->id] = $form;
    }

    public function convertToGroup(int $item_id): void
    {
        $item = BudgetItem::findOrFail($item_id);
        if ($item->is_group) {
            return;
        }
        $item->update(['is_group' => true]);
    }

    public function convertToBudget(int $item_id): void
    {
        $item = BudgetItem::findOrFail($item_id);
        if (! $item->is_group) {
            return;
        }
        if ($item->children()->count() === 0) {
            $item->update(['is_group' => false]);
        }
    }

    public function copyItem(int $item_id)
    {
        $item = BudgetItem::findOrFail($this->item_id);
        $this->copyItemModel($item, $item->parent_id);
    }

    private function copyItemModel(BudgetItem $item, ?int $parent_id): void
    {
        $newItem = BudgetItem::create([
            'budget_plan_id' => $item->budget_plan_id,
            'budget_type' => $item->budget_type,
            'is_group' => $item->is_group,
            'parent_id' => $parent_id,
            'value' => $item->value,
            'position' => $item->position + 1,
            'name' => $item->name.' - Copy',
            'short_name' => $item->short_name.' - Copy',
        ]);

        foreach ($item->children as $child) {
            $this->copyItemModel($child, $newItem->id);
        }

    }

    public function delete()
    {
        $item = BudgetItem::findOrFail($this->item_id);

        if ($item->children()->count() > 0) {
            $this->addError('name', 'You cannot delete more than one item.');

            return;
        }
        $item->delete();
    }
}
