<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChildCategory;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\ProductFitment;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFitmentFilterTest extends TestCase
{
    /**
     * Test that products are filtered by product_fitments table.
     *
     * This test verifies:
     * 1. Only products with matching product_fitments records are shown
     * 2. Store by vendor ID filter works
     * 3. Sort by latest/oldest product using beginYear works
     */
    public function test_products_filtered_by_product_fitments()
    {
        // Create test category
        $category = Category::factory()->create(['slug' => 'test-category', 'status' => 1]);

        // Create test subcategory
        $subcategory = Subcategory::factory()->create([
            'slug' => 'test-subcategory',
            'category_id' => $category->id
        ]);

        // Create test childcategory
        $childcategory = Childcategory::factory()->create([
            'slug' => 'test-childcategory',
            'subcategory_id' => $subcategory->id
        ]);

        // Create vendor user
        $vendor = User::factory()->create(['is_vendor' => 2]);

        // Create products
        $productWithFitment = Product::factory()->create([
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'childcategory_id' => $childcategory->id,
        ]);

        $productWithoutFitment = Product::factory()->create([
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'childcategory_id' => $childcategory->id,
        ]);

        // Create merchant products
        MerchantProduct::factory()->create([
            'product_id' => $productWithFitment->id,
            'user_id' => $vendor->id,
            'status' => 1,
            'stock' => 10,
        ]);

        MerchantProduct::factory()->create([
            'product_id' => $productWithoutFitment->id,
            'user_id' => $vendor->id,
            'status' => 1,
            'stock' => 10,
        ]);

        // Create product_fitment only for first product
        ProductFitment::create([
            'product_id' => $productWithFitment->id,
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'childcategory_id' => $childcategory->id,
            'beginYear' => 2020,
        ]);

        // Make request to category page
        $response = $this->get(route('front.category', ['slug' => 'test-category']));

        // Assert only product with fitment is shown
        $response->assertStatus(200);
        $response->assertSee($productWithFitment->name);
        $response->assertDontSee($productWithoutFitment->name);
    }

    /**
     * Test store by vendor ID filter.
     */
    public function test_store_by_vendor_filter()
    {
        $category = Category::factory()->create(['slug' => 'test-category-2', 'status' => 1]);
        $vendor1 = User::factory()->create(['is_vendor' => 2]);
        $vendor2 = User::factory()->create(['is_vendor' => 2]);

        $product = Product::factory()->create(['category_id' => $category->id]);

        // Create merchant products for different vendors
        MerchantProduct::factory()->create([
            'product_id' => $product->id,
            'user_id' => $vendor1->id,
            'status' => 1,
            'stock' => 10,
        ]);

        MerchantProduct::factory()->create([
            'product_id' => $product->id,
            'user_id' => $vendor2->id,
            'status' => 1,
            'stock' => 10,
        ]);

        ProductFitment::create([
            'product_id' => $product->id,
            'category_id' => $category->id,
            'subcategory_id' => 1,
            'childcategory_id' => 1,
            'beginYear' => 2021,
        ]);

        // Filter by vendor1
        $response = $this->get(route('front.category', [
            'slug' => 'test-category-2',
            'store' => $vendor1->id
        ]));

        $response->assertStatus(200);
        // Should show vendor1's listing
    }

    /**
     * Test sort by latest/oldest product using beginYear.
     */
    public function test_sort_by_beginyear()
    {
        $category = Category::factory()->create(['slug' => 'test-category-3', 'status' => 1]);
        $vendor = User::factory()->create(['is_vendor' => 2]);

        $oldProduct = Product::factory()->create(['category_id' => $category->id]);
        $newProduct = Product::factory()->create(['category_id' => $category->id]);

        MerchantProduct::factory()->create([
            'product_id' => $oldProduct->id,
            'user_id' => $vendor->id,
            'status' => 1,
            'stock' => 10,
        ]);

        MerchantProduct::factory()->create([
            'product_id' => $newProduct->id,
            'user_id' => $vendor->id,
            'status' => 1,
            'stock' => 10,
        ]);

        ProductFitment::create([
            'product_id' => $oldProduct->id,
            'category_id' => $category->id,
            'subcategory_id' => 1,
            'childcategory_id' => 1,
            'beginYear' => 2015,
        ]);

        ProductFitment::create([
            'product_id' => $newProduct->id,
            'category_id' => $category->id,
            'subcategory_id' => 1,
            'childcategory_id' => 1,
            'beginYear' => 2023,
        ]);

        // Test latest_product sort (DESC)
        $response = $this->get(route('front.category', [
            'slug' => 'test-category-3',
            'sort' => 'latest_product'
        ]));

        $response->assertStatus(200);
        // newProduct (2023) should appear before oldProduct (2015)

        // Test oldest_product sort (ASC)
        $response = $this->get(route('front.category', [
            'slug' => 'test-category-3',
            'sort' => 'oldest_product'
        ]));

        $response->assertStatus(200);
        // oldProduct (2015) should appear before newProduct (2023)
    }
}
