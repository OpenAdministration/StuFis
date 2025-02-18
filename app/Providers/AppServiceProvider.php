<?php

namespace App\Providers;

use App\Services\Auth\AuthService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //

        $this->registerAuth();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('stufis.features') === 'dev') {
            $this->loadMigrationsFrom([
                base_path('database/migrations/dev'),
                base_path('database/migrations/preview'),
            ]);
        }
        if (config('stufis.features') === 'preview') {
            $this->loadMigrationsFrom(base_path('database/migrations/preview'));
        }

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('stumv', \SocialiteProviders\LaravelPassport\Provider::class);
        });

        $this->bootRoute();
    }

    public function bootRoute()
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

    }

    public function registerAuth(): void
    {
        $this->app->singleton(AuthService::class, function (Application $application) {
            $serviceName = ucfirst(strtolower((string) config('auth.service')));
            // weird to escape, but correct
            $classPath = "\App\Services\Auth\\{$serviceName}AuthService";
            if (class_exists($classPath)) {
                return new $classPath;
            }

            abort(500, 'Config Error. Wrong Auth provider given in Environment. Fitting AuthService Class not found');
        });
    }
}
