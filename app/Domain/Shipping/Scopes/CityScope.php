<?php

namespace App\Domain\Shipping\Scopes;

use Illuminate\Database\Eloquent\Builder;

/**
 * City Scope
 *
 * Local scopes for city queries.
 */
trait CityScope
{
    /**
     * Scope to filter by country.
     */
    public function scopeInCountry(Builder $query, int $countryId): Builder
    {
        return $query->where('country_id', $countryId);
    }

    /**
     * Scope to get active cities.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to get inactive cities.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 0);
    }

    /**
     * Scope to search by name.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('name_ar', 'like', "%{$term}%");
        });
    }

    /**
     * Scope to get cities with shipping.
     */
    public function scopeWithShipping(Builder $query): Builder
    {
        return $query->whereHas('shippings');
    }

    /**
     * Scope to get cities with merchants.
     */
    public function scopeWithMerchants(Builder $query): Builder
    {
        return $query->whereHas('branches');
    }

    /**
     * Scope to order alphabetically.
     */
    public function scopeAlphabetical(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    /**
     * Scope to order by Arabic name.
     */
    public function scopeAlphabeticalArabic(Builder $query): Builder
    {
        return $query->orderBy('name_ar');
    }
}
