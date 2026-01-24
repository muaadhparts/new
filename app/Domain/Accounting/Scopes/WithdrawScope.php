<?php

namespace App\Domain\Accounting\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Withdraw Scope
 *
 * Local scopes for withdrawal queries.
 */
trait WithdrawScope
{
    /**
     * Scope to filter by merchant.
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query->where('user_id', $merchantId);
    }

    /**
     * Scope to get pending withdrawals.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get processing withdrawals.
     */
    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope to get completed withdrawals.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get rejected withdrawals.
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope to get actionable withdrawals.
     */
    public function scopeActionable(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'processing']);
    }

    /**
     * Scope to filter by method.
     */
    public function scopeByMethod(Builder $query, string $method): Builder
    {
        return $query->where('method', $method);
    }

    /**
     * Scope to filter by bank.
     */
    public function scopeByBank(Builder $query, string $bankName): Builder
    {
        return $query->where('bank_name', $bankName);
    }

    /**
     * Scope to filter by minimum amount.
     */
    public function scopeMinAmount(Builder $query, float $amount): Builder
    {
        return $query->where('amount', '>=', $amount);
    }

    /**
     * Scope to filter by maximum amount.
     */
    public function scopeMaxAmount(Builder $query, float $amount): Builder
    {
        return $query->where('amount', '<=', $amount);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get today's withdrawals.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to get this month's withdrawals.
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * Scope to order by newest.
     */
    public function scopeNewest(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Scope to order by amount.
     */
    public function scopeOrderByAmount(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('amount', $direction);
    }
}
