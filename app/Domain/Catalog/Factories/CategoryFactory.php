<?php

namespace App\Domain\Catalog\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Catalog\Models\Category;

/**
 * New Category Factory
 *
 * Factory for creating Category instances in tests.
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'name_ar' => $name,
            'slug' => \Str::slug($name),
            'parent_id' => null,
            'level' => 1,
            'icon' => null,
            'image' => null,
            'status' => 1,
            'sort_order' => $this->faker->numberBetween(1, 100),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Root category (level 1).
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
            'level' => 1,
        ]);
    }

    /**
     * Child category with parent.
     */
    public function childOf(Category $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent->id,
            'level' => $parent->level + 1,
        ]);
    }

    /**
     * Indicate the category is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
        ]);
    }

    /**
     * Indicate the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
        ]);
    }

    /**
     * Auto parts category.
     */
    public function autoParts(): static
    {
        $categories = ['Engine Parts', 'Brake System', 'Suspension', 'Electrical', 'Body Parts', 'Interior'];

        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement($categories),
        ]);
    }
}
