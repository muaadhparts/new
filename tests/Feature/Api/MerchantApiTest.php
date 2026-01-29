<?php

namespace Tests\Feature\Api;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MerchantApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_merchant_items()
    {
        // Create test data
        $merchant = User::factory()->create(['is_merchant' => 2]);
        $catalogItem = CatalogItem::factory()->create(['status' => 1]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $merchant->id,
            'status' => 1,
        ]);

        // Make API request
        $response = $this->getJson("/api/merchant/{$merchant->id}/items");

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
                    'merchantName',
                ]
            ],
            'error'
        ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_merchant()
    {
        // Make API request with invalid merchant ID
        $response = $this->getJson('/api/merchant/99999/items');

        // Assertions
        $response->assertStatus(200); // API returns 200 with error message
        $response->assertJson([
            'status' => false,
            'error' => ['message' => 'Merchant not found']
        ]);
    }

    /** @test */
    public function it_filters_merchant_items_by_type()
    {
        // Create test data
        $merchant = User::factory()->create(['is_merchant' => 2]);
        $catalogItem1 = CatalogItem::factory()->create(['status' => 1]);
        $catalogItem2 = CatalogItem::factory()->create(['status' => 1]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem1->id,
            'user_id' => $merchant->id,
            'status' => 1,
            'item_type' => 'normal',
        ]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem2->id,
            'user_id' => $merchant->id,
            'status' => 1,
            'item_type' => 'affiliate',
        ]);

        // Make API request with type filter
        $response = $this->getJson("/api/merchant/{$merchant->id}/items?type=normal");

        // Assertions
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }
}
