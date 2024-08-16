<?php

namespace App\Http\Controllers;

class Dev extends Controller
{
    public function groups()
    {
        $groups = \Auth::user()?->getGroups();

        return view('components.dump', ['dump' => $groups]);
    }
}
