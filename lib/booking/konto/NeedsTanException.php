<?php


namespace booking\konto;

use Fhp\BaseAction;
use Throwable;

class NeedsTanException extends \RuntimeException
{
    private $fintsAction;

    public function __construct(string $message, BaseAction $baseAction, Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->fintsAction = $baseAction;
    }

    public function getAction() : BaseAction
    {
        return $this->fintsAction;
    }

}