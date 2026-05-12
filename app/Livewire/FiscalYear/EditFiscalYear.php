<?php

namespace App\Livewire\FiscalYear;

use App\Models\FiscalYear;
use Livewire\Attributes\Locked;
use Livewire\Component;

class EditFiscalYear extends Component
{
    #[Locked]
    public $id;

    public $start_date;

    public $end_date;

    public function mount($year_id = null)
    {
        $this->id = $year_id;
        if ($this->id) {
            // edit
            $fiscal_year = FiscalYear::find($this->id);
            $this->start_date = $fiscal_year->start_date->format('Y-m-d');
            $this->end_date = $fiscal_year->end_date->format('Y-m-d');
        } else {
            // create with suggestions
            $lastYear = FiscalYear::orderBy('end_date', 'desc')->limit(1)->first();
            if ($lastYear) {
                $this->start_date = $lastYear->end_date->addDay()->format('Y-m-d');
                $this->end_date = $lastYear->end_date->addYear()->format('Y-m-d');
            }
        }
    }

    public function rules(): array
    {
        return [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ];
    }

    public function save()
    {
        $this->validate();
        FiscalYear::updateOrCreate([
            'id' => $this->id,
        ], [
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ]);
        $this->redirect(route('budget-plan.index'));
    }

    public function render()
    {
        return view('livewire.fiscal-year.edit-fiscal-year');
    }
}
