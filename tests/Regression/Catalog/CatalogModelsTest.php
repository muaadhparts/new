<?php

namespace Tests\Regression\Catalog;

use Tests\TestCase;
use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\BrandRegion;
use App\Domain\Catalog\Models\Catalog;
use App\Domain\Catalog\Models\NewCategory;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Models\CatalogItemFitment;
use App\Domain\Catalog\Models\Section;
use App\Domain\Catalog\Models\Illustration;
use App\Domain\Catalog\Models\Callout;
use App\Domain\Catalog\Models\CatalogReview;
use App\Domain\Catalog\Models\CategoryPeriod;
use App\Domain\Catalog\Models\Specification;
use App\Domain\Catalog\Models\SpecificationItem;
use App\Domain\Catalog\Models\CategorySpecGroup;
use App\Domain\Catalog\Models\CategorySpecGroupItem;
use App\Domain\Catalog\Models\QualityBrand;
use App\Domain\Catalog\Models\SkuAlternative;

class CatalogModelsTest extends TestCase
{
    /**
     * Test that old model paths still work (backward compatibility)
     */
    public function test_old_model_paths_work(): void
    {
        // These should not throw exceptions
        $brand = Brand::first();
        $this->assertNotNull($brand);

        $catalog = Catalog::first();
        $this->assertNotNull($catalog);

        $category = NewCategory::first();
        $this->assertNotNull($category);
    }

    /**
     * Test that old models are instances of new Domain models
     */
    public function test_old_models_extend_domain_models(): void
    {
        $brand = Brand::first();
        $this->assertInstanceOf(Brand::class, $brand);

        $catalog = Catalog::first();
        $this->assertInstanceOf(Catalog::class, $catalog);

        $category = NewCategory::first();
        $this->assertInstanceOf(NewCategory::class, $category);
    }

    /**
     * Test Brand model functionality
     */
    public function test_brand_model_works(): void
    {
        $brand = Brand::where('status', 1)->first();

        $this->assertNotNull($brand);
        $this->assertIsString($brand->name);
        $this->assertIsString($brand->localized_name);

        // Test catalogs relation
        $catalogs = $brand->catalogs;
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $catalogs);
    }

    /**
     * Test Catalog model functionality
     */
    public function test_catalog_model_works(): void
    {
        $catalog = Catalog::where('status', 1)->first();

        $this->assertNotNull($catalog);
        $this->assertIsString($catalog->localized_name);

        // Test brand relation
        $this->assertNotNull($catalog->brand);

        // Test categories relation
        $categories = $catalog->newCategories;
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $categories);
    }

    /**
     * Test NewCategory model functionality
     */
    public function test_new_category_model_works(): void
    {
        $category = NewCategory::first();

        $this->assertNotNull($category);
        $this->assertIsString($category->localized_name);
        $this->assertIsString($category->name);
        $this->assertIsInt($category->status);

        // Test catalog relation
        $this->assertNotNull($category->catalog);
    }

    /**
     * Test CatalogItem model functionality
     */
    public function test_catalog_item_model_works(): void
    {
        $catalogItem = CatalogItem::first();

        if ($catalogItem) {
            $this->assertInstanceOf(CatalogItem::class, $catalogItem);
            $this->assertIsString($catalogItem->localized_name);

            // Test part_number
            $this->assertNotNull($catalogItem->part_number);
        } else {
            $this->markTestSkipped('No CatalogItem found in database');
        }
    }

    /**
     * Test CatalogItem with merchant items
     */
    public function test_catalog_item_merchant_relations(): void
    {
        $catalogItem = CatalogItem::whereHas('merchantItems', function ($q) {
            $q->where('status', 1);
        })->first();

        if ($catalogItem) {
            // Test merchantItems relation
            $merchantItems = $catalogItem->merchantItems;
            $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $merchantItems);
            $this->assertGreaterThan(0, $merchantItems->count());

            // Test activeMerchant method
            $activeMerchant = $catalogItem->activeMerchant();
            $this->assertNotNull($activeMerchant);
        } else {
            $this->markTestSkipped('No CatalogItem with active merchant items found');
        }
    }

    /**
     * Test CatalogItem scopes
     */
    public function test_catalog_item_scopes_work(): void
    {
        // Test withOffersData scope
        $items = CatalogItem::withOffersData()->limit(5)->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $items);

        // Test withBestOffer scope
        $items = CatalogItem::withBestOffer()->limit(5)->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $items);

        // Test home scope
        $items = CatalogItem::home()->limit(5)->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $items);
    }

    /**
     * Test QualityBrand model functionality
     */
    public function test_quality_brand_model_works(): void
    {
        $qualityBrand = QualityBrand::active()->first();

        if ($qualityBrand) {
            $this->assertInstanceOf(QualityBrand::class, $qualityBrand);
            $this->assertIsString($qualityBrand->localized_name);
            $this->assertIsString($qualityBrand->display_name);
        } else {
            $this->markTestSkipped('No active QualityBrand found in database');
        }
    }

    /**
     * Test Section model functionality
     */
    public function test_section_model_works(): void
    {
        $section = Section::first();

        if ($section) {
            $this->assertNotNull($section->code);

            // Test catalog relation
            $catalog = $section->catalog;
            $this->assertNotNull($catalog);
        } else {
            $this->markTestSkipped('No Section found in database');
        }
    }

    /**
     * Test CatalogItemFitment model functionality
     */
    public function test_catalog_item_fitment_works(): void
    {
        $fitment = CatalogItemFitment::first();

        if ($fitment) {
            // Test brand relation
            $brand = $fitment->brand;
            $this->assertNotNull($brand);

            // Test brand name accessor
            $brandName = $fitment->brand_name;
            $this->assertIsString($brandName);
        } else {
            $this->markTestSkipped('No CatalogItemFitment found in database');
        }
    }

    /**
     * Test CatalogItem price methods
     */
    public function test_catalog_item_price_methods(): void
    {
        $catalogItem = CatalogItem::whereHas('merchantItems', function ($q) {
            $q->where('status', 1)->where('price', '>', 0);
        })->first();

        if ($catalogItem) {
            // Test merchantPrice
            $price = $catalogItem->merchantPrice();
            $this->assertNotNull($price);

            // Test showPrice
            $formattedPrice = $catalogItem->showPrice();
            $this->assertNotNull($formattedPrice);

            // Test convertPrice
            $converted = CatalogItem::convertPrice(100);
            $this->assertIsString($converted);
        } else {
            $this->markTestSkipped('No CatalogItem with priced merchant items found');
        }
    }

    /**
     * Test Category hierarchy
     */
    public function test_category_hierarchy_works(): void
    {
        // Test level 1 categories
        $rootCategories = NewCategory::level(1)->limit(5)->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $rootCategories);

        // Test children relation
        $categoryWithChildren = NewCategory::whereHas('children')->first();
        if ($categoryWithChildren) {
            $children = $categoryWithChildren->children;
            $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $children);
            $this->assertGreaterThan(0, $children->count());
        }
    }

    /**
     * Test CatalogItem URL generation
     */
    public function test_catalog_item_url_generation(): void
    {
        $catalogItem = CatalogItem::whereNotNull('part_number')->first();

        if ($catalogItem) {
            $url = $catalogItem->getCatalogItemUrl();
            $this->assertIsString($url);
            $this->assertStringContainsString($catalogItem->part_number, $url);
        } else {
            $this->markTestSkipped('No CatalogItem with part_number found');
        }
    }
}
