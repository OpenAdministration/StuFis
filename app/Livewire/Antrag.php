<?php

namespace App\Livewire;

use App\Models\Project;
use Livewire\Component;
use App\Livewire\Forms\ProjectForm;
use App\Http\Requests\AntragRequest;
use App\Http\Requests\ProjectRequest;
use App\Livewire\Forms\ApplicantForm;
use App\Livewire\Forms\FundingRequestForm;
use App\Livewire\Forms\ProjectBudgetForm;

class Antrag extends Component
{
    public ApplicantForm $applicantForm;

    public ProjectForm $projectForm;

    public ProjectBudgetForm $projectBudgetForm;

    public FundingRequestForm $fundingRequestForm;

    public function saveProjectForm()
    {
        $this->projectForm->store();

        return $this->redirect('antrag/2');
    }


    public function saveprojectBudgetForm()
    {
        $this->projectBudgetForm->store();

        return $this->redirect('antrag/3');
    }

    public function store(AntragRequest $request)
    {
        Antrag::create($request->validated() + [
            'user_id' => auth()->id(),
        ]);
/*        Project::create([
            'version',
            'state',
            'user_id' => auth()->id(),
            'name',
            'start_date',
            'end_date',
            'description'
        ]);

        Antrag::create([
            'user_id' => auth()->id(),
            'project_id',
            'state',
            'form_name',
            'form_version',
            'version',
            'legal_basis',
            'legal_basis_details',
            'constraints',
            'funding_total',
            'extra_fields',
        ]);
*/
    }

    public function update(AntragRequest $request, Antrag $antrag){
        $antrag->update($request->validated(), $antrag);

        return redirect()->route('antrag');
    }

    // pro Antragsschritt ein eigenes Update mit eigenem Request und Weiterleitung zum nächsten Schritt:
    // Step 1: User / Actor
    // Step 2: Projekt + Aufgabe der Studischaft
    // Step 3: Finanzplan
    // Step 4: Antrag
    // Step 5: Anhänge

    //public function createProject(ProjectRequest $request, Project)

    public function render(int $site = 1)
    {
        return view("livewire.antrag.$site");
    }
}
