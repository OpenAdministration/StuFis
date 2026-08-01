<?php

namespace App\States\BudgetPlan;

use App\Models\BudgetPlan;
use App\States\BudgetPlan\Transitions\ApplyAmendmentTransition;
use App\States\BudgetPlan\Transitions\RevertAmendmentTransition;
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
        // Two arcs carry a custom transition class instead of the package default: Approved -> Active
        // is where a Nachtragshaushaltsplan (amendment) gets applied onto its parent plan's live
        // budget_item rows, and Active -> Approved is where an applied amendment gets reverted. Both
        // transition classes are no-ops for an ordinary (non-amendment) plan — see
        // App\Support\Budget\AmendmentApplier.
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Resolved::class, Draft::class)
            ->allowTransition(Draft::class, Resolved::class)
            ->allowTransition(Approved::class, Resolved::class)
            ->allowTransition(Resolved::class, Approved::class)
            ->allowTransition(Active::class, Approved::class, RevertAmendmentTransition::class)
            ->allowTransition(Approved::class, Active::class, ApplyAmendmentTransition::class)
            ->allowTransition(Completed::class, Active::class)
            ->allowTransition(Active::class, Completed::class);
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
