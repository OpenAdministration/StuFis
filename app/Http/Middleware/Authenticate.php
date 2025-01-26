<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        // do it like in the parent
        $this->authenticate($request, $guards);

        // adds to parent: only user with the login or admin group pass
        $groups = \Auth::user()->getGroups();
        if ($groups->contains('login') || $groups->contains('admin')) {
            return $next($request);
        }
        // otherwise: non-pass
        $this->unauthenticated($request, $guards);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): string
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }
}
