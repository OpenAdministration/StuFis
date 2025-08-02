<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class VersionChangeNotification
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (($user = \Auth::user()) !== null) {
            $lastVersion = $user->version;
            if ($lastVersion !== config('stufis.version')) {
                Session::put('message.text', __('general.version_notification.text'));
                Session::put('message.type', 'update');
                $user->update(['version' => config('stufis.version')]);
            }
        }

        return $next($request);
    }
}
