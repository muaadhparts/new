<?php

namespace App\Domain\Shipping\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Shipping Unavailable Exception
 *
 * Thrown when shipping is not available for a location.
 */
class ShippingUnavailableException extends DomainException
{
    protected string $errorCode = 'SHIPPING_UNAVAILABLE';

    public function __construct(
        string $reason = 'Shipping is not available',
        ?string $location = null,
        array $context = []
    ) {
        if ($location) {
            $context['location'] = $location;
        }

        parent::__construct($reason, 400, null, $context);
    }

    /**
     * Create for location not serviced
     */
    public static function forLocation(string $city, ?string $country = null): self
    {
        $location = $country ? "{$city}, {$country}" : $city;
        return new self(
            "Shipping is not available to {$location}",
            $location,
            ['city' => $city, 'country' => $country, 'reason' => 'not_serviced']
        );
    }

    /**
     * Create for no carriers available
     */
    public static function noCarriers(): self
    {
        return new self(
            'No shipping carriers available',
            null,
            ['reason' => 'no_carriers']
        );
    }

    /**
     * Create for merchant doesn't ship to location
     */
    public static function merchantDoesNotShip(int $merchantId, string $location): self
    {
        return new self(
            "Merchant does not ship to {$location}",
            $location,
            ['merchant_id' => $merchantId, 'reason' => 'merchant_restriction']
        );
    }

    /**
     * Create for oversized items
     */
    public static function oversized(): self
    {
        return new self(
            'Items are too large for standard shipping',
            null,
            ['reason' => 'oversized']
        );
    }

    public function getDomain(): string
    {
        return 'Shipping';
    }

    public function getUserMessage(): string
    {
        return __('messages.shipping_unavailable');
    }

    public function shouldReport(): bool
    {
        return false;
    }
}
