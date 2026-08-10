<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Legacy\Project;
use App\Models\User;

class DeleteProject extends Controller
{
    public function __invoke(int $project_id)
    {
        $project = Project::findOrFail($project_id);

        // authorize
        $userPerm = Auth::user()->can('budget-officer', User::class)
            || $project->creator->id === Auth::user()->id;
        $dataPerm = $project->expenses()->count() === 0;

        abort_if($userPerm === false || $dataPerm === false, 403);

        // delete
        $project->posts()->delete();
        $project->delete();

        return to_route('legacy.dashboard', ['sub' => 'mygremium']);
    }
}
