<?php

namespace App\Domain\Shipping\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Shipping\Models\Courier;

/**
 * Shipment Tracking Factory
 *
 * Factory for creating ShipmentTracking instances in tests.
 */
class ShipmentTrackingFactory extends Factory
{
    protected $model = ShipmentTracking::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'merchant_purchase_id' => MerchantPurchase::factory(),
            'courier_id' => Courier::factory(),
            'tracking_number' => strtoupper($this->faker->bothify('TRK#########??')),
            'status' => 'pending',
            'shipped_at' => null,
            'delivered_at' => null,
            'estimated_delivery' => now()->addDays(3),
            'history' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Pending shipment.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'shipped_at' => null,
        ]);
    }

    /**
     * Picked up shipment.
     */
    public function pickedUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'picked_up',
            'shipped_at' => now(),
        ]);
    }

    /**
     * In transit shipment.
     */
    public function inTransit(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_transit',
            'shipped_at' => now()->subDay(),
        ]);
    }

    /**
     * Out for delivery.
     */
    public function outForDelivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'out_for_delivery',
            'shipped_at' => now()->subDays(2),
        ]);
    }

    /**
     * Delivered shipment.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'shipped_at' => now()->subDays(3),
            'delivered_at' => now(),
        ]);
    }

    /**
     * Failed delivery.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'shipped_at' => now()->subDays(2),
        ]);
    }

    /**
     * For specific merchant purchase.
     */
    public function forMerchantPurchase(MerchantPurchase $purchase): static
    {
        return $this->state(fn (array $attributes) => [
            'merchant_purchase_id' => $purchase->id,
        ]);
    }

    /**
     * With specific courier.
     */
    public function withCourier(Courier $courier): static
    {
        return $this->state(fn (array $attributes) => [
            'courier_id' => $courier->id,
        ]);
    }

    /**
     * With tracking history.
     */
    public function withHistory(): static
    {
        return $this->state(fn (array $attributes) => [
            'history' => json_encode([
                ['status' => 'pending', 'date' => now()->subDays(3)->toIso8601String(), 'message' => 'Order received'],
                ['status' => 'picked_up', 'date' => now()->subDays(2)->toIso8601String(), 'message' => 'Picked up from merchant'],
                ['status' => 'in_transit', 'date' => now()->subDay()->toIso8601String(), 'message' => 'In transit to destination'],
            ]),
        ]);
    }
}
