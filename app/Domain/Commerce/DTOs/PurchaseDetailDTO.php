<?php

namespace App\Domain\Commerce\DTOs;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Models\MerchantPurchase;

/**
 * PurchaseDetailDTO - Pre-computed data for purchase detail page
 *
 * DATA FLOW POLICY: Views must only read properties, no logic, no queries
 */
final class PurchaseDetailDTO
{
    public function __construct(
        // Basic info
        public readonly int $id,
        public readonly string $orderNumber,
        public readonly string $formattedDate,
        public readonly string $status,
        public readonly string $statusLabel,
        public readonly string $statusClass,

        // Customer info
        public readonly string $customerName,
        public readonly string $customerEmail,
        public readonly string $customerPhone,

        // Shipping info
        public readonly string $shippingAddress,
        public readonly string $shippingCity,
        public readonly string $shippingCountry,
        public readonly ?string $shippingMethod,

        // Payment info
        public readonly string $paymentMethod,
        public readonly string $paymentStatus,

        // Items
        public readonly array $items,
        public readonly int $totalItems,

        // Totals
        public readonly string $formattedSubtotal,
        public readonly string $formattedShipping,
        public readonly string $formattedTax,
        public readonly string $formattedDiscount,
        public readonly string $formattedTotal,

        // Timeline
        public readonly array $timeline,

        // Actions
        public readonly bool $canCancel,
        public readonly bool $canTrack,
        public readonly ?string $trackingUrl,

        // Merchant purchases
        public readonly array $merchantPurchases,
    ) {}

    /**
     * Build DTO from Purchase model
     */
    public static function fromModel(Purchase $purchase): self
    {
        $status = $purchase->status ?? 'pending';
        $cart = is_array($purchase->cart) ? $purchase->cart : [];

        return new self(
            // Basic info
            id: $purchase->id,
            orderNumber: $purchase->purchase_number ?? "#{$purchase->id}",
            formattedDate: $purchase->created_at?->format('Y-m-d H:i') ?? '',
            status: $status,
            statusLabel: self::getStatusLabel($status),
            statusClass: self::getStatusClass($status),

            // Customer info
            customerName: $purchase->customer_name ?? '',
            customerEmail: $purchase->customer_email ?? '',
            customerPhone: $purchase->customer_phone ?? '',

            // Shipping info
            shippingAddress: $purchase->customer_address ?? '',
            shippingCity: $purchase->customer_city ?? '',
            shippingCountry: $purchase->customer_country ?? '',
            shippingMethod: $purchase->shipping_method ?? null,

            // Payment info
            paymentMethod: self::formatPaymentMethod($purchase->method ?? ''),
            paymentStatus: $purchase->payment_status ?? 'pending',

            // Items
            items: self::buildCartItems($cart),
            totalItems: count($cart),

            // Totals
            formattedSubtotal: monetaryUnit()->format($purchase->subtotal ?? 0),
            formattedShipping: monetaryUnit()->format($purchase->shipping ?? 0),
            formattedTax: monetaryUnit()->format($purchase->tax ?? 0),
            formattedDiscount: monetaryUnit()->format($purchase->discount ?? 0),
            formattedTotal: monetaryUnit()->format($purchase->total ?? 0),

            // Timeline
            timeline: self::buildTimeline($purchase),

            // Actions
            canCancel: in_array($status, ['pending', 'processing']),
            canTrack: !empty($purchase->tracking_number),
            trackingUrl: $purchase->tracking_number
                ? route('front.tracking', ['number' => $purchase->tracking_number])
                : null,

            // Merchant purchases
            merchantPurchases: self::buildMerchantPurchases($purchase),
        );
    }

    /**
     * Build cart items for display
     */
    private static function buildCartItems(array $cart): array
    {
        return collect($cart)->map(function ($item, $key) {
            return [
                'key' => $key,
                'name' => getLocalizedCatalogItemName($item),
                'partNumber' => $item['part_number'] ?? null,
                'quantity' => (int) ($item['qty'] ?? 1),
                'price' => monetaryUnit()->format($item['price'] ?? 0),
                'total' => monetaryUnit()->format($item['total_price'] ?? ($item['price'] ?? 0) * ($item['qty'] ?? 1)),
                'photo' => $item['photo'] ?? asset('assets/images/noimage.png'),
                'merchantName' => getMerchantName($item),
            ];
        })->values()->toArray();
    }

    /**
     * Build timeline for order tracking
     */
    private static function buildTimeline(Purchase $purchase): array
    {
        $timeline = [];

        // Order placed
        $timeline[] = [
            'status' => 'placed',
            'label' => __('Order Placed'),
            'date' => $purchase->created_at?->format('Y-m-d H:i'),
            'completed' => true,
        ];

        // Processing
        $timeline[] = [
            'status' => 'processing',
            'label' => __('Processing'),
            'date' => $purchase->processing_at?->format('Y-m-d H:i'),
            'completed' => in_array($purchase->status, ['processing', 'shipped', 'delivered', 'completed']),
        ];

        // Shipped
        $timeline[] = [
            'status' => 'shipped',
            'label' => __('Shipped'),
            'date' => $purchase->shipped_at?->format('Y-m-d H:i'),
            'completed' => in_array($purchase->status, ['shipped', 'delivered', 'completed']),
        ];

        // Delivered
        $timeline[] = [
            'status' => 'delivered',
            'label' => __('Delivered'),
            'date' => $purchase->delivered_at?->format('Y-m-d H:i'),
            'completed' => in_array($purchase->status, ['delivered', 'completed']),
        ];

        return $timeline;
    }

    /**
     * Build merchant purchases data
     */
    private static function buildMerchantPurchases(Purchase $purchase): array
    {
        if (!$purchase->relationLoaded('merchantPurchases')) {
            return [];
        }

        return $purchase->merchantPurchases->map(function (MerchantPurchase $mp) {
            return [
                'id' => $mp->id,
                'merchantName' => $mp->user ? getLocalizedShopName($mp->user) : __('Unknown'),
                'status' => $mp->status,
                'statusLabel' => self::getStatusLabel($mp->status),
                'formattedTotal' => monetaryUnit()->format($mp->price ?? 0),
                'itemsCount' => is_array($mp->cart) ? count($mp->cart) : 0,
            ];
        })->toArray();
    }

    /**
     * Format payment method for display
     * Delegates to PurchaseDisplayService for consistency
     */
    private static function formatPaymentMethod(string $method): string
    {
        return purchaseDisplay()->formatPaymentMethod($method);
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
}
