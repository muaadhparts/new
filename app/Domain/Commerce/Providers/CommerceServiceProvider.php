<?php

namespace App\Domain\Commerce\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Commerce\Observers\PurchaseObserver;
use App\Domain\Commerce\Observers\MerchantPurchaseObserver;

/**
 * Commerce Domain Service Provider
 *
 * Registers commerce-specific services, observers, and policies.
 */
class CommerceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register commerce-specific bindings
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
        Purchase::observe(PurchaseObserver::class);
        MerchantPurchase::observe(MerchantPurchaseObserver::class);
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
