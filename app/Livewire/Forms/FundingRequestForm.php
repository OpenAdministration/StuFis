<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use App\Models\Project;
use App\Models\Application;
use Livewire\Attributes\Validate;

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
