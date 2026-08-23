<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

/**
 * Carries a finished file download out of a legacy Renderer.
 *
 * A legacy page renders into an output buffer that LegacyController hands to the app layout,
 * so a handler cannot simply echo binary content - it would end up inside the HTML. Throwing
 * unwinds to the controller, which drops the buffer and returns the response untouched.
 */
class LegacyDownloadException extends Exception
{
    public function __construct(public Response $response)
    {
        parent::__construct();
    }
}
