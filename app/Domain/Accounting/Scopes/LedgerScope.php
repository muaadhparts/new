<?php

namespace App\Domain\Accounting\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Ledger Scope
 *
 * Local scopes for accounting ledger queries.
 */
trait LedgerScope
{
    /**
     * Scope to filter by merchant.
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query->where('user_id', $merchantId);
    }

    /**
     * Scope to get credit transactions.
     */
    public function scopeCredits(Builder $query): Builder
    {
        return $query->where('type', 'credit');
    }

    /**
     * Scope to get debit transactions.
     */
    public function scopeDebits(Builder $query): Builder
    {
        return $query->where('type', 'debit');
    }

    /**
     * Scope to filter by reference type.
     */
    public function scopeByReference(Builder $query, string $type, ?int $id = null): Builder
    {
        $query->where('reference_type', $type);

        if ($id !== null) {
            $query->where('reference_id', $id);
        }

        return $query;
    }

    /**
     * Scope to get order-related transactions.
     */
    public function scopeOrders(Builder $query): Builder
    {
        return $query->where('reference_type', 'like', '%Purchase%');
    }

    /**
     * Scope to get commission transactions.
     */
    public function scopeCommissions(Builder $query): Builder
    {
        return $query->where('description', 'like', '%commission%');
    }

    /**
     * Scope to get withdrawal transactions.
     */
    public function scopeWithdrawals(Builder $query): Builder
    {
        return $query->where('reference_type', 'like', '%Withdraw%');
    }

    /**
     * Scope to filter by minimum amount.
     */
    public function scopeMinAmount(Builder $query, float $amount): Builder
    {
        return $query->where('amount', '>=', $amount);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateBetween(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get today's transactions.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to get this month's transactions.
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
     * Scope to order by oldest.
     */
    public function scopeOldest(Builder $query): Builder
    {
        return $query->orderBy('created_at');
    }

    /**
     * Scope to calculate total credits.
     */
    public function scopeTotalCredits(Builder $query): Builder
    {
        return $query->credits()->selectRaw('SUM(amount) as total');
    }

    /**
     * Scope to calculate total debits.
     */
    public function scopeTotalDebits(Builder $query): Builder
    {
        return $query->debits()->selectRaw('SUM(amount) as total');
    }
}
