<?php

namespace App\States\Project;

use App\Models\Legacy\Project;
use App\Rules\ExactlyOneZeroMoneyRule;
use App\Rules\FluxEditorRule;
use Illuminate\Support\Facades\Validator;
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
    #[\Override]
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

    public function rules() : array {
        // some sensible default i dont want to copy paste around
        return [
            'name' => 'required|string|max:128',
            'responsible' => 'required|string|max:128|email',
            'org' => 'required|string|max:64',
            'protocol' => 'sometimes|nullable|string|url',
            //'recht' => 'required|string|in:...',
            //'recht-additional' => 'sometimes|nullable|string',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after:date_start',
            'beschreibung' => ['required', 'string', new FluxEditorRule],
            'posts' => 'required|array|min:1',
            'posts.*.id' => 'sometimes|integer',
            //'posts.*.titel_id' => 'sometimes|integer|exists:App\Models\Legacy\LegacyBudgetItem,id',
            'posts.*.name' => 'required|string|max:128|min:1',
            'posts.*.einnahmen' => 'required|money:EUR',
            'posts.*.ausgaben' => ['required','money:EUR', new ExactlyOneZeroMoneyRule('posts.*.einnahmen')],
            'posts.*.position' => 'sometimes|integer',
            'posts.*.bemerkung' => 'sometimes|string|max:256',
        ];
    }

    public function getValidator() : \Illuminate\Validation\Validator
    {
        $model = $this->getModel();
        $data = [...$model->getAttributes(), 'posts' => $model->posts->all()];
        return Validator::make($data, static::rules());
    }

    public function validate() : array
    {
        return $this->getValidator()->validate();
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
