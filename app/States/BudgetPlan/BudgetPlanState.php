<?php

namespace App\States\BudgetPlan;

use App\Models\BudgetPlan;
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
        // allowTransition only accepts an array for the "from" side, so transitions are grouped by target.
        return parent::config()
            ->default(Draft::class)
            ->allowTransition(Resolved::class, Draft::class)
            ->allowTransition([Draft::class, Approved::class], Resolved::class)
            ->allowTransition([Resolved::class, Published::class], Approved::class)
            ->allowTransition([Approved::class, Completed::class], Published::class)
            ->allowTransition(Published::class, Completed::class);
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
