<?php

namespace App\Exceptions;

use Exception;

class LegacyJsonException extends Exception
{
    public function __construct(public mixed $content)
    {
        parent::__construct('', 200, null);
    }
}
