<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;

// Catalog Queries
use App\Domain\Catalog\Queries\CatalogItemQuery;
use App\Domain\Catalog\Queries\ActiveMerchantsQuery;
use App\Domain\Catalog\Queries\CategoryDescendantsQuery;

// Commerce Queries
use App\Domain\Commerce\Queries\PurchaseQuery;
use App\Domain\Commerce\Queries\MerchantPurchaseQuery;

// Merchant Queries
use App\Domain\Merchant\Queries\MerchantItemQuery;

/**
 * Regression Tests for Domain Query Classes
 *
 * Phase 11: Query Classes
 *
 * This test ensures that query classes are properly structured and functional.
 */
class QueryClassesTest extends TestCase
{
    // =========================================================================
    // CATALOG QUERIES
    // =========================================================================

    /** @test */
    public function catalog_item_query_exists()
    {
        $this->assertTrue(class_exists(CatalogItemQuery::class));
    }

    /** @test */
    public function catalog_item_query_can_be_instantiated()
    {
        $query = CatalogItemQuery::make();
        $this->assertInstanceOf(CatalogItemQuery::class, $query);
    }

    /** @test */
    public function catalog_item_query_has_filter_methods()
    {
        $this->assertTrue(method_exists(CatalogItemQuery::class, 'forBrand'));
        $this->assertTrue(method_exists(CatalogItemQuery::class, 'forCatalog'));
        $this->assertTrue(method_exists(CatalogItemQuery::class, 'forMerchants'));
        $this->assertTrue(method_exists(CatalogItemQuery::class, 'forBranches'));
        $this->assertTrue(method_exists(CatalogItemQuery::class, 'forQualityBrands'));
        $this->assertTrue(method_exists(CatalogItemQuery::class, 'priceRange'));
        $this->assertTrue(method_exists(CatalogItemQuery::class, 'search'));
        $this->assertTrue(method_exists(CatalogItemQuery::class, 'withDiscount'));
    }

    /** @test */
    public function catalog_item_query_has_sorting_methods()
    {
        $this->assertTrue(method_exists(CatalogItemQuery::class, 'sortBy'));
    }

    /** @test */
    public function catalog_item_query_has_execution_methods()
    {
        $this->assertTrue(method_exists(CatalogItemQuery::class, 'paginate'));
        $this->assertTrue(method_exists(CatalogItemQuery::class, 'get'));
        $this->assertTrue(method_exists(CatalogItemQuery::class, 'count'));
        $this->assertTrue(method_exists(CatalogItemQuery::class, 'getQuery'));
    }

    /** @test */
    public function catalog_item_query_returns_fluent_interface()
    {
        $query = CatalogItemQuery::make();

        $result = $query->forMerchants([1, 2, 3]);
        $this->assertInstanceOf(CatalogItemQuery::class, $result);

        $result = $query->search('test');
        $this->assertInstanceOf(CatalogItemQuery::class, $result);

        $result = $query->sortBy('price_asc');
        $this->assertInstanceOf(CatalogItemQuery::class, $result);
    }

    /** @test */
    public function active_merchants_query_exists()
    {
        $this->assertTrue(class_exists(ActiveMerchantsQuery::class));
    }

    /** @test */
    public function active_merchants_query_has_static_methods()
    {
        $this->assertTrue(method_exists(ActiveMerchantsQuery::class, 'all'));
        $this->assertTrue(method_exists(ActiveMerchantsQuery::class, 'forCategory'));
        $this->assertTrue(method_exists(ActiveMerchantsQuery::class, 'forBrand'));
        $this->assertTrue(method_exists(ActiveMerchantsQuery::class, 'getItemCount'));
        $this->assertTrue(method_exists(ActiveMerchantsQuery::class, 'hasActiveItems'));
        $this->assertTrue(method_exists(ActiveMerchantsQuery::class, 'withItemCounts'));
    }

    /** @test */
    public function category_descendants_query_exists()
    {
        $this->assertTrue(class_exists(CategoryDescendantsQuery::class));
    }

    /** @test */
    public function category_descendants_query_has_static_methods()
    {
        $this->assertTrue(method_exists(CategoryDescendantsQuery::class, 'getDescendantIds'));
        $this->assertTrue(method_exists(CategoryDescendantsQuery::class, 'getAncestorIds'));
        $this->assertTrue(method_exists(CategoryDescendantsQuery::class, 'getDescendants'));
        $this->assertTrue(method_exists(CategoryDescendantsQuery::class, 'getAncestors'));
        $this->assertTrue(method_exists(CategoryDescendantsQuery::class, 'getChildren'));
        $this->assertTrue(method_exists(CategoryDescendantsQuery::class, 'getSiblings'));
        $this->assertTrue(method_exists(CategoryDescendantsQuery::class, 'getMaxDepth'));
        $this->assertTrue(method_exists(CategoryDescendantsQuery::class, 'atLevel'));
        $this->assertTrue(method_exists(CategoryDescendantsQuery::class, 'roots'));
    }

    // =========================================================================
    // COMMERCE QUERIES
    // =========================================================================

    /** @test */
    public function purchase_query_exists()
    {
        $this->assertTrue(class_exists(PurchaseQuery::class));
    }

    /** @test */
    public function purchase_query_can_be_instantiated()
    {
        $query = PurchaseQuery::make();
        $this->assertInstanceOf(PurchaseQuery::class, $query);
    }

    /** @test */
    public function purchase_query_has_filter_methods()
    {
        $this->assertTrue(method_exists(PurchaseQuery::class, 'forCustomer'));
        $this->assertTrue(method_exists(PurchaseQuery::class, 'withStatus'));
        $this->assertTrue(method_exists(PurchaseQuery::class, 'withStatuses'));
        $this->assertTrue(method_exists(PurchaseQuery::class, 'pending'));
        $this->assertTrue(method_exists(PurchaseQuery::class, 'processing'));
        $this->assertTrue(method_exists(PurchaseQuery::class, 'completed'));
        $this->assertTrue(method_exists(PurchaseQuery::class, 'cancelled'));
        $this->assertTrue(method_exists(PurchaseQuery::class, 'betweenDates'));
        $this->assertTrue(method_exists(PurchaseQuery::class, 'paid'));
        $this->assertTrue(method_exists(PurchaseQuery::class, 'unpaid'));
    }

    /** @test */
    public function purchase_query_has_execution_methods()
    {
        $this->assertTrue(method_exists(PurchaseQuery::class, 'paginate'));
        $this->assertTrue(method_exists(PurchaseQuery::class, 'get'));
        $this->assertTrue(method_exists(PurchaseQuery::class, 'first'));
        $this->assertTrue(method_exists(PurchaseQuery::class, 'count'));
        $this->assertTrue(method_exists(PurchaseQuery::class, 'totalAmount'));
    }

    /** @test */
    public function purchase_query_returns_fluent_interface()
    {
        $query = PurchaseQuery::make();

        $result = $query->forCustomer(1);
        $this->assertInstanceOf(PurchaseQuery::class, $result);

        $result = $query->pending();
        $this->assertInstanceOf(PurchaseQuery::class, $result);

        $result = $query->latest();
        $this->assertInstanceOf(PurchaseQuery::class, $result);
    }

    /** @test */
    public function merchant_purchase_query_exists()
    {
        $this->assertTrue(class_exists(MerchantPurchaseQuery::class));
    }

    /** @test */
    public function merchant_purchase_query_can_be_instantiated()
    {
        $query = MerchantPurchaseQuery::make();
        $this->assertInstanceOf(MerchantPurchaseQuery::class, $query);
    }

    /** @test */
    public function merchant_purchase_query_has_filter_methods()
    {
        $this->assertTrue(method_exists(MerchantPurchaseQuery::class, 'forMerchant'));
        $this->assertTrue(method_exists(MerchantPurchaseQuery::class, 'forBranch'));
        $this->assertTrue(method_exists(MerchantPurchaseQuery::class, 'forPurchase'));
        $this->assertTrue(method_exists(MerchantPurchaseQuery::class, 'withStatus'));
        $this->assertTrue(method_exists(MerchantPurchaseQuery::class, 'newOrders'));
        $this->assertTrue(method_exists(MerchantPurchaseQuery::class, 'inProgress'));
        $this->assertTrue(method_exists(MerchantPurchaseQuery::class, 'delivered'));
        $this->assertTrue(method_exists(MerchantPurchaseQuery::class, 'today'));
        $this->assertTrue(method_exists(MerchantPurchaseQuery::class, 'thisWeek'));
        $this->assertTrue(method_exists(MerchantPurchaseQuery::class, 'thisMonth'));
    }

    /** @test */
    public function merchant_purchase_query_has_analytics_methods()
    {
        $this->assertTrue(method_exists(MerchantPurchaseQuery::class, 'getDailySummary'));
        $this->assertTrue(method_exists(MerchantPurchaseQuery::class, 'totalAmount'));
    }

    // =========================================================================
    // MERCHANT QUERIES
    // =========================================================================

    /** @test */
    public function merchant_item_query_exists()
    {
        $this->assertTrue(class_exists(MerchantItemQuery::class));
    }

    /** @test */
    public function merchant_item_query_can_be_instantiated()
    {
        $query = MerchantItemQuery::make();
        $this->assertInstanceOf(MerchantItemQuery::class, $query);
    }

    /** @test */
    public function merchant_item_query_has_filter_methods()
    {
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'forMerchant'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'forBranch'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'forCatalogItem'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'active'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'inactive'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'inStock'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'outOfStock'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'preorder'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'withQualityBrand'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'onDiscount'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'priceRange'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'lowStock'));
    }

    /** @test */
    public function merchant_item_query_has_sorting_methods()
    {
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'cheapestFirst'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'expensiveFirst'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'mostStock'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'latest'));
    }

    /** @test */
    public function merchant_item_query_has_analytics_methods()
    {
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'totalStock'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'averagePrice'));
        $this->assertTrue(method_exists(MerchantItemQuery::class, 'inventoryValue'));
    }

    /** @test */
    public function merchant_item_query_returns_fluent_interface()
    {
        $query = MerchantItemQuery::make();

        $result = $query->forMerchant(1);
        $this->assertInstanceOf(MerchantItemQuery::class, $result);

        $result = $query->active();
        $this->assertInstanceOf(MerchantItemQuery::class, $result);

        $result = $query->inStock();
        $this->assertInstanceOf(MerchantItemQuery::class, $result);

        $result = $query->cheapestFirst();
        $this->assertInstanceOf(MerchantItemQuery::class, $result);
    }

    // =========================================================================
    // DIRECTORY STRUCTURE
    // =========================================================================

    /** @test */
    public function catalog_queries_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Catalog/Queries'));
    }

    /** @test */
    public function commerce_queries_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Commerce/Queries'));
    }

    /** @test */
    public function merchant_queries_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Merchant/Queries'));
    }
}
