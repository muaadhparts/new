<?php

namespace Database\Factories;

use App\Models\MerchantItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerchantItemFactory extends Factory
{
    protected $model = MerchantItem::class;

    public function definition(): array
    {
        return [
            'catalog_item_id' => null, // Must be provided
            'user_id' => null, // Must be provided
            'merchant_branch_id' => null, // Must be provided
            'quality_brand_id' => null, // Must be provided
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'previous_price' => null,
            'stock' => $this->faker->numberBetween(0, 100),
            'preordered' => 0,
            'minimum_qty' => 1,
            'status' => 1,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }
}
