<?php

namespace App\Providers;

use App\Contracts\WhatsAppLinkGenerator;
use App\Services\WaMeLinkGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Ficha CI-COD-08: se vincula el contrato WhatsAppLinkGenerator con su
     * implementación concreta para permitir inyección de dependencias
     * y sustituirla por un doble de prueba (Mockery) en las pruebas unitarias.
     */
    public function register(): void
    {
        $this->app->singleton(WhatsAppLinkGenerator::class, WaMeLinkGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
