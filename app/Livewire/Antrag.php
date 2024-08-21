<?php

namespace App\Livewire;

use App\Http\Requests\AntragRequest;
use Livewire\Component;

class Antrag extends Component
{
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

    public function render(int $site = 1)
    {
        return view("livewire.antrag.$site");
    }
}
