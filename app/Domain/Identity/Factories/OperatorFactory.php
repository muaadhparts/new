<?php

namespace App\Domain\Identity\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Domain\Identity\Models\Operator;
use App\Domain\Identity\Models\OperatorRole;

/**
 * Operator Factory
 *
 * Factory for creating Operator (admin) instances in tests.
 */
class OperatorFactory extends Factory
{
    protected $model = Operator::class;

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
            'password' => static::$password ??= Hash::make('password'),
            'role_id' => OperatorRole::factory(),
            'status' => 1,
            'photo' => null,
            'remember_token' => \Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Super admin role.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => OperatorRole::factory()->superAdmin(),
        ]);
    }

    /**
     * Active operator.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 1,
        ]);
    }

    /**
     * Inactive operator.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 0,
        ]);
    }

    /**
     * With specific role.
     */
    public function withRole(OperatorRole $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => $role->id,
        ]);
    }

    /**
     * With photo.
     */
    public function withPhoto(): static
    {
        return $this->state(fn (array $attributes) => [
            'photo' => 'operators/' . $this->faker->uuid() . '.jpg',
        ]);
    }
}
