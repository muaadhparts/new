<?php

namespace App\Domain\Merchant\Events;

use App\Domain\Platform\Events\DomainEvent;

/**
 * Event fired when merchant status changes
 */
class MerchantStatusChangedEvent extends DomainEvent
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_PENDING = 'pending';

    public function __construct(
        public readonly int $merchantId,
        public readonly string $previousStatus,
        public readonly string $newStatus,
        public readonly ?string $reason = null,
        public readonly ?int $changedBy = null
    ) {
        parent::__construct();
    }

    public function aggregateType(): string
    {
        return 'User'; // Merchant is a User with merchant role
    }

    public function aggregateId(): int|string
    {
        return $this->merchantId;
    }

    public function payload(): array
    {
        return [
            'merchant_id' => $this->merchantId,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'reason' => $this->reason,
            'changed_by' => $this->changedBy,
        ];
    }

    /**
     * Check if merchant was activated
     */
    public function wasActivated(): bool
    {
        return $this->newStatus === self::STATUS_ACTIVE
            && $this->previousStatus !== self::STATUS_ACTIVE;
    }

    /**
     * Check if merchant was deactivated
     */
    public function wasDeactivated(): bool
    {
        return $this->newStatus === self::STATUS_INACTIVE
            && $this->previousStatus === self::STATUS_ACTIVE;
    }

    /**
     * Check if merchant was suspended
     */
    public function wasSuspended(): bool
    {
        return $this->newStatus === self::STATUS_SUSPENDED;
    }
}
