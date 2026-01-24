<?php

namespace App\Domain\Commerce\Events;

use App\Domain\Platform\Events\DomainEvent;

/**
 * Event fired when payment is received for an order
 */
class PaymentReceivedEvent extends DomainEvent
{
    public function __construct(
        public readonly int $purchaseId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $paymentMethod,
        public readonly ?string $transactionId = null
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
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->paymentMethod,
            'transaction_id' => $this->transactionId,
        ];
    }

    /**
     * Check if full payment
     */
    public function isFullPayment(float $orderTotal): bool
    {
        return abs($this->amount - $orderTotal) < 0.01;
    }
}
