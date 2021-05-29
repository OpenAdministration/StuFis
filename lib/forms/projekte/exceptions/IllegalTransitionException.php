<?php

namespace forms\projekte\exceptions;

use Throwable;

class IllegalTransitionException extends \Exception{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null){
        parent::__construct($message, $code, $previous);
    }
}