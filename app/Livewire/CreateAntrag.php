<?php

namespace App\Livewire;

use App\Livewire\Forms\ActorForm;
use App\Livewire\Forms\FundingRequestForm;
use App\Livewire\Forms\ProjectBudgetForm;
use App\Models\PtfProject\Actor;
use Livewire\Attributes\Url;
use Livewire\Component;

class CreateAntrag extends Component
{
    #[Url]
    public int $page = 1;

    public ActorForm $userForm;

    public ActorForm $organisationForm;

    public $projectForm; // might have been deleted at the rework of the project view

    public ProjectBudgetForm $projectBudgetForm;

    public FundingRequestForm $fundingRequestForm;

    public function store()
    {
        // TODO: call all form store methods
    }

    // pro Antragsschritt die Page variable hochzählen und damit Weiterleitung zum nächsten Schritt:
    // Step 1: User / Actor
    // Step 2: Projekt + Aufgabe der Studischaft
    // Step 3: Finanzplan
    // Step 4: Antrag
    // Step 5: Anhänge

    public function render()
    {

        // for better code analysis, and better error handling a bit more verbose than needed
        switch ($this->page) {
            case 1:
                $users = Actor::user()->get();
                $orgs = Actor::organisation()->get();

                return view('livewire.create-antrag.1', ['users' => $users, 'orgs' => $orgs]);
            case 2:
                return view('livewire.create-antrag.2');
            case 3:
                return view('livewire.create-antrag.3');
            case 4:
                return view('livewire.create-antrag.4');
            case 5:
                return view('livewire.create-antrag.5');
            default:
                abort(404);
        }
    }

    public function nextPage(): void
    {
        $this->page = min($this->page + 1, 5);
    }

    public function previousPage(): void
    {
        $this->page = max($this->page - 1, 1);
    }
}
