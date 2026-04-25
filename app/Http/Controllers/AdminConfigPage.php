<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Contracts\View\View;

class AdminConfigPage extends Controller
{

    public function render(): View
    {
        $config = Setting::toMap();
        return view('components.dump', ['dump' => $config]);
    }
}
