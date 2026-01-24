<?php

namespace App\Domain\Shipping\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Shipping\Models\Country;

/**
 * Country Factory
 *
 * Factory for creating Country instances in tests.
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->country(),
            'name_ar' => 'دولة ' . $this->faker->numberBetween(1, 100),
            'code' => strtoupper($this->faker->unique()->lexify('??')),
            'phone_code' => '+' . $this->faker->numberBetween(1, 999),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Active country.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
        ]);
    }

    /**
     * Inactive country.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
        ]);
    }

    /**
     * Saudi Arabia.
     */
    public function saudiArabia(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Saudi Arabia',
            'name_ar' => 'المملكة العربية السعودية',
            'code' => 'SA',
            'phone_code' => '+966',
        ]);
    }

    /**
     * UAE.
     */
    public function uae(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'United Arab Emirates',
            'name_ar' => 'الإمارات العربية المتحدة',
            'code' => 'AE',
            'phone_code' => '+971',
        ]);
    }

    /**
     * Kuwait.
     */
    public function kuwait(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Kuwait',
            'name_ar' => 'الكويت',
            'code' => 'KW',
            'phone_code' => '+965',
        ]);
    }
}
