<?php

namespace App\Domain\Commerce\Events;

use App\Domain\Platform\Events\DomainEvent;

/**
 * Event fired when an order status changes
 */
class OrderStatusChangedEvent extends DomainEvent
{
    public function __construct(
        public readonly int $purchaseId,
        public readonly string $previousStatus,
        public readonly string $newStatus,
        public readonly ?int $changedBy = null,
        public readonly ?string $reason = null
    ) {
        parent::__construct();
    }

    public function aggregateType(): string
    {
        return 'Purchase';
    }

    public function aggregateId(): int|string
    {
        return $this->purchaseId;
    }

    public function payload(): array
    {
        return [
            'purchase_id' => $this->purchaseId,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'changed_by' => $this->changedBy,
            'reason' => $this->reason,
        ];
    }

    /**
     * Check if this is a completion transition
     */
    public function isCompleted(): bool
    {
        return in_array($this->newStatus, ['completed', 'delivered']);
    }

    /**
     * Check if this is a cancellation
     */
    public function isCancelled(): bool
    {
        return $this->newStatus === 'cancelled';
    }

    /**
     * Check if moving to processing
     */
    public function isProcessing(): bool
    {
        return $this->newStatus === 'processing';
    }
}
