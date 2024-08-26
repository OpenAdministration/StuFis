<?php

namespace App\Livewire\Forms;

use App\Models\Project;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ProjectForm extends Form
{
    #[Validate('required')]
    public $name = '';

    #[Validate('required')]
    public $start_date = '';

    #[Validate('required')]
    public $end_date = '';

    #[Validate('required')]
    public $description = '';

    #[Validate('required')]
    public $target_group = '';

    #[Validate('required')]
    public $student_body_duties = [];

    #[Validate('required')]
    public $estimated_guests = '';

    #[Validate('required')]
    public $estimated_students = '';

    public function store()
    {
        $this->validate();

        Project::create($this->all());
    }
}
