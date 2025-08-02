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

    public function markdown()
    {
        return view('debug.markdown')->layout('components.layouts.index');
    }

    public function fontWeight()
    {
        return view('debug.font-weight', ['text' => 'Sphinx of black quartz, judge my vow.']);
    }

    public function showMiddleware()
    {
        $route = request()->route();
        $middlewares = $route->gatherMiddleware();

        return view('components.dump', ['dump' => [$middlewares]]);
    }
}
