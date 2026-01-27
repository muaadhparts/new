<?php

namespace App\Domain\Commerce\DTOs;

use App\Domain\Commerce\Models\Purchase;

/**
 * PurchaseListItemDTO - Pre-computed data for purchase list display
 *
 * DATA FLOW POLICY: Views must only read properties, no logic, no queries
 */
final class PurchaseListItemDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $orderNumber,
        public readonly string $formattedDate,
        public readonly string $formattedTotal,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly string $statusClass,
        public readonly int $itemsCount,
        public readonly string $detailsUrl,
        public readonly bool $canCancel,
        public readonly ?string $trackingNumber,
    ) {}

    /**
     * Build DTO from Purchase model
     */
    public static function fromModel(Purchase $purchase): self
    {
        $status = $purchase->status ?? 'pending';

        return new self(
            id: $purchase->id,
            orderNumber: $purchase->purchase_number ?? $purchase->order_number ?? "#{$purchase->id}",
            formattedDate: $purchase->created_at?->format('Y-m-d H:i') ?? '',
            formattedTotal: monetaryUnit()->format($purchase->total ?? 0),
            status: $status,
            statusLabel: self::getStatusLabel($status),
            statusClass: self::getStatusClass($status),
            itemsCount: is_array($purchase->cart) ? count($purchase->cart) : 0,
            detailsUrl: route('user.purchase.show', $purchase->id),
            canCancel: in_array($status, ['pending', 'processing']),
            trackingNumber: $purchase->tracking_number,
        );
    }

    /**
     * Get localized status label
     * Delegates to PurchaseDisplayService for consistency
     */
    private static function getStatusLabel(string $status): string
    {
        return purchaseDisplay()->getStatusLabel($status);
    }

    /**
     * Get CSS class for status badge
     * Delegates to PurchaseDisplayService for consistency
     */
    private static function getStatusClass(string $status): string
    {
        return purchaseDisplay()->getStatusClass($status);
    }

    /**
     * Build collection of DTOs from Purchase collection
     */
    public static function fromCollection($purchases): array
    {
        return $purchases->map(fn($purchase) => self::fromModel($purchase))->toArray();
    }
}
