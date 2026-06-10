<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;

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

        // L'administrateur a un accès total : il passe toutes les vérifications de permission
        // (routes via le middleware Spatie `permission:` → canAny() → Gate, ainsi que @can / Gate::allows).
        Gate::before(function ($user, $ability) {
            return method_exists($user, 'isAdmin') && $user->isAdmin() ? true : null;
        });
    }
}
