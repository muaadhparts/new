<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Commerce\Services\Cart\MerchantCartManager;
use App\Domain\Commerce\Services\Cart\StockReservation;
use App\Domain\Commerce\Services\MerchantCheckout\MerchantSessionManager;
use App\Domain\Commerce\Services\MerchantCheckout\MerchantPriceCalculator;
use App\Domain\Commerce\Services\MerchantCheckout\MerchantCheckoutService;
use App\Domain\Commerce\Services\MerchantCheckout\MerchantPurchaseCreator;
use App\Domain\Accounting\Services\PaymentAccountingService;

class MerchantCheckoutServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Stock Reservation
        $this->app->singleton(StockReservation::class);

        // MerchantCartManager - Single Source of Truth for cart
        $this->app->singleton(MerchantCartManager::class, function ($app) {
            return new MerchantCartManager(
                $app->make(StockReservation::class)
            );
        });

        $this->app->singleton(MerchantSessionManager::class);
        $this->app->singleton(MerchantPriceCalculator::class);

        $this->app->singleton(MerchantCheckoutService::class, function ($app) {
            return new MerchantCheckoutService(
                $app->make(MerchantCartManager::class),
                $app->make(MerchantSessionManager::class),
                $app->make(MerchantPriceCalculator::class)
            );
        });

        $this->app->singleton(MerchantPurchaseCreator::class, function ($app) {
            return new MerchantPurchaseCreator(
                $app->make(MerchantCartManager::class),
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
