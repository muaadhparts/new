<?php

namespace App\Domain\Shipping\Traits;

use App\Domain\Shipping\ValueObjects\Address;

/**
 * Has Address Trait
 *
 * Provides address functionality for models.
 */
trait HasAddress
{
    /**
     * Get address columns
     */
    public function getAddressColumns(): array
    {
        return $this->addressColumns ?? [
            'street' => 'customer_address',
            'city' => 'customer_city',
            'country' => 'customer_country',
            'postal_code' => 'customer_zip',
        ];
    }

    /**
     * Get address as value object
     */
    public function getAddress(): ?Address
    {
        $columns = $this->getAddressColumns();

        $street = $this->{$columns['street']} ?? null;
        $city = $this->{$columns['city']} ?? null;
        $country = $this->{$columns['country']} ?? 'Saudi Arabia';

        if (!$street || !$city) {
            return null;
        }

        try {
            return Address::create(
                street: $street,
                city: $city,
                country: $country,
                postalCode: $this->{$columns['postal_code']} ?? null
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get full address string
     */
    public function getFullAddress(): string
    {
        return $this->getAddress()?->fullAddress() ?? '';
    }

    /**
     * Get city name
     */
    public function getname(): ?string
    {
        $columns = $this->getAddressColumns();
        return $this->{$columns['city']} ?? null;
    }

    /**
     * Get country name
     */
    public function getCountryName(): string
    {
        $columns = $this->getAddressColumns();
        return $this->{$columns['country']} ?? 'Saudi Arabia';
    }

    /**
     * Check if has complete address
     */
    public function hasCompleteAddress(): bool
    {
        return $this->getAddress() !== null;
    }

    /**
     * Set address from value object
     */
    public function setAddress(Address $address): bool
    {
        $columns = $this->getAddressColumns();

        return $this->update([
            $columns['street'] => $address->street(),
            $columns['city'] => $address->city(),
            $columns['country'] => $address->country(),
            $columns['postal_code'] => $address->postalCode(),
        ]);
    }

    /**
     * Scope by city
     */
    public function scopeInCity($query, string $city)
    {
        $columns = $this->getAddressColumns();
        return $query->where($columns['city'], $city);
    }

    /**
     * Scope by country
     */
    public function scopeInCountry($query, string $country)
    {
        $columns = $this->getAddressColumns();
        return $query->where($columns['country'], $country);
    }

    /**
     * Scope with address
     */
    public function scopeWithAddress($query)
    {
        $columns = $this->getAddressColumns();
        return $query->whereNotNull($columns['street'])
            ->whereNotNull($columns['city'])
            ->where($columns['street'], '!=', '')
            ->where($columns['city'], '!=', '');
    }
}
