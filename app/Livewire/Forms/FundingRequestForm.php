<?php

namespace App\Livewire\Forms;

use App\Models\Legacy\Project;
use App\Models\PtfProject\Application;
use Livewire\Form;

class FundingRequestForm extends Form
{
    public Project $project;

    public $funding_total = '';

    // vorkasse?

    public function store()
    {
        $this->validate();

        Application::create($this->all());
    }
}
