<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 12.05.18
 * Time: 21:41
 */

namespace forms\projekte\exceptions;

use Throwable;

class OldFormException extends \Exception{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null){
        parent::__construct($message, $code, $previous);
    }
}