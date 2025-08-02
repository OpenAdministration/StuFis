<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\VersionChangeNotification;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            if (config('stufis.features') === 'dev') {
                Route::middleware('web')
                    ->group(base_path('routes/web-dev.php'));
                Route::middleware('web')
                    ->group(base_path('routes/web-preview.php'));
            }

            if (config('stufis.features') === 'preview') {
                Route::middleware('web')
                    ->group(base_path('routes/web-preview.php'));
            }

            if (App::hasDebugModeEnabled()) {
                Route::middleware('web')
                    ->group(base_path('routes/web-debug.php'));
            }

            // has to be last because there is a catch-all inside
            Route::middleware('web')
                ->withoutMiddleware(VerifyCsrfToken::class)
                ->group(base_path('routes/legacy.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectUsersTo('/');

        $middleware->throttleApi();

        $middleware->alias([
            'auth' => Authenticate::class,
        ]);
        $middleware->appendToGroup('web', VersionChangeNotification::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
