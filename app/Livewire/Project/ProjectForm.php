<?php

namespace App\Livewire\Project;

use App\Models\Legacy\LegacyBudgetPlan;
use App\Models\Legacy\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use Livewire\Form;

class ProjectForm extends Form
{
    public string $name = '';

    public string $responsible = '';

    public string $org = '';

    public string $org_mail = '';

    public string $protokoll = '';

    public string $beschreibung = '';

    public string $recht = '';

    public string $recht_additional = '';

    public array $dateRange = [];

    public int $hhp_id;

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'responsible' => 'required|email',
            'org' => 'required|string',
            'org_mail' => 'nullable|email',
            'protokoll' => 'nullable|string',
            'beschreibung' => 'required|string',
            'recht' => 'nullable|string',
            'recht_additional' => 'nullable|string',
            'dateRange' => 'required|array|size:2',
            'dateRange.*' => 'required|date',
            'createdat' => 'nullable|date',
        ];
    }

    public int $version = 1;

    /**
     * Set the project and load its data into the form
     */
    public function setProject(Project $project): void
    {
        $this->name = $project->name ?? '';
        $this->responsible = $project->responsible ?? '';
        $this->org = $project->org ?? '';
        $this->org_mail = $project->org_mail ?? '';
        $this->protokoll = $project->protokoll ?? '';
        $this->beschreibung = $project->beschreibung ?? '';
        $this->recht = $project->recht ?? '';
        $this->recht_additional = $project->recht_additional ?? '';
        $this->dateRange = ['start' => $project->date_start, 'end' => $project->date_end];
        $this->version = $project->version;
        $this->hhp_id = LegacyBudgetPlan::findByDate($project->createdat)->id;
    }

    /**
     * Initialize form for new project
     */
    public function initializeNew(): void
    {
        $this->hhp_id = LegacyBudgetPlan::findByDate(now())->id;
        $this->version = 1;
    }

    /**
     * Prepare data for saving
     */
    public function getValues(): array
    {
        return [
            'name' => $this->name,
            'responsible' => $this->responsible,
            'org' => $this->org,
            'org_mail' => $this->org_mail,
            'protokoll' => $this->protokoll,
            'beschreibung' => $this->beschreibung,
            'recht' => $this->recht,
            'recht_additional' => $this->recht_additional,
            // make compatible with legacy database
            'date_start' => $this->dateRange['start'] ?? null,
            'date_end' => $this->dateRange['end'] ?? null,
            'version' => $this->version,
            'createdat' => Date::parse(LegacyBudgetPlan::find($this->hhp_id)->von),
        ];
    }
}
