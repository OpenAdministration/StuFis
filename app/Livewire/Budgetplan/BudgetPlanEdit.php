<?php

namespace App\Livewire\Budgetplan;

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\FiscalYear;
use DB;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class BudgetPlanEdit extends Component
{
    public $organization;

    public $fiscal_year_id;

    #[Url(as: 'plan_id')]
    public $plan_id;

    public $resolution_date;

    public $approval_date;

    public function mount(int $plan_id): void
    {
        $plan = BudgetPlan::findOrFail($plan_id);
        $this->organization = $plan->organization;
        $this->fiscal_year_id = $plan->fiscal_year_id;
        $this->resolution_date = $plan->resolution_date;
        $this->approval_date = $plan->approval_date;

    }

    public function render()
    {

        $fiscal_years = FiscalYear::all();

        $items_in = BudgetItem::where('budget_plan_id', $this->plan_id)
            ->where('budget_type', 1)
            ->where('parent_id', null)
            ->orderBy('position')
            ->get();

        $items_out = BudgetItem::where('budget_plan_id', $this->plan_id)
            ->where('budget_type', -1)
            ->where('parent_id', null)
            ->orderBy('position')
            ->get();

        return view('livewire.budgetplan.plan-edit', [
            'fiscal_years' => $fiscal_years, 'items_in' => $items_in, 'items_out' => $items_out,
        ]);
    }

    public function addGroup($budget_type): void
    {
        BudgetItem::create([
            'budget_plan_id' => $this->plan_id,
            'budget_type' => $budget_type,
            'is_group' => true,
        ]);
    }

    #[On('save-budget-plan')]
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

    public function updated($property): void
    {
        $value = $this->$property;
        $item = BudgetPlan::findOrFail($this->plan_id);
        $item->update([
            $property => $value,
        ]);

        Flux::toast('Your changes have been saved.', variant: 'success');

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

        DB::transaction(function () use ($block, $item, $current_position, $new_position) {
            if ($current_position < $new_position) {
                // if item is shifted down then shift everything up
                $block->decrement('position');
            } else {
                $block->increment('position');
            }

            $item->update(['position' => $new_position]);
        });

        Flux::toast('Draging and droppin', variant: 'success');
    }
}
