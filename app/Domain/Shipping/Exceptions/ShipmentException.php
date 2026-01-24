<?php

namespace App\Domain\Shipping\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Shipment Exception
 *
 * Thrown for shipment-related errors.
 */
class ShipmentException extends DomainException
{
    protected string $errorCode = 'SHIPMENT_ERROR';

    public function __construct(
        string $message = 'Shipment operation failed',
        int $code = 400,
        array $context = []
    ) {
        parent::__construct($message, $code, null, $context);
    }

    /**
     * Shipment not found
     */
    public static function notFound(int $shipmentId): self
    {
        return new self(
            "Shipment not found: {$shipmentId}",
            404,
            ['shipment_id' => $shipmentId, 'reason' => 'not_found']
        );
    }

    /**
     * Cannot cancel shipment
     */
    public static function cannotCancel(int $shipmentId, string $status): self
    {
        return new self(
            "Cannot cancel shipment in '{$status}' status",
            400,
            ['shipment_id' => $shipmentId, 'status' => $status, 'reason' => 'cannot_cancel']
        );
    }

    /**
     * Invalid status transition
     */
    public static function invalidTransition(string $from, string $to): self
    {
        return new self(
            "Cannot transition shipment from '{$from}' to '{$to}'",
            400,
            ['from_status' => $from, 'to_status' => $to, 'reason' => 'invalid_transition']
        );
    }

    /**
     * Carrier API error
     */
    public static function carrierError(string $carrier, string $error): self
    {
        return new self(
            "Carrier '{$carrier}' error: {$error}",
            502,
            ['carrier' => $carrier, 'carrier_error' => $error, 'reason' => 'carrier_error']
        );
    }

    /**
     * Tracking not available
     */
    public static function trackingUnavailable(string $trackingNumber): self
    {
        return new self(
            "Tracking information unavailable for: {$trackingNumber}",
            404,
            ['tracking_number' => $trackingNumber, 'reason' => 'tracking_unavailable']
        );
    }

    public function getDomain(): string
    {
        return 'Shipping';
    }
}
