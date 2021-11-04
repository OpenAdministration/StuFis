<?php


namespace booking\konto;

use Fhp\BaseAction;
use Fhp\Model\TanRequest;
use JetBrains\PhpStorm\Pure;
use Throwable;

class NeedsTanException extends \RuntimeException
{
    private BaseAction $fintsAction;

    /**
     * NeedsTanException constructor.
     * @param string $message
     * @param BaseAction $baseAction
     * @param Throwable|null $previous
     */
    #[Pure]
    public function __construct(BaseAction $baseAction, string $message = 'TAN wird benötigt', Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->fintsAction = $baseAction;
    }

    public function getAction() : BaseAction
    {
        return $this->fintsAction;
    }

    /**
     * @return TanRequest
     */
    public function getTanRequest() : TanRequest
    {
        return $this->fintsAction->getTanRequest();
    }

}