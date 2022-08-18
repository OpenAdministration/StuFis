<?php

namespace App\Exceptions;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;
use Throwable;

class LegacyDieException extends Exception
{
    public string $debug;

    public function __construct(string $message = "", int $code = 0, string $debug = '', ?Throwable $previous = null)
    {
        $this->debug = $debug;
        parent::__construct($message, $code, $previous);
    }

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
