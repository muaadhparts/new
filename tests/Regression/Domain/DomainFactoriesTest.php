<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;
use App\Domain\Catalog\Factories\CatalogItemFactory;
use App\Domain\Catalog\Factories\BrandFactory;
use App\Domain\Catalog\Factories\CategoryFactory;
use App\Domain\Catalog\Factories\CatalogReviewFactory;
use App\Domain\Merchant\Factories\MerchantItemFactory;
use App\Domain\Merchant\Factories\MerchantBranchFactory;
use App\Domain\Merchant\Factories\MerchantSettingFactory;
use App\Domain\Commerce\Factories\PurchaseFactory;
use App\Domain\Commerce\Factories\MerchantPurchaseFactory;
use App\Domain\Identity\Factories\UserFactory;
use App\Domain\Identity\Factories\OperatorFactory;
use App\Domain\Identity\Factories\OperatorRoleFactory;
use App\Domain\Shipping\Factories\ShipmentTrackingFactory;
use App\Domain\Shipping\Factories\CourierFactory;
use App\Domain\Shipping\Factories\CityFactory;
use App\Domain\Shipping\Factories\CountryFactory;
use App\Domain\Accounting\Factories\AccountBalanceFactory;
use App\Domain\Accounting\Factories\WithdrawFactory;
use App\Domain\Accounting\Factories\AccountingLedgerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Phase 29: Domain Factories Tests
 *
 * Tests for model factories across domains.
 */
class DomainFactoriesTest extends TestCase
{
    // ============================================
    // Catalog Domain Factories
    // ============================================

    /** @test */
    public function catalog_item_factory_exists()
    {
        $this->assertTrue(class_exists(CatalogItemFactory::class));
    }

    /** @test */
    public function catalog_item_factory_extends_factory()
    {
        $this->assertTrue(is_subclass_of(CatalogItemFactory::class, Factory::class));
    }

    /** @test */
    public function catalog_item_factory_has_definition_method()
    {
        $this->assertTrue(method_exists(CatalogItemFactory::class, 'definition'));
    }

    /** @test */
    public function catalog_item_factory_has_state_methods()
    {
        $factory = new CatalogItemFactory();
        $this->assertTrue(method_exists($factory, 'active'));
        $this->assertTrue(method_exists($factory, 'inactive'));
        $this->assertTrue(method_exists($factory, 'forBrand'));
        $this->assertTrue(method_exists($factory, 'forCategory'));
    }

    /** @test */
    public function brand_factory_exists()
    {
        $this->assertTrue(class_exists(BrandFactory::class));
    }

    /** @test */
    public function brand_factory_has_state_methods()
    {
        $factory = new BrandFactory();
        $this->assertTrue(method_exists($factory, 'active'));
        $this->assertTrue(method_exists($factory, 'inactive'));
        $this->assertTrue(method_exists($factory, 'withLogo'));
        $this->assertTrue(method_exists($factory, 'popular'));
    }

    /** @test */
    public function new_category_factory_exists()
    {
        $this->assertTrue(class_exists(CategoryFactory::class));
    }

    /** @test */
    public function new_category_factory_has_hierarchy_methods()
    {
        $factory = new CategoryFactory();
        $this->assertTrue(method_exists($factory, 'root'));
        $this->assertTrue(method_exists($factory, 'childOf'));
        $this->assertTrue(method_exists($factory, 'autoParts'));
    }

    /** @test */
    public function catalog_review_factory_exists()
    {
        $this->assertTrue(class_exists(CatalogReviewFactory::class));
    }

    /** @test */
    public function catalog_review_factory_has_rating_states()
    {
        $factory = new CatalogReviewFactory();
        $this->assertTrue(method_exists($factory, 'pending'));
        $this->assertTrue(method_exists($factory, 'approved'));
        $this->assertTrue(method_exists($factory, 'rejected'));
        $this->assertTrue(method_exists($factory, 'highRating'));
        $this->assertTrue(method_exists($factory, 'lowRating'));
    }

    // ============================================
    // Merchant Domain Factories
    // ============================================

    /** @test */
    public function merchant_item_factory_exists()
    {
        $this->assertTrue(class_exists(MerchantItemFactory::class));
    }

    /** @test */
    public function merchant_item_factory_has_stock_states()
    {
        $factory = new MerchantItemFactory();
        $this->assertTrue(method_exists($factory, 'inStock'));
        $this->assertTrue(method_exists($factory, 'outOfStock'));
        $this->assertTrue(method_exists($factory, 'lowStock'));
        $this->assertTrue(method_exists($factory, 'withDiscount'));
    }

    /** @test */
    public function merchant_branch_factory_exists()
    {
        $this->assertTrue(class_exists(MerchantBranchFactory::class));
    }

    /** @test */
    public function merchant_branch_factory_has_location_states()
    {
        $factory = new MerchantBranchFactory();
        $this->assertTrue(method_exists($factory, 'main'));
        $this->assertTrue(method_exists($factory, 'secondary'));
        $this->assertTrue(method_exists($factory, 'inRiyadh'));
        $this->assertTrue(method_exists($factory, 'inJeddah'));
    }

    /** @test */
    public function merchant_setting_factory_exists()
    {
        $this->assertTrue(class_exists(MerchantSettingFactory::class));
    }

    /** @test */
    public function merchant_setting_factory_has_configuration_states()
    {
        $factory = new MerchantSettingFactory();
        $this->assertTrue(method_exists($factory, 'withMinOrder'));
        $this->assertTrue(method_exists($factory, 'withFreeShipping'));
        $this->assertTrue(method_exists($factory, 'autoAccept'));
        $this->assertTrue(method_exists($factory, 'withBranding'));
    }

    // ============================================
    // Commerce Domain Factories
    // ============================================

    /** @test */
    public function purchase_factory_exists()
    {
        $this->assertTrue(class_exists(PurchaseFactory::class));
    }

    /** @test */
    public function purchase_factory_has_status_states()
    {
        $factory = new PurchaseFactory();
        $this->assertTrue(method_exists($factory, 'pending'));
        $this->assertTrue(method_exists($factory, 'confirmed'));
        $this->assertTrue(method_exists($factory, 'processing'));
        $this->assertTrue(method_exists($factory, 'shipped'));
        $this->assertTrue(method_exists($factory, 'delivered'));
        $this->assertTrue(method_exists($factory, 'cancelled'));
    }

    /** @test */
    public function purchase_factory_has_payment_states()
    {
        $factory = new PurchaseFactory();
        $this->assertTrue(method_exists($factory, 'paid'));
        $this->assertTrue(method_exists($factory, 'cod'));
        $this->assertTrue(method_exists($factory, 'online'));
        $this->assertTrue(method_exists($factory, 'withCart'));
    }

    /** @test */
    public function merchant_purchase_factory_exists()
    {
        $this->assertTrue(class_exists(MerchantPurchaseFactory::class));
    }

    /** @test */
    public function merchant_purchase_factory_has_commission_method()
    {
        $factory = new MerchantPurchaseFactory();
        $this->assertTrue(method_exists($factory, 'withCommission'));
        $this->assertTrue(method_exists($factory, 'forPurchase'));
        $this->assertTrue(method_exists($factory, 'forMerchant'));
    }

    // ============================================
    // Identity Domain Factories
    // ============================================

    /** @test */
    public function user_factory_exists()
    {
        $this->assertTrue(class_exists(UserFactory::class));
    }

    /** @test */
    public function user_factory_has_role_states()
    {
        $factory = new UserFactory();
        $this->assertTrue(method_exists($factory, 'merchant'));
        $this->assertTrue(method_exists($factory, 'customer'));
        $this->assertTrue(method_exists($factory, 'unverified'));
        $this->assertTrue(method_exists($factory, 'active'));
        $this->assertTrue(method_exists($factory, 'inactive'));
        $this->assertTrue(method_exists($factory, 'banned'));
    }

    /** @test */
    public function operator_factory_exists()
    {
        $this->assertTrue(class_exists(OperatorFactory::class));
    }

    /** @test */
    public function operator_factory_has_admin_states()
    {
        $factory = new OperatorFactory();
        $this->assertTrue(method_exists($factory, 'superAdmin'));
        $this->assertTrue(method_exists($factory, 'active'));
        $this->assertTrue(method_exists($factory, 'withRole'));
    }

    /** @test */
    public function operator_role_factory_exists()
    {
        $this->assertTrue(class_exists(OperatorRoleFactory::class));
    }

    /** @test */
    public function operator_role_factory_has_role_presets()
    {
        $factory = new OperatorRoleFactory();
        $this->assertTrue(method_exists($factory, 'superAdmin'));
        $this->assertTrue(method_exists($factory, 'contentManager'));
        $this->assertTrue(method_exists($factory, 'orderManager'));
        $this->assertTrue(method_exists($factory, 'support'));
        $this->assertTrue(method_exists($factory, 'withPermissions'));
    }

    // ============================================
    // Shipping Domain Factories
    // ============================================

    /** @test */
    public function shipment_tracking_factory_exists()
    {
        $this->assertTrue(class_exists(ShipmentTrackingFactory::class));
    }

    /** @test */
    public function shipment_tracking_factory_has_status_states()
    {
        $factory = new ShipmentTrackingFactory();
        $this->assertTrue(method_exists($factory, 'pending'));
        $this->assertTrue(method_exists($factory, 'pickedUp'));
        $this->assertTrue(method_exists($factory, 'inTransit'));
        $this->assertTrue(method_exists($factory, 'outForDelivery'));
        $this->assertTrue(method_exists($factory, 'delivered'));
        $this->assertTrue(method_exists($factory, 'failed'));
    }

    /** @test */
    public function courier_factory_exists()
    {
        $this->assertTrue(class_exists(CourierFactory::class));
    }

    /** @test */
    public function courier_factory_has_provider_states()
    {
        $factory = new CourierFactory();
        $this->assertTrue(method_exists($factory, 'smsa'));
        $this->assertTrue(method_exists($factory, 'aramex'));
        $this->assertTrue(method_exists($factory, 'withApi'));
    }

    /** @test */
    public function city_factory_exists()
    {
        $this->assertTrue(class_exists(CityFactory::class));
    }

    /** @test */
    public function city_factory_has_saudi_cities()
    {
        $factory = new CityFactory();
        $this->assertTrue(method_exists($factory, 'riyadh'));
        $this->assertTrue(method_exists($factory, 'jeddah'));
        $this->assertTrue(method_exists($factory, 'dammam'));
        $this->assertTrue(method_exists($factory, 'inCountry'));
    }

    /** @test */
    public function country_factory_exists()
    {
        $this->assertTrue(class_exists(CountryFactory::class));
    }

    /** @test */
    public function country_factory_has_gcc_countries()
    {
        $factory = new CountryFactory();
        $this->assertTrue(method_exists($factory, 'saudiArabia'));
        $this->assertTrue(method_exists($factory, 'uae'));
        $this->assertTrue(method_exists($factory, 'kuwait'));
    }

    // ============================================
    // Accounting Domain Factories
    // ============================================

    /** @test */
    public function account_balance_factory_exists()
    {
        $this->assertTrue(class_exists(AccountBalanceFactory::class));
    }

    /** @test */
    public function account_balance_factory_has_balance_states()
    {
        $factory = new AccountBalanceFactory();
        $this->assertTrue(method_exists($factory, 'zero'));
        $this->assertTrue(method_exists($factory, 'withBalance'));
        $this->assertTrue(method_exists($factory, 'withPending'));
        $this->assertTrue(method_exists($factory, 'rich'));
        $this->assertTrue(method_exists($factory, 'newMerchant'));
    }

    /** @test */
    public function withdraw_factory_exists()
    {
        $this->assertTrue(class_exists(WithdrawFactory::class));
    }

    /** @test */
    public function withdraw_factory_has_status_states()
    {
        $factory = new WithdrawFactory();
        $this->assertTrue(method_exists($factory, 'pending'));
        $this->assertTrue(method_exists($factory, 'processing'));
        $this->assertTrue(method_exists($factory, 'completed'));
        $this->assertTrue(method_exists($factory, 'rejected'));
        $this->assertTrue(method_exists($factory, 'amount'));
    }

    /** @test */
    public function accounting_ledger_factory_exists()
    {
        $this->assertTrue(class_exists(AccountingLedgerFactory::class));
    }

    /** @test */
    public function accounting_ledger_factory_has_transaction_types()
    {
        $factory = new AccountingLedgerFactory();
        $this->assertTrue(method_exists($factory, 'credit'));
        $this->assertTrue(method_exists($factory, 'debit'));
        $this->assertTrue(method_exists($factory, 'orderPayment'));
        $this->assertTrue(method_exists($factory, 'commission'));
        $this->assertTrue(method_exists($factory, 'withdrawal'));
    }

    // ============================================
    // Integration Tests
    // ============================================

    /** @test */
    public function all_factories_exist()
    {
        $factories = [
            CatalogItemFactory::class,
            BrandFactory::class,
            CategoryFactory::class,
            CatalogReviewFactory::class,
            MerchantItemFactory::class,
            MerchantBranchFactory::class,
            MerchantSettingFactory::class,
            PurchaseFactory::class,
            MerchantPurchaseFactory::class,
            UserFactory::class,
            OperatorFactory::class,
            OperatorRoleFactory::class,
            ShipmentTrackingFactory::class,
            CourierFactory::class,
            CityFactory::class,
            CountryFactory::class,
            AccountBalanceFactory::class,
            WithdrawFactory::class,
            AccountingLedgerFactory::class,
        ];

        foreach ($factories as $factory) {
            $this->assertTrue(class_exists($factory), "{$factory} should exist");
        }
    }

    /** @test */
    public function all_factories_extend_base_factory()
    {
        $factories = [
            CatalogItemFactory::class,
            BrandFactory::class,
            CategoryFactory::class,
            CatalogReviewFactory::class,
            MerchantItemFactory::class,
            MerchantBranchFactory::class,
            MerchantSettingFactory::class,
            PurchaseFactory::class,
            MerchantPurchaseFactory::class,
            UserFactory::class,
            OperatorFactory::class,
            OperatorRoleFactory::class,
            ShipmentTrackingFactory::class,
            CourierFactory::class,
            CityFactory::class,
            CountryFactory::class,
            AccountBalanceFactory::class,
            WithdrawFactory::class,
            AccountingLedgerFactory::class,
        ];

        foreach ($factories as $factory) {
            $this->assertTrue(
                is_subclass_of($factory, Factory::class),
                "{$factory} should extend Factory"
            );
        }
    }

    /** @test */
    public function all_factories_have_definition_method()
    {
        $factories = [
            CatalogItemFactory::class,
            BrandFactory::class,
            CategoryFactory::class,
            CatalogReviewFactory::class,
            MerchantItemFactory::class,
            MerchantBranchFactory::class,
            MerchantSettingFactory::class,
            PurchaseFactory::class,
            MerchantPurchaseFactory::class,
            UserFactory::class,
            OperatorFactory::class,
            OperatorRoleFactory::class,
            ShipmentTrackingFactory::class,
            CourierFactory::class,
            CityFactory::class,
            CountryFactory::class,
            AccountBalanceFactory::class,
            WithdrawFactory::class,
            AccountingLedgerFactory::class,
        ];

        foreach ($factories as $factory) {
            $this->assertTrue(
                method_exists($factory, 'definition'),
                "{$factory} should have definition method"
            );
        }
    }

    /** @test */
    public function catalog_factories_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Catalog\\Factories',
            CatalogItemFactory::class
        );
    }

    /** @test */
    public function merchant_factories_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Merchant\\Factories',
            MerchantItemFactory::class
        );
    }

    /** @test */
    public function commerce_factories_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Commerce\\Factories',
            PurchaseFactory::class
        );
    }

    /** @test */
    public function identity_factories_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Identity\\Factories',
            UserFactory::class
        );
    }

    /** @test */
    public function shipping_factories_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Shipping\\Factories',
            ShipmentTrackingFactory::class
        );
    }

    /** @test */
    public function accounting_factories_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Accounting\\Factories',
            AccountBalanceFactory::class
        );
    }
}
