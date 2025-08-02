<?php

namespace App\Http\Controllers;

class ViewChangelog extends Controller
{
    public function __invoke()
    {
        $changelog = file_get_contents(base_path('docs/feature-changelog.md'));

        return view('changelog', ['changelogs' => $changelog]);
    }
}
