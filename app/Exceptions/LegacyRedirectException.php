<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\RedirectResponse;

class LegacyRedirectException extends Exception
{
    public function __construct(public RedirectResponse $redirect)
    {
        parent::__construct();
    }
}
