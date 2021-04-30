<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 01.05.18
 * Time: 02:54
 */

namespace forms\projekte\exceptions;

use Throwable;

class IllegalTransitionException extends \Exception{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null){
        parent::__construct($message, $code, $previous);
    }
}