<?php

namespace App\States\BudgetPlan;

use App\Models\BudgetItem;
use App\Models\BudgetPlan;
use App\Models\User;
use App\Rules\BudgetPlanItemRule;
use App\States\BudgetPlan\Transitions\ApplyAmendmentTransition;
use App\States\BudgetPlan\Transitions\ApproveAmendmentTransition;
use App\States\BudgetPlan\Transitions\CompleteAmendmentsTransition;
use App\States\BudgetPlan\Transitions\ReactivateAmendmentsTransition;
use App\States\BudgetPlan\Transitions\RevertAmendmentTransition;
use Illuminate\Support\Facades\Validator;
use Livewire\Wireable;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class BudgetPlanState extends State implements Wireable
{
    public static string $name;

    public function iconName(): string
    {
        return 'fas-file-pen';
    }

    public function color(): string
    {
        return 'zinc';
    }

    public function label(): string
    {
        return __('budget-plan.stateNames.'.static::$name);
    }

    public function actionLabel(): string
    {
        return __('budget-plan.stateActions.'.static::$name);
    }

    #[\Override]
    public static function config(): StateConfig
    {
        // Linear workflow: each state may advance to the next or step back to the previous one.
        // Three arcs carry a custom transition class instead of the package default. Resolved ->
        // Approved and Active -> Approved (revert) both default an amendment's activation_date to
        // approval_date when unset (DefaultsActivationDateOnApproval); Approved -> Active is where
        // an amendment gets applied onto its parent plan's live budget_item rows, and Active ->
        // Approved also un-applies it on the way back. All three are no-ops for an ordinary
        // (non-amendment) plan — see App\Support\Budget\AmendmentApplier.
        //
        // Two more arcs, Active <-> Completed, carry a cascade transition class (OP#589 F8): when
        // an original plan crosses this arc, every one of its amendments that is currently
        // Active/Completed follows it in the same DB transaction, so an amendment never drifts out
        // of sync with the plan it was applied to. An amendment reached directly on this arc is
        // blocked at the policy layer (BudgetPlanPolicy::transitionTo) — see
        // CompleteAmendmentsTransition / ReactivateAmendmentsTransition for the cascade itself.
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Resolved::class, Draft::class)
            ->allowTransition(Draft::class, Resolved::class)
            ->allowTransition(Approved::class, Resolved::class)
            ->allowTransition(Resolved::class, Approved::class, ApproveAmendmentTransition::class)
            ->allowTransition(Active::class, Approved::class, RevertAmendmentTransition::class)
            ->allowTransition(Approved::class, Active::class, ApplyAmendmentTransition::class)
            ->allowTransition(Completed::class, Active::class, ReactivateAmendmentsTransition::class)
            ->allowTransition(Active::class, Completed::class, CompleteAmendmentsTransition::class);
    }

    /**
     * The canonical order of the linear BudgetPlanState workflow (OP#584) — Draft is the least
     * advanced, Completed the most. config() only declares which arcs exist, not their relative
     * order, so this is the single source of truth used to tell a forward step (promoting data
     * toward "official") from a backward one (demoting) — see isAdvancement(). Do not infer this
     * from config()'s allowTransition() calls; those describe the graph, not a direction.
     *
     * @return list<class-string<BudgetPlanState>>
     */
    public static function order(): array
    {
        return [Draft::class, Resolved::class, Approved::class, Active::class, Completed::class];
    }

    /** This state's position in order() (0 = Draft, ... 4 = Completed). */
    public function rank(): int
    {
        return array_search(static::class, static::order(), true);
    }

    /**
     * Whether moving from this state to $target is a forward step along order() (e.g.
     * Resolved -> Approved) rather than a backward one (e.g. Active -> Approved). Used by
     * ⚡plan-view::changeState() to decide whether the target state's item rules (see
     * itemRules()) apply at all: a backward step only ever demotes data away from "official" and
     * must never be gated by a pre-existing violation it cannot fix — reverting an applied
     * amendment, or reactivating a Completed plan, must always stay possible regardless of
     * legacy item data.
     */
    public function isAdvancement(BudgetPlanState $target): bool
    {
        return $target->rank() > $this->rank();
    }

    /**
     * Whether moving from this state to $target is the Active <-> Completed arc that
     * CompleteAmendmentsTransition / ReactivateAmendmentsTransition cascade onto an amendment's
     * siblings (OP#589 F8) — the one arc an amendment must never walk on its own. Used by
     * BudgetPlanPolicy::transitionTo() to refuse it for an amendment reached directly.
     */
    public function isCascadingArc(BudgetPlanState $target): bool
    {
        return ($this instanceof Active && $target instanceof Completed)
            || ($this instanceof Completed && $target instanceof Active);
    }

    /**
     * Business-rule checks a plan's budget items must satisfy to legitimately BE in this state
     * (OP#584): short_name (Titelnummer) unique within scope, name non-empty, value non-negative.
     * Direction-agnostic by design — whether reaching this state is a forward or backward step is
     * decided by the caller (see isAdvancement()), not here.
     *
     * Completed overrides this to an empty ruleset: Active -> Completed is the only forward arc
     * that ever reaches it, and it's a bookkeeping-period toggle, not a data edit — an
     * already-Active plan must never get stuck un-transitionable over legacy item data just
     * because it's being marked done. (Completed -> Active reactivation is a backward step and is
     * already exempt via isAdvancement() regardless of this override — see that method.)
     */
    public function itemRules(): array
    {
        return [
            'items' => 'array',
            'items.*' => [new BudgetPlanItemRule],
        ];
    }

    public function rules(): array
    {
        return $this->itemRules();
    }

    /**
     * The budget items this state's rules are checked against: this plan's own rows, plus — for
     * an amendment — its base plan's rows too (OP#584: an amendment must not introduce a
     * Titelnummer that already exists on the plan it will be merged into). An amendment's own
     * `add` rows already live under its own budget_plan_id (see BudgetItemChange's class doc),
     * while `modify`/`delete` rows keep pointing at the live base-plan row rather than a copy, so
     * a plain budget_plan_id IN (...) query already reflects every item exactly once. Walking
     * itemChanges() on top of this would double-count a modify/delete row's already-included base
     * item as a spurious duplicate of itself — so this deliberately doesn't.
     */
    protected function itemsForValidation(): array
    {
        $plan = $this->getModel();
        $planIds = array_filter([$plan->id, $plan->parent_plan_id]);

        return BudgetItem::query()
            ->whereIn('budget_plan_id', $planIds)
            ->get(['id', 'short_name', 'name', 'value'])
            ->map(static fn (BudgetItem $item): array => [
                'id' => $item->id,
                'short_name' => $item->short_name,
                'name' => $item->name,
                'value' => $item->value->getAmount(),
            ])
            ->all();
    }

    /**
     * Create and return a validator instance checking this state's item rules against the plan's
     * current budget items (or the provided data, e.g. from a test). Mirrors ProjectState's
     * getValidator()/validate() — see that class for the full pattern this is ported from.
     */
    public function getValidator(array $data = [], ?User $user = null): \Illuminate\Contracts\Validation\Validator
    {
        if ($data === []) {
            $data = ['items' => $this->itemsForValidation()];
        }

        return Validator::make($data, $this->rules());
    }

    public function validate(array $data = []): array
    {
        return $this->getValidator($data)->validate();
    }

    public function toLivewire(): array
    {
        return [$this->getValue(), $this->getModel()->getKey()];
    }

    public static function fromLivewire($value): BudgetPlanState
    {
        [$name, $id] = $value;
        $model = BudgetPlan::find($id);

        return BudgetPlanState::make($name, $model);
    }
}
