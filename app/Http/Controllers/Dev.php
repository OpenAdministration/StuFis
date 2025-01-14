<?php

namespace App\Http\Controllers;

use App\Services\Auth\AuthService;

class Dev extends Controller
{
    public function groups()
    {
        $groupsRaw = \App::get(AuthService::class)->userGroupsRaw();
        $groupMapping = \App::get(AuthService::class)->groupMapping();
        $groups = \Auth::user()?->getGroups();

        return view('components.dump', ['dump' => [
            'groups-raw' => $groupsRaw,
            'groupMapping' => $groupMapping,
            'groups' => $groups,
        ]]);
    }
}
