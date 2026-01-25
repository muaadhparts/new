<?php

namespace Tests\Regression\Catalog;

use Tests\TestCase;
use App\Domain\Catalog\Services\CatalogItemFilterService;
use App\Domain\Catalog\Services\CatalogItemCardDataBuilder;
use App\Domain\Catalog\Services\CategoryFilterService;
use App\Domain\Catalog\Services\NewCategoryTreeService;
use App\Domain\Catalog\Services\CompatibilityService;
use App\Domain\Catalog\Services\CatalogItemOffersService;
use App\Domain\Catalog\Services\CatalogSessionManager;

/**
 * Regression Tests for Catalog Domain Services
 *
 * Tests to ensure all Catalog domain services are properly structured
 * and can be resolved from the container.
 */
class CatalogServicesTest extends TestCase
{
    // =========================================================================
    // CATALOG ITEM FILTER SERVICE TESTS
    // =========================================================================

    /** @test */
    public function catalog_item_filter_service_can_be_resolved()
    {
        $service = app(CatalogItemFilterService::class);
        $this->assertInstanceOf(CatalogItemFilterService::class, $service);
    }

    /** @test */
    public function catalog_item_filter_service_has_sidebar_methods()
    {
        $service = app(CatalogItemFilterService::class);

        $this->assertTrue(method_exists($service, 'getFilterSidebarData'));
        $this->assertTrue(method_exists($service, 'getActiveMerchants'));
        $this->assertTrue(method_exists($service, 'getBranchesForMerchants'));
    }

    /** @test */
    public function catalog_item_filter_service_has_hierarchy_methods()
    {
        $service = app(CatalogItemFilterService::class);

        $this->assertTrue(method_exists($service, 'resolveCategoryHierarchy'));
        $this->assertTrue(method_exists($service, 'getDescendantIds'));
    }

    /** @test */
    public function catalog_item_filter_service_has_query_methods()
    {
        $service = app(CatalogItemFilterService::class);

        $this->assertTrue(method_exists($service, 'buildCatalogItemQuery'));
        $this->assertTrue(method_exists($service, 'applyMerchantItemsEagerLoad'));
    }

    /** @test */
    public function catalog_item_filter_service_has_filter_methods()
    {
        $service = app(CatalogItemFilterService::class);

        $this->assertTrue(method_exists($service, 'applyCatalogItemFitmentFilters'));
        $this->assertTrue(method_exists($service, 'applyCatalogItemMerchantFilter'));
        $this->assertTrue(method_exists($service, 'applyCatalogItemBranchFilter'));
        $this->assertTrue(method_exists($service, 'applyCatalogItemQualityBrandFilter'));
        $this->assertTrue(method_exists($service, 'applyCatalogItemPriceFilter'));
        $this->assertTrue(method_exists($service, 'applyCatalogItemSearchFilter'));
        $this->assertTrue(method_exists($service, 'applyCatalogItemDiscountFilter'));
        $this->assertTrue(method_exists($service, 'applyCatalogItemSorting'));
        $this->assertTrue(method_exists($service, 'applyCatalogItemFilters'));
    }

    /** @test */
    public function catalog_item_filter_service_has_result_methods()
    {
        $service = app(CatalogItemFilterService::class);

        $this->assertTrue(method_exists($service, 'getCatalogItemFirstResults'));
        $this->assertTrue(method_exists($service, 'getCatalogItemsFromCategoryTree'));
    }

    // =========================================================================
    // CATALOG ITEM CARD DATA BUILDER TESTS
    // =========================================================================

    /** @test */
    public function catalog_item_card_data_builder_can_be_resolved()
    {
        $service = app(CatalogItemCardDataBuilder::class);
        $this->assertInstanceOf(CatalogItemCardDataBuilder::class, $service);
    }

    /** @test */
    public function catalog_item_card_data_builder_has_constants()
    {
        $this->assertNotEmpty(CatalogItemCardDataBuilder::MERCHANT_ITEM_RELATIONS);
        $this->assertNotEmpty(CatalogItemCardDataBuilder::CATALOG_ITEM_RELATIONS);
    }

    /** @test */
    public function catalog_item_card_data_builder_has_eager_loading_methods()
    {
        $service = app(CatalogItemCardDataBuilder::class);

        $this->assertTrue(method_exists($service, 'applyMerchantItemEagerLoading'));
        $this->assertTrue(method_exists($service, 'applyCatalogItemEagerLoading'));
    }

    /** @test */
    public function catalog_item_card_data_builder_has_favorite_methods()
    {
        $service = app(CatalogItemCardDataBuilder::class);

        $this->assertTrue(method_exists($service, 'initialize'));
        $this->assertTrue(method_exists($service, 'isInFavorites'));
        $this->assertTrue(method_exists($service, 'isMerchantInFavorites'));
        $this->assertTrue(method_exists($service, 'getFavoriteCatalogItemIds'));
        $this->assertTrue(method_exists($service, 'getFavoriteMerchantIds'));
    }

    /** @test */
    public function catalog_item_card_data_builder_has_card_building_methods()
    {
        $service = app(CatalogItemCardDataBuilder::class);

        $this->assertTrue(method_exists($service, 'buildCardsFromMerchants'));
        $this->assertTrue(method_exists($service, 'buildCardsFromPaginator'));
        $this->assertTrue(method_exists($service, 'buildCardsFromCatalogItems'));
        $this->assertTrue(method_exists($service, 'buildCardsFromCatalogItemPaginator'));
        $this->assertTrue(method_exists($service, 'buildCardFromMerchant'));
    }

    /** @test */
    public function catalog_item_card_data_builder_has_utility_methods()
    {
        $service = app(CatalogItemCardDataBuilder::class);

        $this->assertTrue(method_exists($service, 'getViewData'));
        $this->assertTrue(method_exists($service, 'getMuaadhSettings'));
        $this->assertTrue(method_exists($service, 'invalidateFavoriteCache'));
    }

    // =========================================================================
    // CATEGORY FILTER SERVICE TESTS
    // =========================================================================

    /** @test */
    public function category_filter_service_can_be_resolved()
    {
        $service = app(CategoryFilterService::class);
        $this->assertInstanceOf(CategoryFilterService::class, $service);
    }

    /** @test */
    public function category_filter_service_has_filtering_methods()
    {
        $service = app(CategoryFilterService::class);

        $this->assertTrue(method_exists($service, 'getFilteredLevel3FullCodes'));
    }

    /** @test */
    public function category_filter_service_has_node_loading_methods()
    {
        $service = app(CategoryFilterService::class);

        $this->assertTrue(method_exists($service, 'loadLevel1Nodes'));
        $this->assertTrue(method_exists($service, 'loadLevel2Nodes'));
        $this->assertTrue(method_exists($service, 'loadLevel3Nodes'));
    }

    /** @test */
    public function category_filter_service_has_helper_methods()
    {
        $service = app(CategoryFilterService::class);

        $this->assertTrue(method_exists($service, 'findCategory'));
        $this->assertTrue(method_exists($service, 'computeAllowedCodesForSections'));
    }

    // =========================================================================
    // NEW CATEGORY TREE SERVICE TESTS
    // =========================================================================

    /** @test */
    public function new_category_tree_service_can_be_resolved()
    {
        $service = app(NewCategoryTreeService::class);
        $this->assertInstanceOf(NewCategoryTreeService::class, $service);
    }

    /** @test */
    public function new_category_tree_service_has_descendant_methods()
    {
        $service = app(NewCategoryTreeService::class);

        $this->assertTrue(method_exists($service, 'getDescendantIds'));
        $this->assertTrue(method_exists($service, 'getDescendantIdsForMultiple'));
    }

    /** @test */
    public function new_category_tree_service_has_parts_methods()
    {
        $service = app(NewCategoryTreeService::class);

        $this->assertTrue(method_exists($service, 'getRawParts'));
        $this->assertTrue(method_exists($service, 'countAvailableParts'));
    }

    /** @test */
    public function new_category_tree_service_has_tree_methods()
    {
        $service = app(NewCategoryTreeService::class);

        $this->assertTrue(method_exists($service, 'buildCategoryTree'));
        $this->assertTrue(method_exists($service, 'getBreadcrumb'));
    }

    /** @test */
    public function new_category_tree_service_has_resolution_methods()
    {
        $service = app(NewCategoryTreeService::class);

        $this->assertTrue(method_exists($service, 'resolveCategoryBySlug'));
        $this->assertTrue(method_exists($service, 'resolveCategoryHierarchy'));
        $this->assertTrue(method_exists($service, 'resolveBrandAndCatalog'));
    }

    // =========================================================================
    // COMPATIBILITY SERVICE TESTS
    // =========================================================================

    /** @test */
    public function compatibility_service_can_be_resolved()
    {
        $service = app(CompatibilityService::class);
        $this->assertInstanceOf(CompatibilityService::class, $service);
    }

    /** @test */
    public function compatibility_service_has_catalog_methods()
    {
        $service = app(CompatibilityService::class);

        $this->assertTrue(method_exists($service, 'getCompatibleCatalogs'));
        $this->assertTrue(method_exists($service, 'isCompatibleWith'));
        $this->assertTrue(method_exists($service, 'countCompatibleCatalogs'));
        $this->assertTrue(method_exists($service, 'getCompatibleCatalogCodes'));
    }

    /** @test */
    public function compatibility_service_has_detailed_methods()
    {
        $service = app(CompatibilityService::class);

        $this->assertTrue(method_exists($service, 'getDetailedCompatibility'));
        $this->assertTrue(method_exists($service, 'getCompatibleCatalogsBatch'));
    }

    // =========================================================================
    // CATALOG ITEM OFFERS SERVICE TESTS
    // =========================================================================

    /** @test */
    public function catalog_item_offers_service_can_be_resolved()
    {
        $service = app(CatalogItemOffersService::class);
        $this->assertInstanceOf(CatalogItemOffersService::class, $service);
    }

    /** @test */
    public function catalog_item_offers_service_has_grouped_offers_method()
    {
        $service = app(CatalogItemOffersService::class);

        $this->assertTrue(method_exists($service, 'getGroupedOffers'));
    }

    // =========================================================================
    // CATALOG SESSION MANAGER TESTS
    // =========================================================================

    /** @test */
    public function catalog_session_manager_can_be_resolved()
    {
        $service = app(CatalogSessionManager::class);
        $this->assertInstanceOf(CatalogSessionManager::class, $service);
    }

    /** @test */
    public function catalog_session_manager_has_filter_methods()
    {
        $service = app(CatalogSessionManager::class);

        $this->assertTrue(method_exists($service, 'getSelectedFilters'));
        $this->assertTrue(method_exists($service, 'setSelectedFilters'));
        $this->assertTrue(method_exists($service, 'getLabeledFilters'));
        $this->assertTrue(method_exists($service, 'setLabeledFilters'));
    }

    /** @test */
    public function catalog_session_manager_has_catalog_methods()
    {
        $service = app(CatalogSessionManager::class);

        $this->assertTrue(method_exists($service, 'getCurrentCatalog'));
        $this->assertTrue(method_exists($service, 'setCurrentCatalog'));
        $this->assertTrue(method_exists($service, 'loadBrandAndCatalog'));
        $this->assertTrue(method_exists($service, 'loadCategoryWithRelations'));
    }

    /** @test */
    public function catalog_session_manager_has_code_methods()
    {
        $service = app(CatalogSessionManager::class);

        $this->assertTrue(method_exists($service, 'getAllowedLevel3Codes'));
        $this->assertTrue(method_exists($service, 'setAllowedLevel3Codes'));
        $this->assertTrue(method_exists($service, 'getSpecItemIds'));
        $this->assertTrue(method_exists($service, 'getFilterDate'));
    }

    /** @test */
    public function catalog_session_manager_has_vin_methods()
    {
        $service = app(CatalogSessionManager::class);

        $this->assertTrue(method_exists($service, 'getVin'));
        $this->assertTrue(method_exists($service, 'setVin'));
    }

    /** @test */
    public function catalog_session_manager_has_clear_methods()
    {
        $service = app(CatalogSessionManager::class);

        $this->assertTrue(method_exists($service, 'clearAll'));
        $this->assertTrue(method_exists($service, 'clearFilters'));
    }
}
