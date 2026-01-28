<?php

namespace App\Domain\Catalog\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\Category;

/**
 * Catalog Item Factory
 *
 * Factory for creating CatalogItem instances in tests.
 */
class CatalogItemFactory extends Factory
{
    protected $model = CatalogItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'name_ar' => $this->faker->words(3, true),
            'slug' => $this->faker->unique()->slug(),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####-????')),
            'part_number' => strtoupper($this->faker->bothify('PN-####-????')),
            'description' => $this->faker->paragraph(),
            'description_ar' => $this->faker->paragraph(),
            'brand_id' => Brand::factory(),
            'new_category_id' => Category::factory(),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate the item is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
        ]);
    }

    /**
     * Indicate the item is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
        ]);
    }

    /**
     * Set a specific brand.
     */
    public function forBrand(Brand $brand): static
    {
        return $this->state(fn (array $attributes) => [
            'brand_id' => $brand->id,
        ]);
    }

    /**
     * Set a specific category.
     */
    public function forCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'new_category_id' => $category->id,
        ]);
    }

    /**
     * With Arabic content.
     */
    public function withArabic(): static
    {
        return $this->state(fn (array $attributes) => [
            'name_ar' => 'منتج ' . $this->faker->numberBetween(1, 1000),
            'description_ar' => 'وصف المنتج التجريبي',
        ]);
    }
}
