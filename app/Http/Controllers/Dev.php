<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Dev extends Controller
{
    function groups()
    {
        $groups = \Auth::user()?->getGroups();
        return view('components.dump', ['dump' => $groups]);
    }
}
