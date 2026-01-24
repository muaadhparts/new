<?php

namespace App\Domain\Accounting\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Balance Scope
 *
 * Local scopes for account balance queries.
 */
trait BalanceScope
{
    /**
     * Scope to filter by merchant.
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query->where('user_id', $merchantId);
    }

    /**
     * Scope to get accounts with positive balance.
     */
    public function scopeWithBalance(Builder $query): Builder
    {
        return $query->where('current_balance', '>', 0);
    }

    /**
     * Scope to get accounts with zero balance.
     */
    public function scopeZeroBalance(Builder $query): Builder
    {
        return $query->where('current_balance', 0);
    }

    /**
     * Scope to get accounts with pending balance.
     */
    public function scopeWithPending(Builder $query): Builder
    {
        return $query->where('pending_balance', '>', 0);
    }

    /**
     * Scope to get accounts with minimum balance.
     */
    public function scopeMinimumBalance(Builder $query, float $amount): Builder
    {
        return $query->where('current_balance', '>=', $amount);
    }

    /**
     * Scope to get high earners.
     */
    public function scopeHighEarners(Builder $query, float $threshold = 10000): Builder
    {
        return $query->where('total_earned', '>=', $threshold);
    }

    /**
     * Scope to get accounts eligible for withdrawal.
     */
    public function scopeEligibleForWithdrawal(Builder $query, float $minAmount = 100): Builder
    {
        return $query->where('current_balance', '>=', $minAmount);
    }

    /**
     * Scope to order by balance (highest first).
     */
    public function scopeOrderByBalance(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('current_balance', $direction);
    }

    /**
     * Scope to order by total earned.
     */
    public function scopeOrderByEarnings(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('total_earned', $direction);
    }
}
