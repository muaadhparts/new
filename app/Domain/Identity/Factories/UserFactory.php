<?php

namespace App\Domain\Identity\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Domain\Identity\Models\User;

/**
 * User Factory
 *
 * Factory for creating User instances in tests.
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '+966' . $this->faker->unique()->numberBetween(500000000, 599999999),
            'password' => static::$password ??= Hash::make('password'),
            'email_verified_at' => now(),
            'is_merchant' => 0,
            'status' => 1,
            'remember_token' => \Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Merchant user.
     */
    public function merchant(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_merchant' => 1,
        ]);
    }

    /**
     * Regular customer.
     */
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_merchant' => 0,
        ]);
    }

    /**
     * Active user.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
        ]);
    }

    /**
     * Inactive user.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
        ]);
    }

    /**
     * Banned user.
     */
    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 2,
        ]);
    }

    /**
     * With specific phone.
     */
    public function withPhone(string $phone): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => $phone,
        ]);
    }

    /**
     * With Arabic name.
     */
    public function arabic(): static
    {
        $names = ['أحمد محمد', 'علي عبدالله', 'محمد سعد', 'فهد خالد', 'سارة أحمد'];

        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement($names),
        ]);
    }
}
