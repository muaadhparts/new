<?php

namespace App\Domain\Commerce\DTOs;

/**
 * CheckoutAddressDTO - Customer address for checkout
 *
 * Represents validated customer address data.
 */
class CheckoutAddressDTO
{
    public string $customerName;
    public string $customerEmail;
    public string $customerPhone;
    public string $customerAddress;
    public ?int $countryId;
    public string $customerCountry;
    public ?int $cityId;
    public string $customerCity;
    public ?string $postalCode;
    public ?float $lat;
    public ?float $lng;
    public ?string $orderNotes;

    /**
     * Create DTO from request/session data
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();

        $dto->customerName = $data['customer_name'] ?? '';
        $dto->customerEmail = $data['customer_email'] ?? '';
        $dto->customerPhone = $data['customer_phone'] ?? '';
        $dto->customerAddress = $data['customer_address'] ?? '';
        $dto->countryId = isset($data['country_id']) ? (int) $data['country_id'] : null;
        $dto->customerCountry = $data['customer_country'] ?? '';
        $dto->cityId = isset($data['city_id']) ? (int) $data['city_id'] : null;
        $dto->customerCity = $data['customer_city'] ?? '';
        $dto->postalCode = $data['postal_code'] ?? null;
        $dto->lat = isset($data['lat']) ? (float) $data['lat'] : null;
        $dto->lng = isset($data['lng']) ? (float) $data['lng'] : null;
        $dto->orderNotes = $data['order_notes'] ?? null;

        return $dto;
    }

    /**
     * Convert to array for storage
     */
    public function toArray(): array
    {
        return [
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'customer_address' => $this->customerAddress,
            'country_id' => $this->countryId,
            'customer_country' => $this->customerCountry,
            'city_id' => $this->cityId,
            'customer_city' => $this->customerCity,
            'postal_code' => $this->postalCode,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'order_notes' => $this->orderNotes,
        ];
    }

    /**
     * Check if address has coordinates
     */
    public function hasCoordinates(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }

    /**
     * Get full address string
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->customerAddress,
            $this->customerCity,
            $this->customerCountry,
            $this->postalCode,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Validate required fields
     */
    public function isValid(): bool
    {
        return !empty($this->customerName)
            && !empty($this->customerPhone)
            && !empty($this->customerAddress);
    }
}
