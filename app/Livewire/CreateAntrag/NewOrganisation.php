<?php

namespace App\Livewire\CreateAntrag;

use App\Livewire\Forms\ActorForm;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\View\View;
use Livewire\Component;

class NewOrganisation extends Component
{
    public ActorForm $orgForm;

    public function mount(): void
    {
        $this->orgForm->is_organisation = true;
    }

    public function render(): \Illuminate\Contracts\View\View|Application|Factory|View|\Illuminate\Contracts\Foundation\Application
    {
        return view('livewire.create-antrag.new-organisation');
    }

    public function create(): void
    {
        $this->orgForm->create();
    }
}
