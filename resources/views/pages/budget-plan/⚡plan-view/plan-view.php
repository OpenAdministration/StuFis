<?php

use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\User;
use App\States\BudgetPlan\BudgetPlanState;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;
use Spatie\ModelStates\Validation\ValidStateRule;

new #[Layout('layout.app', ['size' => 'lg'])] class extends Component
{
    #[Locked]
    public int $plan_id;

    public $newState;

    public function mount(int $plan_id): void
    {
        $this->plan_id = $plan_id;
        $this->authorize('view', $this->plan());
    }

    public function with(): array
    {
        $plan = $this->plan();

        return [
            'plan' => $plan,
            'items' => [
                BudgetType::INCOME->slug() => $plan->budgetItemsTree(BudgetType::INCOME),
                BudgetType::EXPENSE->slug() => $plan->budgetItemsTree(BudgetType::EXPENSE),
            ],
        ];
    }

    /**
     * Move the plan along its workflow. Mirrors the project state-change flow:
     * validate the target, authorize the transition, then run it via the state machine.
     */
    public function changeState(): void
    {
        $plan = $this->plan();
        $filtered = $this->validate(['newState' => ['required', new ValidStateRule(BudgetPlanState::class)]]);
        $newState = BudgetPlanState::make($filtered['newState'], $plan);

        $this->authorize('transition-to', [$plan, $newState]);

        try {
            $plan->state->transitionTo($newState);
            Flux::toast(__('budget-plan.view.state-changed'), variant: 'success');
            Flux::modal('state-modal')->close();
            $this->reset('newState');
        } catch (CouldNotPerformTransition $e) {
            $this->addError('newState', $e->getMessage());
        }
    }

    /**
     * Delete the whole plan and its items. Admin-only for now.
     */
    public function deletePlan(): void
    {
        $this->authorize('admin', User::class);

        $plan = $this->plan();

        DB::transaction(static function () use ($plan): void {
            // budget_item has a self-referencing parent_id FK and a plan FK without cascade;
            // drop the items with checks off, then the plan itself
            Schema::disableForeignKeyConstraints();
            $plan->budgetItems()->delete();
            $plan->delete();
            Schema::enableForeignKeyConstraints();
        });

        Flux::toast(__('budget-plan.view.plan-deleted'), variant: 'success');
        $this->redirect(route('budget-plan.index'), navigate: true);
    }

    private function plan(): BudgetPlan
    {
        return BudgetPlan::findOrFail($this->plan_id);
    }
};
