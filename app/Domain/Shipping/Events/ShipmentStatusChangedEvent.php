<?php

namespace App\Domain\Shipping\Events;

use App\Domain\Platform\Events\DomainEvent;

/**
 * Event fired when shipment status changes
 */
class ShipmentStatusChangedEvent extends DomainEvent
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETURNED = 'returned';

    public function __construct(
        public readonly int $shipmentId,
        public readonly int $purchaseId,
        public readonly string $previousStatus,
        public readonly string $newStatus,
        public readonly ?string $location = null,
        public readonly ?string $notes = null
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
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'location' => $this->location,
            'notes' => $this->notes,
        ];
    }

    /**
     * Check if shipment is now delivered
     */
    public function isDelivered(): bool
    {
        return $this->newStatus === self::STATUS_DELIVERED;
    }

    /**
     * Check if shipment failed
     */
    public function isFailed(): bool
    {
        return $this->newStatus === self::STATUS_FAILED;
    }

    /**
     * Check if shipment is in transit
     */
    public function isInTransit(): bool
    {
        return in_array($this->newStatus, [
            self::STATUS_PICKED_UP,
            self::STATUS_IN_TRANSIT,
            self::STATUS_OUT_FOR_DELIVERY,
        ]);
    }

    /**
     * Check if shipment was returned
     */
    public function wasReturned(): bool
    {
        return $this->newStatus === self::STATUS_RETURNED;
    }
}
