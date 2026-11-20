<?php

namespace App\Providers;

use App\Models\Aviso;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // En producción (Railway) todas las URL se generan con https.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // CU41 (RF-82): contador de avisos sin leer en la barra lateral. Se calcula una sola vez
        // por página, al dibujar layouts.navigation, y no en cada vista.
        View::composer('layouts.navigation', function ($view) {
            $view->with('avisosSinLeer', Aviso::sinLeer(auth()->id()));
        });
    }
}
