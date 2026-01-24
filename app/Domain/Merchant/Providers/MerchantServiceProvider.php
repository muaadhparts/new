<?php

namespace App\Domain\Merchant\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Models\MerchantBranch;
use App\Domain\Merchant\Observers\MerchantItemObserver;
use App\Domain\Merchant\Observers\MerchantBranchObserver;

/**
 * Merchant Domain Service Provider
 *
 * Registers merchant-specific services, observers, and policies.
 */
class MerchantServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register merchant-specific bindings
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
        MerchantItem::observe(MerchantItemObserver::class);
        MerchantBranch::observe(MerchantBranchObserver::class);
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
