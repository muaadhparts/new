<?php

namespace App\Domain\Commerce\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Identity\Models\User;

/**
 * Merchant Purchase Factory
 *
 * Factory for creating MerchantPurchase instances in tests.
 */
class MerchantPurchaseFactory extends Factory
{
    protected $model = MerchantPurchase::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'purchase_id' => Purchase::factory(),
            'user_id' => User::factory(),
            'status' => 'pending',
            'cart' => json_encode([]),
            'subtotal' => $this->faker->randomFloat(2, 50, 2000),
            'shipping_cost' => $this->faker->randomFloat(2, 5, 30),
            'total' => $this->faker->randomFloat(2, 50, 2000),
            'commission_rate' => 5.00,
            'commission_amount' => 0,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Accepted status.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'accepted',
        ]);
    }

    /**
     * Ready for pickup.
     */
    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'ready',
        ]);
    }

    /**
     * Shipped status.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shipped',
        ]);
    }

    /**
     * Delivered status.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
        ]);
    }

    /**
     * Cancelled status.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * For specific purchase.
     */
    public function forPurchase(Purchase $purchase): static
    {
        return $this->state(fn (array $attributes) => [
            'purchase_id' => $purchase->id,
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
     * With commission calculated.
     */
    public function withCommission(float $rate = 5.00): static
    {
        return $this->state(function (array $attributes) use ($rate) {
            $subtotal = $attributes['subtotal'] ?? 100;
            return [
                'commission_rate' => $rate,
                'commission_amount' => $subtotal * ($rate / 100),
            ];
        });
    }
}
