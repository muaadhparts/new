<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * ShippingQuoteServiceProvider
 *
 * Registers shipping quote routes and services.
 */
class ShippingQuoteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load shipping quote API routes
        $this->loadRoutesFrom(base_path('routes/shipping-quote-api.php'));
    }
}
