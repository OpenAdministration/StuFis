<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 01.05.18
 * Time: 02:54
 */


class IllegalStateException extends Exception{
    public function __construct(string $message = "", int $code = 0, Throwable $previous = null){
        parent::__construct($message, $code, $previous);
    }
}
