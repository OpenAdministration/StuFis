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
use Illuminate\Validation\ValidationException;
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

    /**
     * approval_date / effective_date, bound only on an amendment's view (B3 — OP#581): an
     * amendment has no metadata editor of its own (⚡plan-edit is exclusively for original plans
     * and redirects amendments away), so this is the only place these two fields can be set.
     * Editable while the amendment is Draft..Approved, read-only from Active onward — see
     * datesEditable().
     */
    public $approval_date;

    public $effective_date;

    public function mount(int $plan_id): void
    {
        $this->plan_id = $plan_id;
        $plan = $this->plan();
        $this->authorize('view', $plan);

        if ($plan->isAmendment()) {
            $this->approval_date = $plan->approval_date?->format('Y-m-d');
            $this->effective_date = $plan->effective_date?->format('Y-m-d');
        }
    }

    /** Authorized users may set the amendment's approval/effective dates up through Approved — once Active the applier has already used them, so they freeze. */
    public function datesEditable(): bool
    {
        $plan = $this->plan();

        return $plan->isAmendment()
            && (! $plan->state instanceof Active && ! $plan->state instanceof Completed)
            && (Auth::user()?->can('update', $plan) ?? false);
    }

    public function updatedApprovalDate(): void
    {
        $this->saveAmendmentDate('approval_date', $this->approval_date);
    }

    public function updatedEffectiveDate(): void
    {
        $this->saveAmendmentDate('effective_date', $this->effective_date);
    }

    private function saveAmendmentDate(string $field, mixed $value): void
    {
        if (! $this->datesEditable()) {
            return;
        }

        $this->plan()->update([$field => $value ?: null]);
        Flux::toast(__('budget-plan.edit.saved'), variant: 'success');
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
            // F4 (OP#589): an amendment may be drafted against an Approved plan as well as an
            // Active one — Approved is already a stable, agreed-upon document, so there's no
            // reason to force waiting for activation first. Keep this in sync with the
            // abort_unless() guard in createAmendment() below.
            'can_create_amendment' => ! $plan->isAmendment()
                && ($plan->state instanceof Active || $plan->state instanceof Approved)
                && Auth::user()?->can('create', BudgetPlan::class),
            // an amendment's own view shows the diff (changed items only, from -> to, with
            // reasons) instead of the plain tree — the full merged tree stays the editor's job
            'amendment_changes' => $plan->isAmendment()
                ? $plan->itemChanges()->with('budgetItem')->get()
                : collect(),
            'dates_editable' => $this->datesEditable(),
            'delta_summary' => $plan->isAmendment() ? $plan->amendmentDeltaSummary() : null,
            // F5 (OP#589): the delete-plan-modal's checklist rows — surfaced here rather than
            // computed inline in the blade so deletePlan()'s server-side guard below reads
            // identically to what the user was shown.
            'user_can_delete_plan' => Auth::user()?->can('admin', User::class) ?? false,
            'plan_deletable' => $plan->isEditable(),
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
        abort_unless(! $plan->isAmendment() && ($plan->state instanceof Active || $plan->state instanceof Approved), 403);

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

        // Business-rule check (OP#584): the target state's item rules (Titelnummer uniqueness,
        // name, non-negative value) must hold before the transition is even authorized — mirrors
        // ⚡show-project's changeState(). Only checked on a FORWARD step (advancesTo()): moving
        // backward only ever demotes data away from "official", never promotes it, so a backward
        // step (e.g. reverting an applied amendment, or reactivating a Completed plan) must always
        // stay possible regardless of a pre-existing violation it cannot fix. This deliberately
        // runs only here, at the Livewire layer: the cascaded Active<->Completed writes for an
        // amendment's siblings (CompleteAmendmentsTransition / ReactivateAmendmentsTransition,
        // OP#589) call $child->state->transitionTo() directly on the model and never come through
        // here, so this check can never block that cascade either way.
        if ($plan->state->advancesTo($newState)) {
            try {
                $newState->getValidator()->validate();
            } catch (ValidationException $e) {
                foreach ($e->validator->errors()->all() as $message) {
                    $this->addError('newState', $message);
                }

                return;
            }
        }

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
     * Delete the whole plan and its items. Admin-only for now, and (F5, OP#589) only while the
     * plan is still editable (Draft/Resolved) — past Approved it's meant to be a stable,
     * agreed-upon document, so it may no longer be wiped outright. Mirrors the checklist rows
     * shown in delete-plan-modal.
     */
    public function deletePlan(): void
    {
        $this->authorize('admin', User::class);

        $plan = $this->plan();
        abort_unless($plan->isEditable(), 403);

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
