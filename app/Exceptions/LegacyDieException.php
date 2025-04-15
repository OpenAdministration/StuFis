<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class LegacyDieException extends Exception
{
    public function __construct(int $code = 0, string $message = '', public string $debug = '', ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
