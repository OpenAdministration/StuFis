<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class LegacyController extends Controller
{
    public function __invoke()
    {
        ob_start();
        require dirname(__FILE__,4) . '/legacy/www/index.php';
        $output = ob_get_clean();
        return view('components.layout', ['legacy' => $output, 'legacySupport' => true]);
        //return new Response($output);
    }
}
