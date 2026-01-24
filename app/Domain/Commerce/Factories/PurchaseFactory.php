<?php

namespace App\Domain\Commerce\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Identity\Models\User;

/**
 * Purchase Factory
 *
 * Factory for creating Purchase instances in tests.
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_number' => 'ORD-' . strtoupper($this->faker->unique()->bothify('####-????')),
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'cod',
            'subtotal' => $this->faker->randomFloat(2, 100, 5000),
            'shipping_cost' => $this->faker->randomFloat(2, 10, 50),
            'tax' => 0,
            'discount' => 0,
            'total' => $this->faker->randomFloat(2, 100, 5000),
            'cart' => json_encode([]),
            'shipping_address' => json_encode([
                'name' => $this->faker->name(),
                'phone' => '+966' . $this->faker->numberBetween(500000000, 599999999),
                'address' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
            ]),
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Pending order.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
    }

    /**
     * Confirmed order.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    /**
     * Processing order.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Shipped order.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shipped',
        ]);
    }

    /**
     * Delivered order.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Cancelled order.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Paid order.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Cash on delivery.
     */
    public function cod(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cod',
        ]);
    }

    /**
     * Online payment.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'online',
            'payment_status' => 'paid',
        ]);
    }

    /**
     * For specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * With cart items.
     */
    public function withCart(array $items): static
    {
        $total = collect($items)->sum(fn ($item) => $item['price'] * $item['quantity']);

        return $this->state(fn (array $attributes) => [
            'cart' => json_encode($items),
            'subtotal' => $total,
            'total' => $total + ($attributes['shipping_cost'] ?? 0),
        ]);
    }
}
