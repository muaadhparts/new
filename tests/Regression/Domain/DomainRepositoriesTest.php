<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;

// Platform Repositories
use App\Domain\Platform\Repositories\BaseRepository;
use App\Domain\Platform\Repositories\SettingRepository;
use App\Domain\Platform\Repositories\MonetaryUnitRepository;

// Catalog Repositories
use App\Domain\Catalog\Repositories\CatalogItemRepository;
use App\Domain\Catalog\Repositories\CategoryRepository;
use App\Domain\Catalog\Repositories\BrandRepository;

// Commerce Repositories
use App\Domain\Commerce\Repositories\PurchaseRepository;
use App\Domain\Commerce\Repositories\MerchantPurchaseRepository;

// Merchant Repositories
use App\Domain\Merchant\Repositories\MerchantItemRepository;
use App\Domain\Merchant\Repositories\MerchantBranchRepository;

// Shipping Repositories
use App\Domain\Shipping\Repositories\ShipmentRepository;

// Identity Repositories
use App\Domain\Identity\Repositories\UserRepository;

// Accounting Repositories
use App\Domain\Accounting\Repositories\WithdrawRepository;

/**
 * Phase 42: Domain Repositories Tests
 *
 * Tests for domain repository classes.
 */
class DomainRepositoriesTest extends TestCase
{
    // ============================================
    // Base Repository
    // ============================================

    /** @test */
    public function base_repository_exists()
    {
        $this->assertTrue(class_exists(BaseRepository::class));
    }

    /** @test */
    public function base_repository_is_abstract()
    {
        $reflection = new \ReflectionClass(BaseRepository::class);
        $this->assertTrue($reflection->isAbstract());
    }

    /** @test */
    public function base_repository_has_required_methods()
    {
        $methods = ['all', 'find', 'findOrFail', 'findBy', 'findFirstBy', 'create', 'update', 'delete', 'paginate', 'query', 'count', 'exists'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(BaseRepository::class, $method),
                "BaseRepository should have {$method} method"
            );
        }
    }

    // ============================================
    // Platform Repositories
    // ============================================

    /** @test */
    public function setting_repository_exists()
    {
        $this->assertTrue(class_exists(SettingRepository::class));
    }

    /** @test */
    public function setting_repository_extends_base()
    {
        $this->assertTrue(is_subclass_of(SettingRepository::class, BaseRepository::class));
    }

    /** @test */
    public function setting_repository_has_custom_methods()
    {
        $methods = ['get', 'set', 'getMany', 'getAllAsArray', 'clearCache'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(SettingRepository::class, $method),
                "SettingRepository should have {$method} method"
            );
        }
    }

    /** @test */
    public function monetary_unit_repository_exists()
    {
        $this->assertTrue(class_exists(MonetaryUnitRepository::class));
    }

    /** @test */
    public function monetary_unit_repository_extends_base()
    {
        $this->assertTrue(is_subclass_of(MonetaryUnitRepository::class, BaseRepository::class));
    }

    /** @test */
    public function monetary_unit_repository_has_custom_methods()
    {
        $methods = ['getDefault', 'getActive', 'setDefault', 'findByCode', 'getForDropdown'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(MonetaryUnitRepository::class, $method),
                "MonetaryUnitRepository should have {$method} method"
            );
        }
    }

    // ============================================
    // Catalog Repositories
    // ============================================

    /** @test */
    public function catalog_item_repository_exists()
    {
        $this->assertTrue(class_exists(CatalogItemRepository::class));
    }

    /** @test */
    public function catalog_item_repository_extends_base()
    {
        $this->assertTrue(is_subclass_of(CatalogItemRepository::class, BaseRepository::class));
    }

    /** @test */
    public function catalog_item_repository_has_custom_methods()
    {
        $methods = ['findBySku', 'findBySlug', 'getByCategory', 'getByBrand', 'search', 'getWithMerchantOffers', 'getRecent'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(CatalogItemRepository::class, $method),
                "CatalogItemRepository should have {$method} method"
            );
        }
    }

    /** @test */
    public function category_repository_exists()
    {
        $this->assertTrue(class_exists(CategoryRepository::class));
    }

    /** @test */
    public function category_repository_extends_base()
    {
        $this->assertTrue(is_subclass_of(CategoryRepository::class, BaseRepository::class));
    }

    /** @test */
    public function category_repository_has_custom_methods()
    {
        $methods = ['getRoots', 'getChildren', 'getTree', 'findBySlug', 'getWithAncestors', 'getForDropdown', 'clearCache'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(CategoryRepository::class, $method),
                "CategoryRepository should have {$method} method"
            );
        }
    }

    /** @test */
    public function brand_repository_exists()
    {
        $this->assertTrue(class_exists(BrandRepository::class));
    }

    /** @test */
    public function brand_repository_extends_base()
    {
        $this->assertTrue(is_subclass_of(BrandRepository::class, BaseRepository::class));
    }

    /** @test */
    public function brand_repository_has_custom_methods()
    {
        $methods = ['getActive', 'findBySlug', 'getForDropdown', 'getWithProductCount', 'clearCache'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(BrandRepository::class, $method),
                "BrandRepository should have {$method} method"
            );
        }
    }

    // ============================================
    // Commerce Repositories
    // ============================================

    /** @test */
    public function purchase_repository_exists()
    {
        $this->assertTrue(class_exists(PurchaseRepository::class));
    }

    /** @test */
    public function purchase_repository_extends_base()
    {
        $this->assertTrue(is_subclass_of(PurchaseRepository::class, BaseRepository::class));
    }

    /** @test */
    public function purchase_repository_has_custom_methods()
    {
        $methods = ['findByOrderNumber', 'getByUser', 'getByStatus', 'getPending', 'getForDateRange', 'getRecent', 'getWithRelations', 'getCountByStatus'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(PurchaseRepository::class, $method),
                "PurchaseRepository should have {$method} method"
            );
        }
    }

    /** @test */
    public function merchant_purchase_repository_exists()
    {
        $this->assertTrue(class_exists(MerchantPurchaseRepository::class));
    }

    /** @test */
    public function merchant_purchase_repository_extends_base()
    {
        $this->assertTrue(is_subclass_of(MerchantPurchaseRepository::class, BaseRepository::class));
    }

    /** @test */
    public function merchant_purchase_repository_has_custom_methods()
    {
        $methods = ['getByMerchant', 'getByMerchantAndStatus', 'getPendingByMerchant', 'getByMerchantForDateRange', 'getMerchantRevenue', 'getWithPurchase'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(MerchantPurchaseRepository::class, $method),
                "MerchantPurchaseRepository should have {$method} method"
            );
        }
    }

    // ============================================
    // Merchant Repositories
    // ============================================

    /** @test */
    public function merchant_item_repository_exists()
    {
        $this->assertTrue(class_exists(MerchantItemRepository::class));
    }

    /** @test */
    public function merchant_item_repository_extends_base()
    {
        $this->assertTrue(is_subclass_of(MerchantItemRepository::class, BaseRepository::class));
    }

    /** @test */
    public function merchant_item_repository_has_custom_methods()
    {
        $methods = ['getByMerchant', 'getActiveByMerchant', 'getLowStockByMerchant', 'getOutOfStockByMerchant', 'findByMerchantAndCatalogItem', 'getByCatalogItem', 'updateStock', 'decrementStock'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(MerchantItemRepository::class, $method),
                "MerchantItemRepository should have {$method} method"
            );
        }
    }

    /** @test */
    public function merchant_branch_repository_exists()
    {
        $this->assertTrue(class_exists(MerchantBranchRepository::class));
    }

    /** @test */
    public function merchant_branch_repository_extends_base()
    {
        $this->assertTrue(is_subclass_of(MerchantBranchRepository::class, BaseRepository::class));
    }

    /** @test */
    public function merchant_branch_repository_has_custom_methods()
    {
        $methods = ['getByMerchant', 'getMainBranch', 'getActiveByMerchant', 'getByCity', 'getNearby', 'setAsMain'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(MerchantBranchRepository::class, $method),
                "MerchantBranchRepository should have {$method} method"
            );
        }
    }

    // ============================================
    // Shipping Repositories
    // ============================================

    /** @test */
    public function shipment_repository_exists()
    {
        $this->assertTrue(class_exists(ShipmentRepository::class));
    }

    /** @test */
    public function shipment_repository_extends_base()
    {
        $this->assertTrue(is_subclass_of(ShipmentRepository::class, BaseRepository::class));
    }

    /** @test */
    public function shipment_repository_has_custom_methods()
    {
        $methods = ['findByTrackingNumber', 'getByStatus', 'getByCourier', 'getPending', 'getInTransit', 'getByPurchase', 'getWithTracking', 'getOverdue'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(ShipmentRepository::class, $method),
                "ShipmentRepository should have {$method} method"
            );
        }
    }

    // ============================================
    // Identity Repositories
    // ============================================

    /** @test */
    public function user_repository_exists()
    {
        $this->assertTrue(class_exists(UserRepository::class));
    }

    /** @test */
    public function user_repository_extends_base()
    {
        $this->assertTrue(is_subclass_of(UserRepository::class, BaseRepository::class));
    }

    /** @test */
    public function user_repository_has_custom_methods()
    {
        $methods = ['findByEmail', 'findByPhone', 'getMerchants', 'getActiveMerchants', 'getCustomers', 'getUnverified', 'getRecentlyRegistered', 'search'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(UserRepository::class, $method),
                "UserRepository should have {$method} method"
            );
        }
    }

    // ============================================
    // Accounting Repositories
    // ============================================

    /** @test */
    public function withdraw_repository_exists()
    {
        $this->assertTrue(class_exists(WithdrawRepository::class));
    }

    /** @test */
    public function withdraw_repository_extends_base()
    {
        $this->assertTrue(is_subclass_of(WithdrawRepository::class, BaseRepository::class));
    }

    /** @test */
    public function withdraw_repository_has_custom_methods()
    {
        $methods = ['getByMerchant', 'getPending', 'getPendingByMerchant', 'getByStatus', 'getTotalWithdrawnByMerchant', 'getForDateRange', 'hasPending'];

        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists(WithdrawRepository::class, $method),
                "WithdrawRepository should have {$method} method"
            );
        }
    }

    // ============================================
    // Integration Tests
    // ============================================

    /** @test */
    public function all_repositories_exist()
    {
        $repositories = [
            SettingRepository::class,
            MonetaryUnitRepository::class,
            CatalogItemRepository::class,
            CategoryRepository::class,
            BrandRepository::class,
            PurchaseRepository::class,
            MerchantPurchaseRepository::class,
            MerchantItemRepository::class,
            MerchantBranchRepository::class,
            ShipmentRepository::class,
            UserRepository::class,
            WithdrawRepository::class,
        ];

        foreach ($repositories as $repository) {
            $this->assertTrue(class_exists($repository), "{$repository} should exist");
        }
    }

    /** @test */
    public function all_repositories_extend_base()
    {
        $repositories = [
            SettingRepository::class,
            MonetaryUnitRepository::class,
            CatalogItemRepository::class,
            CategoryRepository::class,
            BrandRepository::class,
            PurchaseRepository::class,
            MerchantPurchaseRepository::class,
            MerchantItemRepository::class,
            MerchantBranchRepository::class,
            ShipmentRepository::class,
            UserRepository::class,
            WithdrawRepository::class,
        ];

        foreach ($repositories as $repository) {
            $this->assertTrue(
                is_subclass_of($repository, BaseRepository::class),
                "{$repository} should extend BaseRepository"
            );
        }
    }

    // ============================================
    // Namespace Tests
    // ============================================

    /** @test */
    public function platform_repositories_are_in_correct_namespace()
    {
        $this->assertStringStartsWith('App\\Domain\\Platform\\Repositories', SettingRepository::class);
        $this->assertStringStartsWith('App\\Domain\\Platform\\Repositories', MonetaryUnitRepository::class);
    }

    /** @test */
    public function catalog_repositories_are_in_correct_namespace()
    {
        $this->assertStringStartsWith('App\\Domain\\Catalog\\Repositories', CatalogItemRepository::class);
        $this->assertStringStartsWith('App\\Domain\\Catalog\\Repositories', CategoryRepository::class);
        $this->assertStringStartsWith('App\\Domain\\Catalog\\Repositories', BrandRepository::class);
    }

    /** @test */
    public function commerce_repositories_are_in_correct_namespace()
    {
        $this->assertStringStartsWith('App\\Domain\\Commerce\\Repositories', PurchaseRepository::class);
        $this->assertStringStartsWith('App\\Domain\\Commerce\\Repositories', MerchantPurchaseRepository::class);
    }

    /** @test */
    public function merchant_repositories_are_in_correct_namespace()
    {
        $this->assertStringStartsWith('App\\Domain\\Merchant\\Repositories', MerchantItemRepository::class);
        $this->assertStringStartsWith('App\\Domain\\Merchant\\Repositories', MerchantBranchRepository::class);
    }

    /** @test */
    public function shipping_repositories_are_in_correct_namespace()
    {
        $this->assertStringStartsWith('App\\Domain\\Shipping\\Repositories', ShipmentRepository::class);
    }

    /** @test */
    public function identity_repositories_are_in_correct_namespace()
    {
        $this->assertStringStartsWith('App\\Domain\\Identity\\Repositories', UserRepository::class);
    }

    /** @test */
    public function accounting_repositories_are_in_correct_namespace()
    {
        $this->assertStringStartsWith('App\\Domain\\Accounting\\Repositories', WithdrawRepository::class);
    }

    // ============================================
    // Directory Structure Tests
    // ============================================

    /** @test */
    public function platform_repositories_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Platform/Repositories'));
    }

    /** @test */
    public function catalog_repositories_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Catalog/Repositories'));
    }

    /** @test */
    public function commerce_repositories_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Commerce/Repositories'));
    }

    /** @test */
    public function merchant_repositories_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Merchant/Repositories'));
    }

    /** @test */
    public function shipping_repositories_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Shipping/Repositories'));
    }

    /** @test */
    public function identity_repositories_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Identity/Repositories'));
    }

    /** @test */
    public function accounting_repositories_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Accounting/Repositories'));
    }
}
