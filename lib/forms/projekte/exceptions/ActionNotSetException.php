<?php

namespace forms\projekte\exceptions;

use Throwable;

class ActionNotSetException extends \Exception{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null){
        parent::__construct($message, $code, $previous);
    }
}