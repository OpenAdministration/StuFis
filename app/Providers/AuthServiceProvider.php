<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\Auth\AuthService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuthService::class, function (Application $application) {
            $serviceName = ucfirst(strtolower(config('auth.service')));
            // weird to escape, but correct
            $classPath = "\App\Services\Auth\\{$serviceName}AuthService";
            if (class_exists($classPath)) {
                return new $classPath;
            }

            abort(500, 'Config Error. Wrong Auth provider given in Environment. Fitting AuthService Class not found');
        });
    }

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
