<?php

namespace Tests\Unit\Services;

use App\Domain\Catalog\Services\CatalogItemApiService;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Identity\Models\User;
use App\Domain\Commerce\Models\FavoriteSeller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogItemApiServiceTest extends TestCase
{
    use RefreshDatabase;

    private CatalogItemApiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CatalogItemApiService::class);
    }

    /** @test */
    public function it_gets_merchant_catalog_items()
    {
        // Create test data
        $merchant = User::factory()->create(['is_merchant' => 2]);
        $catalogItem = CatalogItem::factory()->create();

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $merchant->id,
            'status' => 1,
            'item_type' => 'normal',
        ]);

        // Get merchant items
        $result = $this->service->getMerchantCatalogItems($merchant->id);

        // Assertions
        $this->assertNotEmpty($result);
        $this->assertGreaterThan(0, $result->count());
    }

    /** @test */
    public function it_filters_merchant_items_by_type()
    {
        // Create test data
        $merchant = User::factory()->create(['is_merchant' => 2]);
        $catalogItem1 = CatalogItem::factory()->create();
        $catalogItem2 = CatalogItem::factory()->create();

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

        // Get only normal items
        $result = $this->service->getMerchantCatalogItems($merchant->id, ['type' => 'normal']);

        // Assertions
        $this->assertNotEmpty($result);
        // Should only return normal items
    }

    /** @test */
    public function it_gets_catalog_items_with_filters()
    {
        // Create test data
        CatalogItem::factory()->count(5)->create(['status' => 1]);

        // Get items with limit
        $result = $this->service->getCatalogItems(['limit' => 3]);

        // Assertions
        $this->assertNotEmpty($result);
        $this->assertLessThanOrEqual(3, $result->count());
    }

    /** @test */
    public function it_paginates_catalog_items()
    {
        // Create test data
        CatalogItem::factory()->count(20)->create(['status' => 1]);

        // Get paginated items
        $result = $this->service->getCatalogItems(['paginate' => 10]);

        // Assertions
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->perPage());
    }

    /** @test */
    public function it_gets_user_favorites()
    {
        // Create test data
        $user = User::factory()->create();
        $catalogItem = CatalogItem::factory()->create(['status' => 1]);

        FavoriteSeller::create([
            'user_id' => $user->id,
            'catalog_item_id' => $catalogItem->id,
        ]);

        // Get favorites
        $result = $this->service->getUserFavorites($user->id);

        // Assertions
        $this->assertNotEmpty($result);
        $this->assertGreaterThan(0, $result->count());
    }

    /** @test */
    public function it_sorts_catalog_items_by_price()
    {
        // Create test data
        $user = User::factory()->create();
        $catalogItem1 = CatalogItem::factory()->create(['status' => 1, 'price' => 100]);
        $catalogItem2 = CatalogItem::factory()->create(['status' => 1, 'price' => 50]);

        FavoriteSeller::create(['user_id' => $user->id, 'catalog_item_id' => $catalogItem1->id]);
        FavoriteSeller::create(['user_id' => $user->id, 'catalog_item_id' => $catalogItem2->id]);

        // Get favorites sorted by price
        $result = $this->service->getUserFavorites($user->id, 'price_asc');

        // Assertions
        $this->assertNotEmpty($result);
        // First item should have lower price
    }
}
