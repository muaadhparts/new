<?php

namespace App\Domain\Commerce\Exceptions;

use App\Domain\Platform\Exceptions\DomainException;

/**
 * Insufficient Stock Exception
 *
 * Thrown when requested quantity exceeds available stock.
 */
class InsufficientStockException extends DomainException
{
    protected string $errorCode = 'INSUFFICIENT_STOCK';

    public function __construct(
        public readonly int $merchantItemId,
        public readonly int $requestedQuantity,
        public readonly int $availableStock,
        ?string $itemName = null
    ) {
        $message = $itemName
            ? "Insufficient stock for '{$itemName}'. Requested: {$requestedQuantity}, Available: {$availableStock}"
            : "Insufficient stock. Requested: {$requestedQuantity}, Available: {$availableStock}";

        parent::__construct($message, 400, null, [
            'merchant_item_id' => $merchantItemId,
            'requested' => $requestedQuantity,
            'available' => $availableStock,
            'item_name' => $itemName,
        ]);
    }

    /**
     * Create from merchant item check
     */
    public static function forItem(int $itemId, int $requested, int $available, ?string $name = null): self
    {
        return new self($itemId, $requested, $available, $name);
    }

    public function getDomain(): string
    {
        return 'Commerce';
    }

    /**
     * Get shortage amount
     */
    public function getShortage(): int
    {
        return $this->requestedQuantity - $this->availableStock;
    }

    public function getUserMessage(): string
    {
        return __('messages.insufficient_stock', [
            'available' => $this->availableStock,
        ]);
    }

    public function shouldReport(): bool
    {
        return false; // Expected business case
    }
}
