<?php

namespace App\States\Project;

use App\Models\Legacy\Project;
use Livewire\Wireable;
use Spatie\ModelStates\Exceptions\InvalidConfig;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class ProjectState extends State implements Wireable
{
    public static string $name;

    public function iconName(): string
    {
        return 'file-pen';

    }

    public function color(): string
    {
        return 'bg-indigo-500';
    }

    public function label(): string
    {
        return __('project.stateNames.'.static::$name);
    }

    public function actionLabel(): string
    {
        return __('project.stateActions.'.static::$name);
    }

    public function expensable(): bool
    {
        return false;
    }

    /**
     * @throws InvalidConfig
     */
    public static function config(): StateConfig
    {
        $config = parent::config()
            ->default(Draft::class)
            ->allowTransition(Draft::class, Applied::class)
            ->allowTransition([ApprovedByOrg::class, ApprovedByFinance::class, ApprovedByOther::class], Terminated::class)
            ->allowTransition([Applied::class, NeedOrgApproval::class, NeedFinanceApproval::class], Revoked::class)
            ->allowTransition([Revoked::class], Draft::class);

        // here would be some dynamic logic from config possible

        $config = $config->allowTransition([
            Applied::class,
            NeedFinanceApproval::class,
            ApprovedByFinance::class,
            // NeedOrgApproval::class,
            ApprovedByOrg::class,
            ApprovedByOther::class,
            Terminated::class,
        ], NeedOrgApproval::class);

        $config = $config->allowTransition([
            Applied::class,
            NeedFinanceApproval::class,
            ApprovedByFinance::class,
            NeedOrgApproval::class,
            // ApprovedByOrg::class,
            ApprovedByOther::class,
            Terminated::class,
        ], ApprovedByOrg::class);

        $config = $config->allowTransition([
            Applied::class,
            // NeedFinanceApproval::class,
            ApprovedByFinance::class,
            NeedOrgApproval::class,
            ApprovedByOrg::class,
            ApprovedByOther::class,
            Terminated::class,
        ], NeedFinanceApproval::class);

        $config = $config->allowTransition([
            Applied::class,
            NeedFinanceApproval::class,
            // ApprovedByFinance::class,
            NeedOrgApproval::class,
            ApprovedByOrg::class,
            ApprovedByOther::class,
            Terminated::class,
        ], ApprovedByFinance::class);

        $config = $config->allowTransition([
            Applied::class,
            NeedFinanceApproval::class,
            ApprovedByFinance::class,
            NeedOrgApproval::class,
            ApprovedByOrg::class,
            // ApprovedByOther::class,
            Terminated::class,
        ], ApprovedByOther::class);

        return $config;
    }

    public function toLivewire(): array
    {
        return [$this->getValue(), $this->getModel()->getKey()];
    }

    public static function fromLivewire($value)
    {
        [$name, $id] = $value;
        $model = Project::find($id);

        return ProjectState::make($name, $model);
    }
}
