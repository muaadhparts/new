<?php

namespace App\Domain\Catalog\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Catalog\Models\CatalogReview;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Identity\Models\User;

/**
 * Catalog Review Factory
 *
 * Factory for creating CatalogReview instances in tests.
 */
class CatalogReviewFactory extends Factory
{
    protected $model = CatalogReview::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'catalog_item_id' => CatalogItem::factory(),
            'user_id' => User::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'title' => $this->faker->sentence(4),
            'review' => $this->faker->paragraph(),
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Pending review.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Approved review.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Rejected review.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    /**
     * High rating (4-5 stars).
     */
    public function highRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->numberBetween(4, 5),
        ]);
    }

    /**
     * Low rating (1-2 stars).
     */
    public function lowRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => $this->faker->numberBetween(1, 2),
        ]);
    }

    /**
     * For specific catalog item.
     */
    public function forItem(CatalogItem $item): static
    {
        return $this->state(fn (array $attributes) => [
            'catalog_item_id' => $item->id,
        ]);
    }

    /**
     * By specific user.
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
