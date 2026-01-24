<?php

namespace App\Domain\Shipping\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Shipping\Models\Courier;

/**
 * Courier Factory
 *
 * Factory for creating Courier instances in tests.
 */
class CourierFactory extends Factory
{
    protected $model = Courier::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $couriers = ['SMSA', 'Aramex', 'DHL', 'FedEx', 'J&T Express', 'Naqel'];
        $name = $this->faker->randomElement($couriers);

        return [
            'name' => $name . ' ' . $this->faker->unique()->numberBetween(1, 100),
            'code' => strtolower(\Str::slug($name)) . '_' . $this->faker->numberBetween(1, 100),
            'logo' => null,
            'tracking_url' => 'https://track.' . strtolower($name) . '.com/?tracking={tracking_number}',
            'api_endpoint' => null,
            'api_key' => null,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Active courier.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
        ]);
    }

    /**
     * Inactive courier.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
        ]);
    }

    /**
     * SMSA courier.
     */
    public function smsa(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'SMSA Express',
            'code' => 'smsa',
            'tracking_url' => 'https://www.smsaexpress.com/track?awb={tracking_number}',
        ]);
    }

    /**
     * Aramex courier.
     */
    public function aramex(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Aramex',
            'code' => 'aramex',
            'tracking_url' => 'https://www.aramex.com/track/shipments?shipment={tracking_number}',
        ]);
    }

    /**
     * With API integration.
     */
    public function withApi(): static
    {
        return $this->state(fn (array $attributes) => [
            'api_endpoint' => 'https://api.courier.com/v1',
            'api_key' => $this->faker->uuid(),
        ]);
    }

    /**
     * With logo.
     */
    public function withLogo(): static
    {
        return $this->state(fn (array $attributes) => [
            'logo' => 'couriers/' . $this->faker->uuid() . '.png',
        ]);
    }
}
