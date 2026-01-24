<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;

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
 * Regression Tests for Domain Contracts
 *
 * Phase 9: Domain Contracts
 *
 * This test ensures that domain contracts are properly bound to implementations.
 */
class DomainContractsTest extends TestCase
{
    // =========================================================================
    // PLATFORM CONTRACTS
    // =========================================================================

    /** @test */
    public function monetary_unit_interface_resolves_to_service()
    {
        $service = app(MonetaryUnitInterface::class);
        $this->assertInstanceOf(MonetaryUnitService::class, $service);
    }

    /** @test */
    public function platform_settings_interface_resolves_to_service()
    {
        $service = app(PlatformSettingsInterface::class);
        $this->assertInstanceOf(PlatformSettingsService::class, $service);
    }

    /** @test */
    public function monetary_unit_interface_has_required_methods()
    {
        $interface = MonetaryUnitInterface::class;

        $this->assertTrue(method_exists($interface, 'getCurrent'));
        $this->assertTrue(method_exists($interface, 'getDefault'));
        $this->assertTrue(method_exists($interface, 'getByCode'));
        $this->assertTrue(method_exists($interface, 'convert'));
        $this->assertTrue(method_exists($interface, 'format'));
        $this->assertTrue(method_exists($interface, 'convertAndFormat'));
    }

    /** @test */
    public function platform_settings_interface_has_required_methods()
    {
        $interface = PlatformSettingsInterface::class;

        $this->assertTrue(method_exists($interface, 'get'));
        $this->assertTrue(method_exists($interface, 'set'));
        $this->assertTrue(method_exists($interface, 'has'));
        $this->assertTrue(method_exists($interface, 'all'));
    }

    // =========================================================================
    // ACCOUNTING CONTRACTS
    // =========================================================================

    /** @test */
    public function account_ledger_interface_resolves_to_service()
    {
        $service = app(AccountLedgerInterface::class);
        $this->assertInstanceOf(AccountLedgerService::class, $service);
    }

    /** @test */
    public function account_ledger_interface_has_required_methods()
    {
        $interface = AccountLedgerInterface::class;

        $this->assertTrue(method_exists($interface, 'getPlatformParty'));
        $this->assertTrue(method_exists($interface, 'getMerchantParty'));
        $this->assertTrue(method_exists($interface, 'recordDebt'));
        $this->assertTrue(method_exists($interface, 'recordSettlement'));
        $this->assertTrue(method_exists($interface, 'getPartySummary'));
    }

    // =========================================================================
    // CATALOG CONTRACTS
    // =========================================================================

    /** @test */
    public function catalog_filter_interface_resolves_to_service()
    {
        $service = app(CatalogFilterInterface::class);
        $this->assertInstanceOf(CatalogItemFilterService::class, $service);
    }

    /** @test */
    public function category_tree_interface_resolves_to_service()
    {
        $service = app(CategoryTreeInterface::class);
        $this->assertInstanceOf(NewCategoryTreeService::class, $service);
    }

    /** @test */
    public function catalog_filter_interface_has_required_methods()
    {
        $interface = CatalogFilterInterface::class;

        $this->assertTrue(method_exists($interface, 'buildCatalogItemQuery'));
        $this->assertTrue(method_exists($interface, 'applyCatalogItemFilters'));
        $this->assertTrue(method_exists($interface, 'getFilterSidebarData'));
    }

    /** @test */
    public function category_tree_interface_has_required_methods()
    {
        $interface = CategoryTreeInterface::class;

        $this->assertTrue(method_exists($interface, 'getDescendantIds'));
        $this->assertTrue(method_exists($interface, 'buildCategoryTree'));
        $this->assertTrue(method_exists($interface, 'getBreadcrumb'));
    }

    // =========================================================================
    // SHIPPING CONTRACTS
    // =========================================================================

    /** @test */
    public function shipping_calculator_interface_resolves_to_service()
    {
        $service = app(ShippingCalculatorInterface::class);
        $this->assertInstanceOf(ShippingCalculatorService::class, $service);
    }

    /** @test */
    public function shipment_tracking_interface_resolves_to_service()
    {
        $service = app(ShipmentTrackingInterface::class);
        $this->assertInstanceOf(ShipmentTrackingService::class, $service);
    }

    /** @test */
    public function shipping_calculator_interface_has_required_methods()
    {
        $interface = ShippingCalculatorInterface::class;

        $this->assertTrue(method_exists($interface, 'calculateVolumetricWeight'));
        $this->assertTrue(method_exists($interface, 'calculateChargeableWeight'));
        $this->assertTrue(method_exists($interface, 'getBranchCity'));
    }

    /** @test */
    public function shipment_tracking_interface_has_required_methods()
    {
        $interface = ShipmentTrackingInterface::class;

        $this->assertTrue(method_exists($interface, 'createTrackingRecord'));
        $this->assertTrue(method_exists($interface, 'createApiShipment'));
        $this->assertTrue(method_exists($interface, 'getCurrentStatus'));
        $this->assertTrue(method_exists($interface, 'cancelShipment'));
    }

    // =========================================================================
    // COMMERCE CONTRACTS
    // =========================================================================

    /** @test */
    public function cart_interface_resolves_to_service()
    {
        $service = app(CartInterface::class);
        $this->assertInstanceOf(MerchantCartManager::class, $service);
    }

    /** @test */
    public function cart_interface_has_required_methods()
    {
        $interface = CartInterface::class;

        $this->assertTrue(method_exists($interface, 'getItems'));
        $this->assertTrue(method_exists($interface, 'addItem'));
        $this->assertTrue(method_exists($interface, 'removeItem'));
        $this->assertTrue(method_exists($interface, 'clear'));
        $this->assertTrue(method_exists($interface, 'getTotal'));
    }

    // =========================================================================
    // MERCHANT CONTRACTS
    // =========================================================================

    /** @test */
    public function merchant_credential_interface_resolves_to_service()
    {
        $service = app(MerchantCredentialInterface::class);
        $this->assertInstanceOf(MerchantCredentialService::class, $service);
    }

    /** @test */
    public function merchant_credential_interface_has_required_methods()
    {
        $interface = MerchantCredentialInterface::class;

        $this->assertTrue(method_exists($interface, 'get'));
        $this->assertTrue(method_exists($interface, 'set'));
        $this->assertTrue(method_exists($interface, 'has'));
        $this->assertTrue(method_exists($interface, 'delete'));
    }

    // =========================================================================
    // INTERFACE EXISTENCE TESTS
    // =========================================================================

    /** @test */
    public function all_domain_contracts_exist()
    {
        $this->assertTrue(interface_exists(MonetaryUnitInterface::class));
        $this->assertTrue(interface_exists(PlatformSettingsInterface::class));
        $this->assertTrue(interface_exists(AccountLedgerInterface::class));
        $this->assertTrue(interface_exists(CatalogFilterInterface::class));
        $this->assertTrue(interface_exists(CategoryTreeInterface::class));
        $this->assertTrue(interface_exists(ShippingCalculatorInterface::class));
        $this->assertTrue(interface_exists(ShipmentTrackingInterface::class));
        $this->assertTrue(interface_exists(CartInterface::class));
        $this->assertTrue(interface_exists(MerchantCredentialInterface::class));
    }
}
