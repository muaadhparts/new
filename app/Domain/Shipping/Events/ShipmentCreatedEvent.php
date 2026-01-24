<?php

namespace App\Domain\Shipping\Events;

use App\Domain\Platform\Events\DomainEvent;

/**
 * Event fired when a shipment is created
 */
class ShipmentCreatedEvent extends DomainEvent
{
    public function __construct(
        public readonly int $shipmentId,
        public readonly int $purchaseId,
        public readonly int $merchantId,
        public readonly string $trackingNumber,
        public readonly string $carrier,
        public readonly ?int $courierId = null
    ) {
        parent::__construct();
    }

    public function aggregateType(): string
    {
        return 'Shipment';
    }

    public function aggregateId(): int|string
    {
        return $this->shipmentId;
    }

    public function payload(): array
    {
        return [
            'shipment_id' => $this->shipmentId,
            'purchase_id' => $this->purchaseId,
            'merchant_id' => $this->merchantId,
            'tracking_number' => $this->trackingNumber,
            'carrier' => $this->carrier,
            'courier_id' => $this->courierId,
            'has_courier' => $this->courierId !== null,
        ];
    }

    /**
     * Check if shipment has assigned courier
     */
    public function hasCourier(): bool
    {
        return $this->courierId !== null;
    }
}
