<?php

namespace App\Http\Controllers;


use Illuminate\Http\Response;

class LegacyController extends Controller
{
    public function __invoke(): Response
    {
        ob_start();
        require dirname(__FILE__,4) . '/legacy/www/index.php';
        $output = ob_get_clean();

        // be sure to import Illuminate\Http\Response
        return new Response($output);
    }
}
