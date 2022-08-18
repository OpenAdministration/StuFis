<?php

namespace App\Http\Controllers;

class LegacyController extends Controller
{
    public function __invoke()
    {
        try {
            ob_start();
            require dirname(__FILE__,4) . '/legacy/www/index.php';
            $output = ob_get_clean();
            return view('legacy.main', ['content' => $output]);
        } catch (\Exception $exception){
            // get rid of the already printed html
            ob_get_clean();
            throw $exception;
        }
    }
}
