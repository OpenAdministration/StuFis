<?php

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \SocialiteProviders\Manager\ServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
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
            Route::middleware('legacy')
                ->group(base_path('routes/legacy.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectUsersTo(AppServiceProvider::HOME);

        $middleware->throttleApi();

        $middleware->group('legacy', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
