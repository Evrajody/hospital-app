<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // Forcer HTTPS dans les environnements de production
        if (in_array(config('app.env'), ['preprod', 'production', 'frontoffice', 'backoffice'])) {
            URL::forceScheme('https');
        }
    }
}
