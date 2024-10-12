<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix the db index length for migrations
        Schema::defaultStringLength(191);

        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }
}
