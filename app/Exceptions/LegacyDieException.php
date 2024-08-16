<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class LegacyDieException extends Exception
{
    public string $debug;

    public function __construct(int $code = 0, string $message = '', string $debug = '', ?Throwable $previous = null)
    {
        $this->debug = $debug;
        parent::__construct($message, $code, $previous);
    }
}
