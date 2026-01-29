<?php

namespace Tests\Feature\Api;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogItemsApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_catalog_items_list()
    {
        // Create test data
        CatalogItem::factory()->count(5)->create(['status' => 1]);

        // Make API request
        $response = $this->getJson('/api/catalogitems');

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
                    'hasStock',
                ]
            ],
            'error'
        ]);
    }

    /** @test */
    public function it_filters_catalog_items_by_type()
    {
        // Create test data
        $catalogItem = CatalogItem::factory()->create(['status' => 1]);
        $merchant = User::factory()->create(['is_merchant' => 2]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $merchant->id,
            'status' => 1,
            'item_type' => 'normal',
        ]);

        // Make API request with filter
        $response = $this->getJson('/api/catalogitems?item_type=normal');

        // Assertions
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    /** @test */
    public function it_limits_catalog_items_results()
    {
        // Create test data
        CatalogItem::factory()->count(10)->create(['status' => 1]);

        // Make API request with limit
        $response = $this->getJson('/api/catalogitems?limit=5');

        // Assertions
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertLessThanOrEqual(5, count($data));
    }

    /** @test */
    public function it_paginates_catalog_items()
    {
        // Create test data
        CatalogItem::factory()->count(20)->create(['status' => 1]);

        // Make API request with pagination
        $response = $this->getJson('/api/catalogitems?paginate=10');

        // Assertions
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'current_page',
                'data',
                'per_page',
                'total',
            ],
            'error'
        ]);
    }

    /** @test */
    public function it_returns_latest_catalog_items()
    {
        // Create test data
        CatalogItem::factory()->count(5)->create(['status' => 1]);

        // Make API request with highlight
        $response = $this->getJson('/api/catalogitems?highlight=latest');

        // Assertions
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }
}
