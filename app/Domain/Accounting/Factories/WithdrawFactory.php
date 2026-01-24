<?php

namespace App\Domain\Accounting\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Accounting\Models\Withdraw;
use App\Domain\Identity\Models\User;

/**
 * Withdraw Factory
 *
 * Factory for creating Withdraw instances in tests.
 */
class WithdrawFactory extends Factory
{
    protected $model = Withdraw::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->merchant(),
            'amount' => $this->faker->randomFloat(2, 100, 5000),
            'method' => 'bank_transfer',
            'status' => 'pending',
            'bank_name' => $this->faker->randomElement(['Al Rajhi Bank', 'NCB', 'SABB', 'Riyad Bank']),
            'account_number' => $this->faker->numerify('SA####################'),
            'account_name' => $this->faker->name(),
            'reference' => null,
            'processed_at' => null,
            'reject_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
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
     * Pending withdrawal.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'processed_at' => null,
        ]);
    }

    /**
     * Processing withdrawal.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Completed withdrawal.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'processed_at' => now(),
            'reference' => 'REF-' . $this->faker->uuid(),
        ]);
    }

    /**
     * Rejected withdrawal.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reject_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Bank transfer method.
     */
    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'bank_transfer',
        ]);
    }

    /**
     * Specific amount.
     */
    public function amount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }
}
