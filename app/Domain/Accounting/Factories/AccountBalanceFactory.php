<?php

namespace App\Domain\Accounting\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Accounting\Models\AccountBalance;
use App\Domain\Identity\Models\User;

/**
 * Account Balance Factory
 *
 * Factory for creating AccountBalance instances in tests.
 */
class AccountBalanceFactory extends Factory
{
    protected $model = AccountBalance::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->merchant(),
            'current_balance' => $this->faker->randomFloat(2, 0, 10000),
            'pending_balance' => $this->faker->randomFloat(2, 0, 5000),
            'total_earned' => $this->faker->randomFloat(2, 0, 50000),
            'total_withdrawn' => $this->faker->randomFloat(2, 0, 20000),
            'total_commission' => $this->faker->randomFloat(2, 0, 5000),
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
     * Zero balance.
     */
    public function zero(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_balance' => 0,
            'pending_balance' => 0,
            'total_earned' => 0,
            'total_withdrawn' => 0,
            'total_commission' => 0,
        ]);
    }

    /**
     * With available balance.
     */
    public function withBalance(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'current_balance' => $amount,
        ]);
    }

    /**
     * With pending balance.
     */
    public function withPending(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'pending_balance' => $amount,
        ]);
    }

    /**
     * Rich merchant (high balance).
     */
    public function rich(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_balance' => $this->faker->randomFloat(2, 50000, 100000),
            'total_earned' => $this->faker->randomFloat(2, 100000, 500000),
        ]);
    }

    /**
     * New merchant (low activity).
     */
    public function newMerchant(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_balance' => $this->faker->randomFloat(2, 0, 500),
            'pending_balance' => $this->faker->randomFloat(2, 0, 200),
            'total_earned' => $this->faker->randomFloat(2, 0, 1000),
            'total_withdrawn' => 0,
        ]);
    }
}
