<?php

namespace App\Providers;

use App\Services\Auth\AuthService;
use App\Support\Money\MoneySynth;
use Cknow\Money\Money;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
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

        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('stumv', \SocialiteProviders\LaravelPassport\Provider::class);
        });

        $this->bootRoute();

        $this->bootMoney();

        // Carbon::setLocale(config('app.locale'));
    }

    public function bootRoute()
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

        // Make sure, this vars cannot be matched with strings
        // prevents wrong routing
        Route::pattern('hhp_id', '[0-9]+');
        Route::pattern('konto_id', '[0-9]+');
        Route::pattern('titel_id', '[0-9]+');
        Route::pattern('projekt_id', '[0-9]+');
        Route::pattern('auslagen_id', '[0-9]+');
        Route::pattern('credential_id', '[0-9]+');
        Route::pattern('year_id', '[0-9]+');
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

    private function bootMoney(): void
    {
        Livewire::propertySynthesizer(MoneySynth::class);
        Builder::macro('sumMoney', fn (string $column): Money => Money::EUR($this->sum($column)));
    }
}
