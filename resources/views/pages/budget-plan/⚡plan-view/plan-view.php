<?php

use App\Models\BudgetPlan;
use App\Models\Enums\BudgetType;
use App\Models\User;
use App\States\BudgetPlan\Active;
use App\States\BudgetPlan\Approved;
use App\States\BudgetPlan\BudgetPlanState;
use App\States\BudgetPlan\Completed;
use App\States\BudgetPlan\Draft;
use App\States\BudgetPlan\Resolved;
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
     * The three meta dates (OP#588), captured ONLY in the state-change modal, at the moment of
     * the transition that gives each one its meaning — never freely editable in an edit form or
     * detail view anymore (that was the pre-OP#588 design: a free resolution_date/approval_date
     * pair on ⚡plan-edit, and an approval_date/activation_date pair editable directly on an
     * amendment's view). All three stay optional: a blank value must never block the transition.
     * See targetState() for which of these the modal actually shows, and changeState() for how
     * a supplied value is persisted in the same write as the state change itself.
     */
    public $resolution_date;

    public $approval_date;

    public $activation_date;

    public function mount(int $plan_id): void
    {
        $this->plan_id = $plan_id;
        $plan = $this->plan();
        $this->authorize('view', $plan);
    }

    /**
     * The state the currently selected $newState resolves to, or null before a selection is made.
     * Drives which of the three optional date fields the state-modal shows (see its blade) —
     * newState is bound wire:model.live so this stays in sync server-side as the user picks,
     * without reaching for JS (CSP forbids inline handlers).
     */
    public function targetState(): ?BudgetPlanState
    {
        if (blank($this->newState)) {
            return null;
        }

        return BudgetPlanState::make($this->newState, $this->plan());
    }

    /**
     * The visible date field(s) depend entirely on the currently selected target state — clear
     * stale input from a previous selection so it can never leak into a different target's branch
     * in changeState().
     */
    public function updatedNewState(): void
    {
        $this->reset('resolution_date', 'approval_date', 'activation_date');
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
            // an Approved amendment whose scheduled activation_date has passed without the daily
            // stufis:apply-due-amendments run having activated it yet (e.g. its parent plan wasn't
            // Active at the time) — surfaced as a warning callout rather than failing silently
            'amendment_overdue' => $plan->isAmendment()
                && $plan->state instanceof Approved
                && $plan->activation_date !== null
                && $plan->activation_date->isPast(),
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
        $filtered = $this->validate([
            'newState' => ['required', new ValidStateRule(BudgetPlanState::class)],
            // OP#588: the meta dates offered alongside the target state — all optional, never
            // required to complete the transition
            'resolution_date' => ['nullable', 'date'],
            'approval_date' => ['nullable', 'date'],
            'activation_date' => ['nullable', 'date'],
        ]);
        $newState = BudgetPlanState::make($filtered['newState'], $plan);

        // OP#584/OP#588 share this same forward/backward distinction (BudgetPlanState::order() /
        // advancesTo()): a backward step only ever demotes data away from "official" and must
        // never be gated OR asked for anything new — so both the item-rule validation below and
        // the date capture further down are skipped entirely for a backward move (e.g. reverting
        // an applied amendment, or reactivating a Completed plan).
        $isForwardStep = $plan->state->advancesTo($newState);

        // Business-rule check (OP#584): the target state's item rules (Titelnummer uniqueness,
        // name, non-negative value) must hold before the transition is even authorized — mirrors
        // ⚡show-project's changeState(). This deliberately runs only here, at the Livewire layer:
        // the cascaded Active<->Completed writes for an amendment's siblings
        // (CompleteAmendmentsTransition / ReactivateAmendmentsTransition, OP#589) call
        // $child->state->transitionTo() directly on the model and never come through here, so
        // this check can never block that cascade either way.
        if ($isForwardStep) {
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

        // OP#588: capture whichever optional date(s) give THIS target state its meaning — see
        // targetState() in the blade for the matching display logic. Setting the attribute
        // directly on $plan here (rather than a separate ->update() call) means the transition's
        // own save() — every arc ultimately runs through Spatie's DefaultTransition::handle(),
        // which calls $this->model->save() — persists the date and the new state in one write,
        // so a plan can never end up transitioned with its date silently dropped.
        if ($isForwardStep) {
            if ($newState instanceof Resolved && filled($this->resolution_date)) {
                $plan->resolution_date = $this->resolution_date;
            }
            if ($newState instanceof Approved) {
                if (filled($this->approval_date)) {
                    $plan->approval_date = $this->approval_date;
                }
                // activation_date stays amendment-only (unchanged from before OP#588)
                if ($plan->isAmendment() && filled($this->activation_date)) {
                    $plan->activation_date = $this->activation_date;
                }
            }
            if ($newState instanceof Active && $plan->isAmendment() && $plan->activation_date === null && filled($this->activation_date)) {
                $plan->activation_date = $this->activation_date;
            }
        }

        try {
            $plan->state->transitionTo($newState);
            Flux::toast(__('budget-plan.view.state-changed'), variant: 'success');
            Flux::modal('state-modal')->close();
            $this->reset('newState', 'resolution_date', 'approval_date', 'activation_date');
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
