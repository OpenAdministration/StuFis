<?php

namespace App\Exceptions;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;

class LegacyDieException extends Exception
{

    /**
     * Render the exception into an HTTP response.
     *
     * @param Request $request
     * @return Response
     */
    public function render(Request $request) : Response
    {
        return response()->view('legacy.error', ['e' => $this]);
    }
}
