<?php

namespace App\Exceptions;

use Exception;

class LegacyJsonException extends Exception
{

    public mixed $content;
    public function __construct(mixed $content)
    {
        $this->content = $content;
        parent::__construct("", 200, null);
    }
}
