<?php

namespace App\Domain\Merchant\Events;

use App\Domain\Platform\Events\DomainEvent;

/**
 * Event fired when stock is updated
 */
class StockUpdatedEvent extends DomainEvent
{
    public function __construct(
        public readonly int $merchantItemId,
        public readonly int $merchantId,
        public readonly int $catalogItemId,
        public readonly int $previousStock,
        public readonly int $newStock,
        public readonly string $reason,
        public readonly ?int $updatedBy = null
    ) {
        parent::__construct();
    }

    public function aggregateType(): string
    {
        return 'MerchantItem';
    }

    public function aggregateId(): int|string
    {
        return $this->merchantItemId;
    }

    public function payload(): array
    {
        return [
            'merchant_item_id' => $this->merchantItemId,
            'merchant_id' => $this->merchantId,
            'catalog_item_id' => $this->catalogItemId,
            'previous_stock' => $this->previousStock,
            'new_stock' => $this->newStock,
            'change' => $this->stockChange(),
            'reason' => $this->reason,
            'updated_by' => $this->updatedBy,
        ];
    }

    /**
     * Get the stock change amount
     */
    public function stockChange(): int
    {
        return $this->newStock - $this->previousStock;
    }

    /**
     * Check if stock was increased
     */
    public function wasIncreased(): bool
    {
        return $this->newStock > $this->previousStock;
    }

    /**
     * Check if stock was decreased
     */
    public function wasDecreased(): bool
    {
        return $this->newStock < $this->previousStock;
    }

    /**
     * Check if item is now out of stock
     */
    public function isNowOutOfStock(): bool
    {
        return $this->previousStock > 0 && $this->newStock <= 0;
    }

    /**
     * Check if item is now low stock
     */
    public function isLowStock(int $threshold = 5): bool
    {
        return $this->newStock > 0 && $this->newStock <= $threshold;
    }
}
