<?php

namespace App\Domain\Catalog\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Catalog\Models\Brand;

/**
 * Brand Factory
 *
 * Factory for creating Brand instances in tests.
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'name' => $name,
            'name_ar' => $name,
            'slug' => \Str::slug($name),
            'logo' => null,
            'description' => $this->faker->sentence(),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate the brand is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
        ]);
    }

    /**
     * Indicate the brand is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
        ]);
    }

    /**
     * With logo.
     */
    public function withLogo(): static
    {
        return $this->state(fn (array $attributes) => [
            'logo' => 'brands/' . $this->faker->uuid() . '.png',
        ]);
    }

    /**
     * Popular brand (Toyota, Honda, etc.).
     */
    public function popular(): static
    {
        $brands = ['Toyota', 'Honda', 'Nissan', 'Hyundai', 'Kia', 'Ford', 'Chevrolet'];

        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement($brands),
        ]);
    }
}
