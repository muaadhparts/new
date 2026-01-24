<?php

namespace App\Domain\Identity\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Identity\Models\OperatorRole;

/**
 * Operator Role Factory
 *
 * Factory for creating OperatorRole instances in tests.
 */
class OperatorRoleFactory extends Factory
{
    protected $model = OperatorRole::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->jobTitle(),
            'permissions' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Super admin with all permissions.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Super Admin',
            'permissions' => json_encode(['*']),
        ]);
    }

    /**
     * Content manager role.
     */
    public function contentManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Content Manager',
            'permissions' => json_encode([
                'catalog.view', 'catalog.create', 'catalog.update',
                'categories.view', 'categories.create', 'categories.update',
                'brands.view', 'brands.create', 'brands.update',
            ]),
        ]);
    }

    /**
     * Order manager role.
     */
    public function orderManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Order Manager',
            'permissions' => json_encode([
                'orders.view', 'orders.update', 'orders.export',
                'merchants.view',
                'shipping.view', 'shipping.update',
            ]),
        ]);
    }

    /**
     * Support role.
     */
    public function support(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Support',
            'permissions' => json_encode([
                'users.view',
                'orders.view',
                'reviews.view', 'reviews.update',
            ]),
        ]);
    }

    /**
     * With specific permissions.
     */
    public function withPermissions(array $permissions): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => json_encode($permissions),
        ]);
    }
}
