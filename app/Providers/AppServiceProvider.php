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

        // Accès entièrement piloté par les permissions : aucun rôle ne bénéficie d'un
        // bypass global. L'administrateur gère lui-même ses permissions (il peut
        // s'accorder les modules métier via la matrice des rôles).
    }
}
