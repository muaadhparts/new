<?php

namespace App\Domain\Shipping\ValueObjects;

use InvalidArgumentException;

/**
 * Address Value Object
 *
 * Immutable representation of a shipping/billing address.
 */
final class Address
{
    private string $street;
    private string $city;
    private ?string $state;
    private ?string $postalCode;
    private string $country;
    private ?int $countryId;
    private ?int $cityId;

    private function __construct(
        string $street,
        string $city,
        ?string $state,
        ?string $postalCode,
        string $country,
        ?int $countryId = null,
        ?int $cityId = null
    ) {
        if (empty(trim($street))) {
            throw new InvalidArgumentException('Street address is required');
        }

        if (empty(trim($city))) {
            throw new InvalidArgumentException('City is required');
        }

        if (empty(trim($country))) {
            throw new InvalidArgumentException('Country is required');
        }

        $this->street = trim($street);
        $this->city = trim($city);
        $this->state = $state ? trim($state) : null;
        $this->postalCode = $postalCode ? trim($postalCode) : null;
        $this->country = trim($country);
        $this->countryId = $countryId;
        $this->cityId = $cityId;
    }

    /**
     * Create from components
     */
    public static function create(
        string $street,
        string $city,
        string $country,
        ?string $state = null,
        ?string $postalCode = null,
        ?int $countryId = null,
        ?int $cityId = null
    ): self {
        return new self($street, $city, $state, $postalCode, $country, $countryId, $cityId);
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['street'] ?? $data['customer_address'] ?? '',
            $data['city'] ?? $data['customer_city'] ?? '',
            $data['state'] ?? null,
            $data['postal_code'] ?? $data['postalCode'] ?? null,
            $data['country'] ?? $data['customer_country'] ?? '',
            $data['country_id'] ?? $data['countryId'] ?? null,
            $data['city_id'] ?? $data['cityId'] ?? null
        );
    }

    // Getters
    public function street(): string
    {
        return $this->street;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function state(): ?string
    {
        return $this->state;
    }

    public function postalCode(): ?string
    {
        return $this->postalCode;
    }

    public function country(): string
    {
        return $this->country;
    }

    public function countryId(): ?int
    {
        return $this->countryId;
    }

    public function cityId(): ?int
    {
        return $this->cityId;
    }

    /**
     * Get full address as single line
     */
    public function fullAddress(): string
    {
        $parts = array_filter([
            $this->street,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get address for shipping label (multiline)
     */
    public function shippingLabel(): string
    {
        $lines = [$this->street];

        $cityLine = $this->city;
        if ($this->state) {
            $cityLine .= ', ' . $this->state;
        }
        if ($this->postalCode) {
            $cityLine .= ' ' . $this->postalCode;
        }
        $lines[] = $cityLine;
        $lines[] = $this->country;

        return implode("\n", $lines);
    }

    /**
     * Check if equals another address
     */
    public function equals(Address $other): bool
    {
        return $this->street === $other->street
            && $this->city === $other->city
            && $this->state === $other->state
            && $this->postalCode === $other->postalCode
            && $this->country === $other->country;
    }

    /**
     * Check if has IDs (for database relations)
     */
    public function hasIds(): bool
    {
        return $this->countryId !== null || $this->cityId !== null;
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return $this->fullAddress();
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'country_id' => $this->countryId,
            'city_id' => $this->cityId,
        ];
    }
}
