<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Cart\MerchantCartManager;
use App\Services\Cart\CartStorage;
use App\Services\Cart\StockReservation;
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
        // Cart Storage and Stock Reservation
        $this->app->singleton(CartStorage::class);
        $this->app->singleton(StockReservation::class);

        // MerchantCartManager is the NEW cart service (replaces old MerchantCartService)
        $this->app->singleton(MerchantCartManager::class, function ($app) {
            return new MerchantCartManager(
                $app->make(CartStorage::class),
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
