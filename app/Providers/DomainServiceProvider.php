<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

// Platform Contracts
use App\Domain\Platform\Contracts\MonetaryUnitInterface;
use App\Domain\Platform\Contracts\PlatformSettingsInterface;
use App\Domain\Platform\Services\MonetaryUnitService;
use App\Domain\Platform\Services\PlatformSettingsService;

// Accounting Contracts
use App\Domain\Accounting\Contracts\AccountLedgerInterface;
use App\Domain\Accounting\Services\AccountLedgerService;

// Catalog Contracts
use App\Domain\Catalog\Contracts\CatalogFilterInterface;
use App\Domain\Catalog\Contracts\CategoryTreeInterface;
use App\Domain\Catalog\Services\CatalogItemFilterService;
use App\Domain\Catalog\Services\NewCategoryTreeService;

// Shipping Contracts
use App\Domain\Shipping\Contracts\ShippingCalculatorInterface;
use App\Domain\Shipping\Contracts\ShipmentTrackingInterface;
use App\Domain\Shipping\Services\ShippingCalculatorService;
use App\Domain\Shipping\Services\ShipmentTrackingService;

// Commerce Contracts
use App\Domain\Commerce\Contracts\CartInterface;
use App\Domain\Commerce\Services\Cart\MerchantCartManager;

// Merchant Contracts
use App\Domain\Merchant\Contracts\MerchantCredentialInterface;
use App\Domain\Merchant\Services\MerchantCredentialService;

/**
 * DomainServiceProvider - Binds Domain Contracts to Implementations
 *
 * This provider registers all Domain service bindings for dependency injection.
 * Use interfaces in controllers and other services for better testability.
 */
class DomainServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array<string, string>
     */
    public array $bindings = [
        // Platform
        MonetaryUnitInterface::class => MonetaryUnitService::class,
        PlatformSettingsInterface::class => PlatformSettingsService::class,

        // Accounting
        AccountLedgerInterface::class => AccountLedgerService::class,

        // Catalog
        CatalogFilterInterface::class => CatalogItemFilterService::class,
        CategoryTreeInterface::class => NewCategoryTreeService::class,

        // Shipping
        ShippingCalculatorInterface::class => ShippingCalculatorService::class,
        ShipmentTrackingInterface::class => ShipmentTrackingService::class,

        // Commerce
        CartInterface::class => MerchantCartManager::class,

        // Merchant
        MerchantCredentialInterface::class => MerchantCredentialService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bindings are auto-registered via $bindings property
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
