<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Legacy\Project;
use framework\auth\AuthHandler;

class DeleteProject extends Controller
{
    public function __invoke(int $project_id)
    {
        $project = Project::findOrFail($project_id);

        // authorize
        $userPerm = AuthHandler::getInstance()->hasGroup('ref-finanzen-hv')
            || $project->creator->id === \Auth::user()->id;
        $dataPerm = $project->expenses()->count() === 0;

        if ($userPerm === false || $dataPerm === false) {
            abort(403);
        }

        // delete
        $project->posts()->delete();
        $project->delete();

        return redirect()->route('legacy.dashboard', ['sub' => 'mygremium']);
    }
}
