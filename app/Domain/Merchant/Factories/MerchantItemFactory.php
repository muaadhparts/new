<?php

namespace App\Domain\Merchant\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Identity\Models\User;
use App\Domain\Merchant\Models\MerchantBranch;

/**
 * Merchant Item Factory
 *
 * Factory for creating MerchantItem instances in tests.
 */
class MerchantItemFactory extends Factory
{
    protected $model = MerchantItem::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'catalog_item_id' => CatalogItem::factory(),
            'user_id' => User::factory(),
            'merchant_branch_id' => MerchantBranch::factory(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'previous_price' => null,
            'stock' => $this->faker->numberBetween(0, 100),
            'min_order_qty' => 1,
            'max_order_qty' => 10,
            'discount' => 0,
            'discount_type' => 'fixed',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * In stock item.
     */
    public function inStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $this->faker->numberBetween(10, 100),
        ]);
    }

    /**
     * Out of stock item.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }

    /**
     * Low stock item.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $this->faker->numberBetween(1, 5),
        ]);
    }

    /**
     * With discount.
     */
    public function withDiscount(int $discount = 10, string $type = 'percent'): static
    {
        return $this->state(fn (array $attributes) => [
            'discount' => $discount,
            'discount_type' => $type,
            'previous_price' => $attributes['price'] ?? $this->faker->randomFloat(2, 100, 1000),
        ]);
    }

    /**
     * Active item.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
        ]);
    }

    /**
     * Inactive item.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
        ]);
    }

    /**
     * For specific merchant.
     */
    public function forMerchant(User $merchant): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $merchant->id,
        ]);
    }

    /**
     * For specific catalog item.
     */
    public function forCatalogItem(CatalogItem $item): static
    {
        return $this->state(fn (array $attributes) => [
            'catalog_item_id' => $item->id,
        ]);
    }

    /**
     * For specific branch.
     */
    public function forBranch(MerchantBranch $branch): static
    {
        return $this->state(fn (array $attributes) => [
            'merchant_branch_id' => $branch->id,
            'user_id' => $branch->user_id,
        ]);
    }
}
