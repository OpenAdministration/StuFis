<?php

namespace App\Exceptions;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class LegacyRedirectException extends Exception
{
    public function __construct(public RedirectResponse $redirect)
    {
        parent::__construct();
    }
}
