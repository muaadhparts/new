<?php

namespace App\Domain\Shipping\Events;

use App\Domain\Platform\Events\DomainEvent;

/**
 * Event fired when a delivery is completed
 */
class DeliveryCompletedEvent extends DomainEvent
{
    public function __construct(
        public readonly int $shipmentId,
        public readonly int $purchaseId,
        public readonly int $customerId,
        public readonly ?int $courierId = null,
        public readonly ?string $receivedBy = null,
        public readonly ?string $signature = null
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
            'customer_id' => $this->customerId,
            'courier_id' => $this->courierId,
            'received_by' => $this->receivedBy,
            'has_signature' => $this->signature !== null,
        ];
    }

    /**
     * Check if delivery was signed for
     */
    public function wasSigned(): bool
    {
        return $this->signature !== null;
    }

    /**
     * Check if received by someone other than customer
     */
    public function wasReceivedByProxy(): bool
    {
        return $this->receivedBy !== null;
    }
}
