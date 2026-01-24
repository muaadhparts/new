<?php

namespace App\Domain\Accounting\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Domain\Accounting\Models\AccountingLedger;
use App\Domain\Identity\Models\User;

/**
 * Accounting Ledger Factory
 *
 * Factory for creating AccountingLedger instances in tests.
 */
class AccountingLedgerFactory extends Factory
{
    protected $model = AccountingLedger::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->merchant(),
            'type' => $this->faker->randomElement(['credit', 'debit']),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'balance_before' => $this->faker->randomFloat(2, 0, 5000),
            'balance_after' => $this->faker->randomFloat(2, 0, 5000),
            'description' => $this->faker->sentence(),
            'reference_type' => null,
            'reference_id' => null,
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
     * Credit transaction.
     */
    public function credit(): static
    {
        return $this->state(function (array $attributes) {
            $before = $attributes['balance_before'] ?? 1000;
            $amount = $attributes['amount'] ?? 100;
            return [
                'type' => 'credit',
                'balance_before' => $before,
                'balance_after' => $before + $amount,
            ];
        });
    }

    /**
     * Debit transaction.
     */
    public function debit(): static
    {
        return $this->state(function (array $attributes) {
            $before = $attributes['balance_before'] ?? 1000;
            $amount = $attributes['amount'] ?? 100;
            return [
                'type' => 'debit',
                'balance_before' => $before,
                'balance_after' => $before - $amount,
            ];
        });
    }

    /**
     * Order payment entry.
     */
    public function orderPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'credit',
            'description' => 'Payment received for order',
            'reference_type' => 'App\\Domain\\Commerce\\Models\\MerchantPurchase',
        ]);
    }

    /**
     * Commission deduction.
     */
    public function commission(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'debit',
            'description' => 'Platform commission',
            'reference_type' => 'App\\Domain\\Commerce\\Models\\MerchantPurchase',
        ]);
    }

    /**
     * Withdrawal entry.
     */
    public function withdrawal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'debit',
            'description' => 'Withdrawal processed',
            'reference_type' => 'App\\Domain\\Accounting\\Models\\Withdraw',
        ]);
    }

    /**
     * With specific reference.
     */
    public function withReference(string $type, int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'reference_type' => $type,
            'reference_id' => $id,
        ]);
    }
}
