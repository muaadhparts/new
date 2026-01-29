<?php

namespace Tests\Feature\Api;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_searches_catalog_items_by_name()
    {
        // Create test data
        $catalogItem = CatalogItem::factory()->create([
            'name' => 'Test Product',
            'status' => 1,
        ]);

        $merchant = User::factory()->create(['is_merchant' => 2]);
        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $merchant->id,
            'status' => 1,
        ]);

        // Make API request
        $response = $this->getJson('/api/search?search=Test');

        // Assertions
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => [
                    'id',
                    'partNumber',
                    'name',
                    'price',
                ]
            ],
            'error'
        ]);
    }

    /** @test */
    public function it_filters_search_by_min_price()
    {
        // Create test data
        $catalogItem1 = CatalogItem::factory()->create(['status' => 1]);
        $catalogItem2 = CatalogItem::factory()->create(['status' => 1]);

        $merchant = User::factory()->create(['is_merchant' => 2]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem1->id,
            'user_id' => $merchant->id,
            'price' => 50,
            'status' => 1,
        ]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem2->id,
            'user_id' => $merchant->id,
            'price' => 150,
            'status' => 1,
        ]);

        // Make API request with min price filter
        $response = $this->getJson('/api/search?min=100');

        // Assertions
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    /** @test */
    public function it_filters_search_by_max_price()
    {
        // Create test data
        $catalogItem = CatalogItem::factory()->create(['status' => 1]);
        $merchant = User::factory()->create(['is_merchant' => 2]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $merchant->id,
            'price' => 50,
            'status' => 1,
        ]);

        // Make API request with max price filter
        $response = $this->getJson('/api/search?max=100');

        // Assertions
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    /** @test */
    public function it_sorts_search_results()
    {
        // Create test data
        $catalogItem1 = CatalogItem::factory()->create(['status' => 1, 'price' => 100]);
        $catalogItem2 = CatalogItem::factory()->create(['status' => 1, 'price' => 50]);

        // Make API request with sort
        $response = $this->getJson('/api/search?sort=price_asc');

        // Assertions
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }
}
