<?php

namespace Tests\Unit\Services;

use App\Domain\Catalog\Builders\CatalogItemCardDTOBuilder;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogItemCardDTOBuilderTest extends TestCase
{
    use RefreshDatabase;

    private CatalogItemCardDTOBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = app(CatalogItemCardDTOBuilder::class);
    }

    /** @test */
    public function it_builds_dto_from_catalog_item_first()
    {
        // Create test data
        $catalogItem = CatalogItem::factory()->create([
            'part_number' => 'TEST123',
            'label_en' => 'Test Product',
            'label_ar' => 'منتج تجريبي',
        ]);

        $merchant = User::factory()->create(['is_merchant' => 2]);

        MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $merchant->id,
            'price' => 100,
            'stock' => 10,
            'status' => 1,
        ]);

        // Load relationships
        $catalogItem->load(['merchantItems' => function($q) {
            $q->where('status', 1)->with(['user', 'qualityBrand', 'merchantBranch']);
        }]);

        // Build DTO
        $dto = $this->builder->fromCatalogItemFirst($catalogItem);

        // Assertions
        $this->assertNotNull($dto);
        $this->assertEquals('TEST123', $dto->partNumber);
        $this->assertNotNull($dto->name);
        $this->assertNotNull($dto->price);
        $this->assertIsBool($dto->hasStock);
    }

    /** @test */
    public function it_builds_dto_from_merchant_item()
    {
        // Create test data
        $catalogItem = CatalogItem::factory()->create([
            'part_number' => 'TEST456',
            'label_en' => 'Test Product 2',
        ]);

        $merchant = User::factory()->create(['is_merchant' => 2]);

        $merchantItem = MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $merchant->id,
            'price' => 200,
            'stock' => 5,
            'status' => 1,
        ]);

        // Load relationships
        $merchantItem->load(['catalogItem', 'user', 'qualityBrand', 'merchantBranch']);

        // Build DTO
        $dto = $this->builder->fromMerchantItem($merchantItem);

        // Assertions
        $this->assertNotNull($dto);
        $this->assertEquals('TEST456', $dto->partNumber);
        $this->assertNotNull($dto->merchantName);
        $this->assertTrue($dto->hasStock);
    }

    /** @test */
    public function it_handles_out_of_stock_items()
    {
        // Create test data
        $catalogItem = CatalogItem::factory()->create();
        $merchant = User::factory()->create(['is_merchant' => 2]);

        $merchantItem = MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $merchant->id,
            'stock' => 0, // Out of stock
            'status' => 1,
        ]);

        $merchantItem->load(['catalogItem', 'user']);

        // Build DTO
        $dto = $this->builder->fromMerchantItem($merchantItem);

        // Assertions
        $this->assertFalse($dto->hasStock);
    }

    /** @test */
    public function it_calculates_discount_correctly()
    {
        // Create test data with discount
        $catalogItem = CatalogItem::factory()->create();
        $merchant = User::factory()->create(['is_merchant' => 2]);

        $merchantItem = MerchantItem::factory()->create([
            'catalog_item_id' => $catalogItem->id,
            'user_id' => $merchant->id,
            'price' => 100,
            'discount_price' => 80, // 20% discount
            'status' => 1,
        ]);

        $merchantItem->load(['catalogItem', 'user']);

        // Build DTO
        $dto = $this->builder->fromMerchantItem($merchantItem);

        // Assertions
        $this->assertTrue($dto->hasDiscount);
        $this->assertNotNull($dto->discountPercentage);
    }
}
