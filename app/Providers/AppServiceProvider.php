<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if(config('stufis.features') === 'dev'){
            $this->loadMigrationsFrom([
                base_path('database/migrations/dev'),
                base_path('database/migrations/preview')
            ]);
        }
        if(config('stufis.features') === 'preview'){
            $this->loadMigrationsFrom(base_path('database/migrations/preview'));
        }
    }
}
