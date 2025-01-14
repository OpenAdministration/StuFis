<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        // adds to parent: only user with the login group can authenticate
        if (! \Auth::user()?->getGroups()->contains('login')) {
            $this->unauthenticated($request, $guards);
        }

        return $next($request);
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
