<?php

namespace App\Domain\Shipping\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Observers\ShipmentTrackingObserver;
use App\Domain\Shipping\Observers\ShipmentObserver;

/**
 * Shipping Domain Service Provider
 *
 * Registers shipping-specific services, observers, and policies.
 */
class ShippingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register shipping-specific bindings
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerObservers();
    }

    /**
     * Register model observers.
     */
    protected function registerObservers(): void
    {
        ShipmentTracking::observe(ShipmentTrackingObserver::class);
        Shipment::observe(ShipmentObserver::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [];
    }
}
