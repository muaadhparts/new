<?php

namespace Tests\Feature\Api;

use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Commerce\Models\FavoriteSeller;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FavoritesApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_user_favorites()
    {
        // Create test data
        $user = User::factory()->create();
        $catalogItem = CatalogItem::factory()->create(['status' => 1]);

        FavoriteSeller::create([
            'user_id' => $user->id,
            'catalog_item_id' => $catalogItem->id,
        ]);

        // Authenticate user
        Sanctum::actingAs($user);

        // Make API request
        $response = $this->getJson('/api/user/favorites');

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
    public function it_adds_item_to_favorites()
    {
        // Create test data
        $user = User::factory()->create();
        $catalogItem = CatalogItem::factory()->create(['status' => 1]);

        // Authenticate user
        Sanctum::actingAs($user);

        // Make API request
        $response = $this->postJson('/api/user/favorites/add', [
            'catalog_item_id' => $catalogItem->id,
        ]);

        // Assertions
        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'data' => ['message' => 'Successfully Added To Favorites.']
        ]);

        // Verify in database
        $this->assertDatabaseHas('favorite_sellers', [
            'user_id' => $user->id,
            'catalog_item_id' => $catalogItem->id,
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_favorites()
    {
        // Create test data
        $user = User::factory()->create();
        $catalogItem = CatalogItem::factory()->create(['status' => 1]);

        // Add to favorites first time
        FavoriteSeller::create([
            'user_id' => $user->id,
            'catalog_item_id' => $catalogItem->id,
        ]);

        // Authenticate user
        Sanctum::actingAs($user);

        // Try to add again
        $response = $this->postJson('/api/user/favorites/add', [
            'catalog_item_id' => $catalogItem->id,
        ]);

        // Assertions
        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'error' => ['message' => 'Already Added To Favorites.']
        ]);
    }

    /** @test */
    public function it_removes_item_from_favorites()
    {
        // Create test data
        $user = User::factory()->create();
        $catalogItem = CatalogItem::factory()->create(['status' => 1]);

        FavoriteSeller::create([
            'user_id' => $user->id,
            'catalog_item_id' => $catalogItem->id,
        ]);

        // Authenticate user
        Sanctum::actingAs($user);

        // Make API request
        $response = $this->deleteJson("/api/user/favorites/remove/{$catalogItem->id}");

        // Assertions
        $response->assertStatus(200);
        $response->assertJson([
            'status' => true,
            'data' => ['message' => 'Successfully Removed From Favorites.']
        ]);

        // Verify removed from database
        $this->assertDatabaseMissing('favorite_sellers', [
            'user_id' => $user->id,
            'catalog_item_id' => $catalogItem->id,
        ]);
    }

    /** @test */
    public function it_sorts_favorites()
    {
        // Create test data
        $user = User::factory()->create();
        $catalogItem1 = CatalogItem::factory()->create(['status' => 1, 'price' => 100]);
        $catalogItem2 = CatalogItem::factory()->create(['status' => 1, 'price' => 50]);

        FavoriteSeller::create(['user_id' => $user->id, 'catalog_item_id' => $catalogItem1->id]);
        FavoriteSeller::create(['user_id' => $user->id, 'catalog_item_id' => $catalogItem2->id]);

        // Authenticate user
        Sanctum::actingAs($user);

        // Make API request with sort
        $response = $this->getJson('/api/user/favorites?sort=price_asc');

        // Assertions
        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }
}
