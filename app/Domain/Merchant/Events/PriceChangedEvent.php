<?php

namespace App\Domain\Merchant\Events;

use App\Domain\Platform\Events\DomainEvent;

/**
 * Event fired when a merchant item price changes
 */
class PriceChangedEvent extends DomainEvent
{
    public function __construct(
        public readonly int $merchantItemId,
        public readonly int $merchantId,
        public readonly int $catalogItemId,
        public readonly float $previousPrice,
        public readonly float $newPrice,
        public readonly ?float $previousDiscount = null,
        public readonly ?float $newDiscount = null,
        public readonly ?int $changedBy = null
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
            'previous_price' => $this->previousPrice,
            'new_price' => $this->newPrice,
            'price_change_percent' => $this->priceChangePercent(),
            'previous_discount' => $this->previousDiscount,
            'new_discount' => $this->newDiscount,
            'changed_by' => $this->changedBy,
        ];
    }

    /**
     * Get price change percentage
     */
    public function priceChangePercent(): float
    {
        if ($this->previousPrice == 0) {
            return 0;
        }

        return round((($this->newPrice - $this->previousPrice) / $this->previousPrice) * 100, 2);
    }

    /**
     * Check if price was increased
     */
    public function wasIncreased(): bool
    {
        return $this->newPrice > $this->previousPrice;
    }

    /**
     * Check if price was decreased
     */
    public function wasDecreased(): bool
    {
        return $this->newPrice < $this->previousPrice;
    }

    /**
     * Check if discount was applied
     */
    public function discountWasApplied(): bool
    {
        return ($this->previousDiscount === null || $this->previousDiscount == 0)
            && $this->newDiscount > 0;
    }

    /**
     * Check if discount was removed
     */
    public function discountWasRemoved(): bool
    {
        return $this->previousDiscount > 0
            && ($this->newDiscount === null || $this->newDiscount == 0);
    }
}
