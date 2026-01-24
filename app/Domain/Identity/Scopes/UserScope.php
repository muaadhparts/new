<?php

namespace App\Domain\Identity\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * User Scope
 *
 * Local scopes for user queries.
 */
trait UserScope
{
    /**
     * Scope to get merchants only.
     */
    public function scopeMerchants(Builder $query): Builder
    {
        return $query->where('is_merchant', 1);
    }

    /**
     * Scope to get customers only.
     */
    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('is_merchant', 0);
    }

    /**
     * Scope to get active users.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to get inactive users.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 0);
    }

    /**
     * Scope to get banned users.
     */
    public function scopeBanned(Builder $query): Builder
    {
        return $query->where('status', 2);
    }

    /**
     * Scope to get verified users.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope to get unverified users.
     */
    public function scopeUnverified(Builder $query): Builder
    {
        return $query->whereNull('email_verified_at');
    }

    /**
     * Scope to search by name, email, or phone.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    /**
     * Scope to filter by email.
     */
    public function scopeByEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    /**
     * Scope to filter by phone.
     */
    public function scopeByPhone(Builder $query, string $phone): Builder
    {
        return $query->where('phone', $phone);
    }

    /**
     * Scope to get users with orders.
     */
    public function scopeWithOrders(Builder $query): Builder
    {
        return $query->whereHas('purchases');
    }

    /**
     * Scope to get users without orders.
     */
    public function scopeWithoutOrders(Builder $query): Builder
    {
        return $query->whereDoesntHave('purchases');
    }

    /**
     * Scope to get recently registered users.
     */
    public function scopeRecentlyRegistered(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to get users registered today.
     */
    public function scopeRegisteredToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope to order by newest.
     */
    public function scopeNewest(Builder $query): Builder
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Scope to order alphabetically.
     */
    public function scopeAlphabetical(Builder $query): Builder
    {
        return $query->orderBy('name');
    }
}
