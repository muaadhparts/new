<?php

namespace App\Domain\Shipping\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Shipping\Models\City;
use App\Domain\Shipping\Models\Country;

/**
 * City Factory
 *
 * Factory for creating City instances in tests.
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->city(),
            'name_ar' => 'مدينة ' . $this->faker->numberBetween(1, 100),
            'country_id' => Country::factory(),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Active city.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
        ]);
    }

    /**
     * Inactive city.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
        ]);
    }

    /**
     * Riyadh city.
     */
    public function riyadh(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Riyadh',
            'name_ar' => 'الرياض',
        ]);
    }

    /**
     * Jeddah city.
     */
    public function jeddah(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Jeddah',
            'name_ar' => 'جدة',
        ]);
    }

    /**
     * Dammam city.
     */
    public function dammam(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Dammam',
            'name_ar' => 'الدمام',
        ]);
    }

    /**
     * For specific country.
     */
    public function inCountry(Country $country): static
    {
        return $this->state(fn (array $attributes) => [
            'country_id' => $country->id,
        ]);
    }
}
