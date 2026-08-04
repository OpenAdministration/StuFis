<?php

namespace App\Providers;

use App\Exports\Datev\DatevExport;
use App\Extensions\Session\OidcDatabaseSessionHandler;
use App\Policies\DatevExportPolicy;
use App\Services\Auth\AuthService;
use App\Support\Money\MoneySynth;
use Cknow\Money\Money;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use SocialiteProviders\LaravelPassport\Provider as PassportProvider;
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
            $event->extendSocialite('stumv', PassportProvider::class);
        });

        $this->bootSession();

        // Layouts live in resources/views/layout (outside the components dir);
        // expose them as anonymous components: <x-layout::app>, <x-layout::error>.
        Blade::anonymousComponentPath(resource_path('views/layout'), 'layout');

        $this->bootRoute();

        $this->bootMoney();

        // DatevExport is not an Eloquent model, so its policy needs registering by hand.
        Gate::policy(DatevExport::class, DatevExportPolicy::class);

        // Feed spatie's per-request CSP nonce to Vite so Livewire and Flux (both
        // fall back to Vite::cspNonce()) tag their inline <script>/<style> with the
        // same nonce as the Content-Security-Policy header. Without this the
        // enforcing policy blocks the inline @livewireScriptConfig and Livewire
        // never boots.
        Vite::useCspNonce(resolve('csp-nonce'));

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
        $this->app->singleton(function (Application $application): AuthService {
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

    /**
     * Back the `database` session driver with a handler that also stores the
     * OIDC `sid` in an indexed column, so OIDC Back-Channel Logout can locate
     * and destroy a session by its `sid`. Overriding the built-in `database`
     * creator (SessionManager checks custom creators first) means this applies
     * transparently whenever SESSION_DRIVER=database, without a bespoke driver
     * name leaking into config.
     */
    private function bootSession(): void
    {
        Session::extend('database', function (Application $app): OidcDatabaseSessionHandler {
            $table = $app['config']->get('session.table', 'sessions');
            $lifetime = $app['config']->get('session.lifetime', 120);
            $connection = $app['db']->connection($app['config']->get('session.connection'));

            return new OidcDatabaseSessionHandler($connection, $table, $lifetime, $app);
        });
    }
}
