<?php

namespace App\Domain\Merchant\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * Branch Scope
 *
 * Local scopes for merchant branch queries.
 */
trait BranchScope
{
    /**
     * Scope to filter by merchant.
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query->where('user_id', $merchantId);
    }

    /**
     * Scope to get main branches only.
     */
    public function scopeMain(Builder $query): Builder
    {
        return $query->where('is_main', true);
    }

    /**
     * Scope to get secondary branches.
     */
    public function scopeSecondary(Builder $query): Builder
    {
        return $query->where('is_main', false);
    }

    /**
     * Scope to filter by city.
     */
    public function scopeInCity(Builder $query, int $cityId): Builder
    {
        return $query->where('city_id', $cityId);
    }

    /**
     * Scope to filter by cities.
     */
    public function scopeInCities(Builder $query, array $cityIds): Builder
    {
        return $query->whereIn('city_id', $cityIds);
    }

    /**
     * Scope to get active branches.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to get branches with inventory.
     */
    public function scopeWithInventory(Builder $query): Builder
    {
        return $query->whereHas('merchantItems', function ($q) {
            $q->where('stock', '>', 0);
        });
    }

    /**
     * Scope to get branches near coordinates.
     */
    public function scopeNearby(Builder $query, float $lat, float $lng, int $radiusKm = 50): Builder
    {
        return $query->selectRaw("*,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance",
            [$lat, $lng, $lat]
        )
        ->having('distance', '<', $radiusKm)
        ->orderBy('distance');
    }

    /**
     * Scope to order by distance from coordinates.
     */
    public function scopeOrderByDistance(Builder $query, float $lat, float $lng): Builder
    {
        return $query->selectRaw("*,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance",
            [$lat, $lng, $lat]
        )
        ->orderBy('distance');
    }
}
