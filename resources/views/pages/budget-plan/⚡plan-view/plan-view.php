<?php

use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\User;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Approved;
use App\States\BudgetPlan\BudgetPlanState;
use App\States\BudgetPlan\Completed;
use App\States\BudgetPlan\Draft;
use App\Support\Budget\AmendmentConflictException;
use App\Support\Budget\BudgetPlanMeasures;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
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
                // annotate() returns the flattened tree with booked/committed Money set per node
                BudgetType::INCOME->slug() => new BudgetPlanMeasures($plan, BudgetType::INCOME)->annotate(),
                BudgetType::EXPENSE->slug() => new BudgetPlanMeasures($plan, BudgetType::EXPENSE)->annotate(),
            ],
            // an Approved amendment whose scheduled effective_date has passed without the daily
            // stufis:apply-due-amendments run having activated it yet (e.g. its parent plan wasn't
            // Active at the time) — surfaced as a warning callout rather than failing silently
            'amendment_overdue' => $plan->isAmendment()
                && $plan->state instanceof Approved
                && $plan->effective_date !== null
                && $plan->effective_date->isPast(),
            // amendments not yet (or no longer) live-effective — parallel drafts are allowed, so
            // there can be more than one. Only shown on an original plan's own view.
            'open_amendments' => $plan->isAmendment()
                ? collect()
                : $plan->amendments()->whereNotIn('state', [Active::$name, Completed::$name])->get(),
            'can_create_amendment' => ! $plan->isAmendment()
                && $plan->state instanceof Active
                && Auth::user()?->can('create', BudgetPlan::class),
            // an amendment's own view shows the diff (changed items only, from -> to, with
            // reasons) instead of the plain tree — the full merged tree stays the editor's job
            'amendment_changes' => $plan->isAmendment()
                ? $plan->itemChanges()->with('budgetItem')->get()
                : collect(),
        ];
    }

    /**
     * Draft a new Nachtragshaushaltsplan against this (Active, original) plan and jump straight
     * into its editor. Parallel drafts are allowed, so this never blocks on existing amendments.
     */
    public function createAmendment(): void
    {
        $plan = $this->plan();
        $this->authorize('create', BudgetPlan::class);
        abort_unless(! $plan->isAmendment() && $plan->state instanceof Active, 403);

        $amendment = BudgetPlan::create([
            'state' => Draft::class,
            'organization' => $plan->organization,
            'fiscal_year_id' => $plan->fiscal_year_id,
            'parent_plan_id' => $plan->id,
        ]);

        $this->redirect(route('budget-plan.amendment.edit', [$plan->id, $amendment->id]), navigate: true);
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
        } catch (AmendmentConflictException $e) {
            // the amendment apply/revert engine aborted the whole transition atomically —
            // the plan's state is unchanged, so surface this as a toast, not a field error
            Flux::toast($e->getMessage(), variant: 'danger');
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
