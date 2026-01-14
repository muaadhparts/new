<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\MerchantCheckout\MerchantCartService;
use App\Services\MerchantCheckout\MerchantSessionManager;
use App\Services\MerchantCheckout\MerchantPriceCalculator;
use App\Services\MerchantCheckout\MerchantCheckoutService;
use App\Services\MerchantCheckout\MerchantPurchaseCreator;
use App\Services\PaymentAccountingService;

class MerchantCheckoutServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register as singletons for consistency within a request
        $this->app->singleton(MerchantCartService::class);
        $this->app->singleton(MerchantSessionManager::class);
        $this->app->singleton(MerchantPriceCalculator::class);

        $this->app->singleton(MerchantCheckoutService::class, function ($app) {
            return new MerchantCheckoutService(
                $app->make(MerchantCartService::class),
                $app->make(MerchantSessionManager::class),
                $app->make(MerchantPriceCalculator::class)
            );
        });

        $this->app->singleton(MerchantPurchaseCreator::class, function ($app) {
            return new MerchantPurchaseCreator(
                $app->make(MerchantCartService::class),
                $app->make(MerchantSessionManager::class),
                $app->make(MerchantPriceCalculator::class),
                $app->make(PaymentAccountingService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
