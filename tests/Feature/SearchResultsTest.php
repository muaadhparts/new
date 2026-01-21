<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\MerchantItem;
use App\Models\User;
use App\Models\QualityBrand;
use App\Models\MerchantBranch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature Tests for Search Results Page
 *
 * Tests the new /search page functionality:
 * - Search by part number
 * - Search by name
 * - Display of unified cards
 * - Alternatives display
 */
class SearchResultsTest extends TestCase
{
    use RefreshDatabase;

    protected User $merchant;
    protected QualityBrand $qualityBrand;
    protected MerchantBranch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a merchant user
        $this->merchant = User::factory()->create([
            'is_merchant' => 2, // Active merchant
        ]);

        // Create quality brand
        $this->qualityBrand = QualityBrand::factory()->create([
            'name_en' => 'Original',
            'name_ar' => 'أصلي',
        ]);

        // Create branch
        $this->branch = MerchantBranch::factory()->create([
            'user_id' => $this->merchant->id,
            'warehouse_name' => 'Main Warehouse',
        ]);
    }

    /** @test */
    public function search_page_loads_successfully()
    {
        $response = $this->get(route('front.search-results'));

        $response->assertStatus(200);
        $response->assertViewIs('frontend.search-results');
    }

    /** @test */
    public function search_page_shows_empty_state_without_query()
    {
        $response = $this->get(route('front.search-results'));

        $response->assertStatus(200);
        $response->assertSee(__('Enter a search term'));
    }

    /** @test */
    public function search_by_part_number_returns_results()
    {
        // Create catalog item
        $catalogItem = CatalogItem::factory()->create([
            'part_number' => 'TEST123',
            'label_en' => 'Test Part',
            'label_ar' => 'قطعة تجريبية',
        ]);

        // Create merchant item (offer)
        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $this->merchant->id,
            'merchant_branch_id' => $this->branch->id,
            'quality_brand_id' => $this->qualityBrand->id,
            'price' => 100,
            'stock' => 10,
            'status' => 1,
        ]);

        $response = $this->get(route('front.search-results', ['q' => 'TEST123']));

        $response->assertStatus(200);
        $response->assertSee('TEST123');
        $response->assertSee('Test Part');
    }

    /** @test */
    public function search_by_partial_part_number_returns_results()
    {
        $catalogItem = CatalogItem::factory()->create([
            'part_number' => 'ABC12345',
            'label_en' => 'ABC Part',
        ]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $this->merchant->id,
            'merchant_branch_id' => $this->branch->id,
            'quality_brand_id' => $this->qualityBrand->id,
            'price' => 50,
            'stock' => 5,
            'status' => 1,
        ]);

        // Search with prefix
        $response = $this->get(route('front.search-results', ['q' => 'ABC']));

        $response->assertStatus(200);
        $response->assertSee('ABC12345');
    }

    /** @test */
    public function search_by_arabic_name_returns_results()
    {
        $catalogItem = CatalogItem::factory()->create([
            'part_number' => 'ARAB001',
            'label_ar' => 'فلتر زيت',
            'label_en' => 'Oil Filter',
        ]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $this->merchant->id,
            'merchant_branch_id' => $this->branch->id,
            'quality_brand_id' => $this->qualityBrand->id,
            'price' => 25,
            'stock' => 20,
            'status' => 1,
        ]);

        $response = $this->get(route('front.search-results', ['q' => 'فلتر']));

        $response->assertStatus(200);
        $response->assertSee('ARAB001');
    }

    /** @test */
    public function search_by_english_name_returns_results()
    {
        $catalogItem = CatalogItem::factory()->create([
            'part_number' => 'ENG001',
            'label_en' => 'Brake Pad',
            'label_ar' => 'فحمات فرامل',
        ]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $this->merchant->id,
            'merchant_branch_id' => $this->branch->id,
            'quality_brand_id' => $this->qualityBrand->id,
            'price' => 75,
            'stock' => 8,
            'status' => 1,
        ]);

        $response = $this->get(route('front.search-results', ['q' => 'Brake']));

        $response->assertStatus(200);
        $response->assertSee('ENG001');
    }

    /** @test */
    public function search_returns_no_results_for_non_matching_query()
    {
        $response = $this->get(route('front.search-results', ['q' => 'NONEXISTENT999']));

        $response->assertStatus(200);
        $response->assertSee(__('No results found'));
    }

    /** @test */
    public function search_requires_minimum_two_characters()
    {
        $response = $this->get(route('front.search-results', ['q' => 'A']));

        $response->assertStatus(200);
        // Should show empty state, not search results
        $response->assertViewHas('count', 0);
    }

    /** @test */
    public function search_results_include_offers_count()
    {
        $catalogItem = CatalogItem::factory()->create([
            'part_number' => 'MULTI001',
        ]);

        // Create multiple offers from different merchants
        $merchant2 = User::factory()->create(['is_merchant' => 2]);
        $branch2 = MerchantBranch::factory()->create(['user_id' => $merchant2->id]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $this->merchant->id,
            'merchant_branch_id' => $this->branch->id,
            'quality_brand_id' => $this->qualityBrand->id,
            'price' => 100,
            'stock' => 5,
            'status' => 1,
        ]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $merchant2->id,
            'merchant_branch_id' => $branch2->id,
            'quality_brand_id' => $this->qualityBrand->id,
            'price' => 90,
            'stock' => 3,
            'status' => 1,
        ]);

        $response = $this->get(route('front.search-results', ['q' => 'MULTI001']));

        $response->assertStatus(200);
        // Card should show offers count
        $response->assertViewHas('cards', function ($cards) {
            return $cards->first()->offersCount === 2;
        });
    }

    /** @test */
    public function search_results_show_lowest_price()
    {
        $catalogItem = CatalogItem::factory()->create([
            'part_number' => 'PRICE001',
        ]);

        // Create offers with different prices
        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $this->merchant->id,
            'merchant_branch_id' => $this->branch->id,
            'quality_brand_id' => $this->qualityBrand->id,
            'price' => 200, // Higher price
            'stock' => 5,
            'status' => 1,
        ]);

        $merchant2 = User::factory()->create(['is_merchant' => 2]);
        $branch2 = MerchantBranch::factory()->create(['user_id' => $merchant2->id]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $merchant2->id,
            'merchant_branch_id' => $branch2->id,
            'quality_brand_id' => $this->qualityBrand->id,
            'price' => 150, // Lower price
            'stock' => 3,
            'status' => 1,
        ]);

        $response = $this->get(route('front.search-results', ['q' => 'PRICE001']));

        $response->assertStatus(200);
        // Should show lowest price (150)
        $response->assertViewHas('cards', function ($cards) {
            return $cards->first()->price === 150.0;
        });
    }

    /** @test */
    public function search_excludes_inactive_merchant_items()
    {
        $catalogItem = CatalogItem::factory()->create([
            'part_number' => 'ACTIVE001',
        ]);

        // Inactive offer (status = 0)
        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $this->merchant->id,
            'merchant_branch_id' => $this->branch->id,
            'quality_brand_id' => $this->qualityBrand->id,
            'price' => 100,
            'stock' => 5,
            'status' => 0, // Inactive
        ]);

        $response = $this->get(route('front.search-results', ['q' => 'ACTIVE001']));

        $response->assertStatus(200);
        // Should find the catalog item but with no merchant (hasMerchant = false)
        $response->assertViewHas('cards', function ($cards) {
            return $cards->isNotEmpty() && !$cards->first()->hasMerchant;
        });
    }

    /** @test */
    public function search_excludes_non_merchant_users()
    {
        $catalogItem = CatalogItem::factory()->create([
            'part_number' => 'MERCH001',
        ]);

        // Regular user (not a merchant)
        $regularUser = User::factory()->create(['is_merchant' => 0]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $regularUser->id,
            'merchant_branch_id' => $this->branch->id,
            'quality_brand_id' => $this->qualityBrand->id,
            'price' => 100,
            'stock' => 5,
            'status' => 1,
        ]);

        $response = $this->get(route('front.search-results', ['q' => 'MERCH001']));

        $response->assertStatus(200);
        // Should not include offer from non-merchant
        $response->assertViewHas('cards', function ($cards) {
            return $cards->isNotEmpty() && !$cards->first()->hasMerchant;
        });
    }
}
