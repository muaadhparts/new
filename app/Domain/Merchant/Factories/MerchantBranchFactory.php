<?php

namespace App\Domain\Merchant\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Merchant\Models\MerchantBranch;
use App\Domain\Identity\Models\User;
use App\Domain\Shipping\Models\City;

/**
 * Merchant Branch Factory
 *
 * Factory for creating MerchantBranch instances in tests.
 */
class MerchantBranchFactory extends Factory
{
    protected $model = MerchantBranch::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company() . ' - ' . $this->faker->city(),
            'name_ar' => 'فرع ' . $this->faker->numberBetween(1, 100),
            'city_id' => City::factory(),
            'address' => $this->faker->streetAddress(),
            'phone' => '+966' . $this->faker->numberBetween(500000000, 599999999),
            'email' => $this->faker->companyEmail(),
            'latitude' => $this->faker->latitude(21, 27),
            'longitude' => $this->faker->longitude(39, 50),
            'is_main' => false,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Main branch.
     */
    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_main' => true,
        ]);
    }

    /**
     * Secondary branch.
     */
    public function secondary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_main' => false,
        ]);
    }

    /**
     * Active branch.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
        ]);
    }

    /**
     * Inactive branch.
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
     * In specific city.
     */
    public function inCity(City $city): static
    {
        return $this->state(fn (array $attributes) => [
            'city_id' => $city->id,
        ]);
    }

    /**
     * In Riyadh.
     */
    public function inRiyadh(): static
    {
        return $this->state(fn (array $attributes) => [
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);
    }

    /**
     * In Jeddah.
     */
    public function inJeddah(): static
    {
        return $this->state(fn (array $attributes) => [
            'latitude' => 21.4858,
            'longitude' => 39.1925,
        ]);
    }
}
