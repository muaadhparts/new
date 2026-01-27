<?php

namespace App\Domain\Commerce\Events;

use App\Domain\Platform\Events\DomainEvent;
use App\Domain\Commerce\Models\Purchase;

/**
 * Event fired when a purchase is placed
 */
class PurchasePlacedEvent extends DomainEvent
{
    public function __construct(
        public readonly int $purchaseId,
        public readonly int $customerId,
        public readonly float $totalAmount,
        public readonly string $currency,
        public readonly int $itemCount,
        public readonly array $merchantIds = []
    ) {
        parent::__construct();
    }

    /**
     * Create from Purchase model
     */
    public static function fromPurchase(Purchase $purchase): self
    {
        $cartItems = $purchase->getCartItems();
        $merchantIds = collect($cartItems)->pluck('merchant_id')->unique()->values()->toArray();

        return new self(
            purchaseId: $purchase->id,
            customerId: $purchase->user_id,
            totalAmount: (float) $purchase->total,
            currency: $purchase->currency ?? 'SAR',
            itemCount: count($cartItems),
            merchantIds: $merchantIds
        );
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
            'customer_id' => $this->customerId,
            'total_amount' => $this->totalAmount,
            'currency' => $this->currency,
            'item_count' => $this->itemCount,
            'merchant_ids' => $this->merchantIds,
        ];
    }
}
