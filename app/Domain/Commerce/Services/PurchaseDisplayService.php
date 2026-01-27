<?php

namespace App\Domain\Commerce\Services;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Commerce\Models\MerchantPurchase;
use Illuminate\Support\Collection;

/**
 * PurchaseDisplayService - Centralized formatting for purchase display
 *
 * API-Ready: All formatting in one place for Web and API consumption.
 * DATA FLOW POLICY: Controller → Service → DTO → View/API
 *
 * @see docs/rules/DATA_FLOW_POLICY.md
 */
class PurchaseDisplayService
{
    /**
     * Standard date format for lists
     */
    public const DATE_FORMAT_LIST = 'd M Y';

    /**
     * Standard date format for details
     */
    public const DATE_FORMAT_DETAIL = 'd-M-Y H:i';

    /**
     * Full date format with seconds
     */
    public const DATE_FORMAT_FULL = 'd-M-Y H:i:s a';

    /**
     * Purchase statuses with their display properties
     */
    private const STATUS_CONFIG = [
        'pending' => [
            'label_key' => 'Pending',
            'class' => 'bg-warning text-dark',
            'color' => 'warning',
            'icon' => 'fa-clock',
        ],
        'processing' => [
            'label_key' => 'Processing',
            'class' => 'bg-info text-white',
            'color' => 'info',
            'icon' => 'fa-cogs',
        ],
        'on_delivery' => [
            'label_key' => 'On Delivery',
            'class' => 'bg-primary text-white',
            'color' => 'primary',
            'icon' => 'fa-truck',
        ],
        'shipped' => [
            'label_key' => 'Shipped',
            'class' => 'bg-primary text-white',
            'color' => 'primary',
            'icon' => 'fa-shipping-fast',
        ],
        'delivered' => [
            'label_key' => 'Delivered',
            'class' => 'bg-success text-white',
            'color' => 'success',
            'icon' => 'fa-check-circle',
        ],
        'completed' => [
            'label_key' => 'Completed',
            'class' => 'bg-success text-white',
            'color' => 'success',
            'icon' => 'fa-check-double',
        ],
        'cancelled' => [
            'label_key' => 'Cancelled',
            'class' => 'bg-danger text-white',
            'color' => 'danger',
            'icon' => 'fa-times-circle',
        ],
        'declined' => [
            'label_key' => 'Declined',
            'class' => 'bg-danger text-white',
            'color' => 'danger',
            'icon' => 'fa-ban',
        ],
        'refunded' => [
            'label_key' => 'Refunded',
            'class' => 'bg-secondary text-white',
            'color' => 'secondary',
            'icon' => 'fa-undo',
        ],
        'requires_attention' => [
            'label_key' => 'Requires Attention',
            'class' => 'bg-danger text-white',
            'color' => 'danger',
            'icon' => 'fa-exclamation-triangle',
        ],
    ];

    /**
     * Payment statuses with their display properties
     */
    private const PAYMENT_STATUS_CONFIG = [
        'pending' => [
            'label_key' => 'Pending',
            'class' => 'bg-warning text-dark',
            'color' => 'warning',
        ],
        'paid' => [
            'label_key' => 'Paid',
            'class' => 'bg-success text-white',
            'color' => 'success',
        ],
        'partial' => [
            'label_key' => 'Partial',
            'class' => 'bg-info text-white',
            'color' => 'info',
        ],
        'failed' => [
            'label_key' => 'Failed',
            'class' => 'bg-danger text-white',
            'color' => 'danger',
        ],
        'refunded' => [
            'label_key' => 'Refunded',
            'class' => 'bg-secondary text-white',
            'color' => 'secondary',
        ],
    ];

    /**
     * Payment method display names
     */
    private const PAYMENT_METHODS = [
        'cod' => 'Cash on Delivery',
        'cash' => 'Cash on Delivery',
        'cash on delivery' => 'Cash on Delivery',
        'myfatoorah' => 'MyFatoorah',
        'stripe' => 'Stripe',
        'paypal' => 'PayPal',
        'wallet' => 'Wallet',
        'bank_transfer' => 'Bank Transfer',
    ];

    // =========================================================================
    // STATUS FORMATTING
    // =========================================================================

    /**
     * Get localized status label
     */
    public function getStatusLabel(string $status): string
    {
        $config = self::STATUS_CONFIG[$status] ?? null;
        return $config ? __($config['label_key']) : ucfirst($status);
    }

    /**
     * Get CSS class for status badge
     */
    public function getStatusClass(string $status): string
    {
        return self::STATUS_CONFIG[$status]['class'] ?? 'bg-secondary text-white';
    }

    /**
     * Get status color name (for custom styling)
     */
    public function getStatusColor(string $status): string
    {
        return self::STATUS_CONFIG[$status]['color'] ?? 'secondary';
    }

    /**
     * Get status icon class
     */
    public function getStatusIcon(string $status): string
    {
        return self::STATUS_CONFIG[$status]['icon'] ?? 'fa-circle';
    }

    /**
     * Get full status display data
     */
    public function getStatusDisplay(string $status): array
    {
        $config = self::STATUS_CONFIG[$status] ?? [
            'label_key' => ucfirst($status),
            'class' => 'bg-secondary text-white',
            'color' => 'secondary',
            'icon' => 'fa-circle',
        ];

        return [
            'status' => $status,
            'label' => __($config['label_key']),
            'class' => $config['class'],
            'color' => $config['color'],
            'icon' => $config['icon'],
        ];
    }

    // =========================================================================
    // PAYMENT STATUS FORMATTING
    // =========================================================================

    /**
     * Get localized payment status label
     */
    public function getPaymentStatusLabel(string $status): string
    {
        $config = self::PAYMENT_STATUS_CONFIG[$status] ?? null;
        return $config ? __($config['label_key']) : ucfirst($status);
    }

    /**
     * Get CSS class for payment status badge
     */
    public function getPaymentStatusClass(string $status): string
    {
        return self::PAYMENT_STATUS_CONFIG[$status]['class'] ?? 'bg-secondary text-white';
    }

    /**
     * Get full payment status display data
     */
    public function getPaymentStatusDisplay(string $status): array
    {
        $config = self::PAYMENT_STATUS_CONFIG[$status] ?? [
            'label_key' => ucfirst($status),
            'class' => 'bg-secondary text-white',
            'color' => 'secondary',
        ];

        return [
            'status' => $status,
            'label' => __($config['label_key']),
            'class' => $config['class'],
            'color' => $config['color'],
        ];
    }

    // =========================================================================
    // PAYMENT METHOD FORMATTING
    // =========================================================================

    /**
     * Format payment method for display
     */
    public function formatPaymentMethod(string $method): string
    {
        $key = strtolower(trim($method));
        $displayName = self::PAYMENT_METHODS[$key] ?? null;

        return $displayName ? __($displayName) : ucfirst($method);
    }

    // =========================================================================
    // DATE FORMATTING
    // =========================================================================

    /**
     * Format date for list display
     */
    public function formatDateForList(?\DateTimeInterface $date): string
    {
        return $date?->format(self::DATE_FORMAT_LIST) ?? __('N/A');
    }

    /**
     * Format date for detail display
     */
    public function formatDateForDetail(?\DateTimeInterface $date): string
    {
        return $date?->format(self::DATE_FORMAT_DETAIL) ?? __('N/A');
    }

    /**
     * Format full date with time
     */
    public function formatDateFull(?\DateTimeInterface $date): string
    {
        return $date?->format(self::DATE_FORMAT_FULL) ?? __('N/A');
    }

    /**
     * Format date only (no time)
     */
    public function formatDateOnly(?\DateTimeInterface $date): string
    {
        return $date?->format('d-M-Y') ?? __('N/A');
    }

    // =========================================================================
    // PRICE FORMATTING
    // =========================================================================

    /**
     * Format price using monetaryUnit service
     */
    public function formatPrice(float $amount): string
    {
        return monetaryUnit()->format($amount);
    }

    /**
     * Format price with specific currency (for orders with stored currency)
     */
    public function formatOrderPrice(float $amount, ?string $currencySign = null): string
    {
        if ($currencySign) {
            return \PriceHelper::showOrderCurrencyPrice($amount, $currencySign);
        }

        return monetaryUnit()->format($amount);
    }

    /**
     * Format price with currency conversion
     */
    public function formatPriceConverted(float $amount, float $currencyValue = 1.0, ?string $currencySign = null): string
    {
        $converted = $amount * $currencyValue;

        if ($currencySign) {
            return \PriceHelper::showOrderCurrencyPrice($converted, $currencySign);
        }

        return monetaryUnit()->format($converted);
    }

    // =========================================================================
    // PURCHASE DISPLAY DATA
    // =========================================================================

    /**
     * Build display data for a purchase (for list view)
     */
    public function forList(Purchase $purchase): array
    {
        $status = $purchase->status ?? 'pending';
        $paymentStatus = $purchase->payment_status ?? 'pending';

        return [
            'id' => $purchase->id,
            'order_number' => $purchase->purchase_number ?? "#{$purchase->id}",
            'date_formatted' => $this->formatDateForList($purchase->created_at),
            'total_formatted' => $this->formatOrderPrice(
                $purchase->pay_amount * ($purchase->currency_value ?: 1),
                $purchase->currency_sign
            ),
            'items_count' => is_array($purchase->cart) ? count($purchase->cart['items'] ?? $purchase->cart) : 0,
            'status' => $status,
            'status_label' => $this->getStatusLabel($status),
            'status_class' => $this->getStatusClass($status),
            'status_color' => $this->getStatusColor($status),
            'payment_status' => $paymentStatus,
            'payment_status_label' => $this->getPaymentStatusLabel($paymentStatus),
            'payment_status_class' => $this->getPaymentStatusClass($paymentStatus),
            'can_cancel' => in_array($status, ['pending', 'processing']),
            'can_track' => !empty($purchase->tracking_number),
            'tracking_number' => $purchase->tracking_number,
        ];
    }

    /**
     * Build display data for a purchase (for detail view)
     */
    public function forDetail(Purchase $purchase): array
    {
        $status = $purchase->status ?? 'pending';
        $paymentStatus = $purchase->payment_status ?? 'pending';
        $currencyValue = $purchase->currency_value ?: 1;
        $currencySign = $purchase->currency_sign;

        return [
            // Basic info
            'id' => $purchase->id,
            'order_number' => $purchase->purchase_number ?? "#{$purchase->id}",
            'date_formatted' => $this->formatDateForDetail($purchase->created_at),
            'date_full' => $this->formatDateFull($purchase->created_at),

            // Status
            'status' => $this->getStatusDisplay($status),
            'payment_status' => $this->getPaymentStatusDisplay($paymentStatus),

            // Payment method
            'payment_method' => $this->formatPaymentMethod($purchase->method ?? ''),

            // Totals - all pre-formatted
            'subtotal_formatted' => $this->formatOrderPrice($purchase->subtotal ?? 0, $currencySign),
            'shipping_cost_formatted' => $this->formatOrderPrice($purchase->shipping_cost ?? 0, $currencySign),
            'tax_formatted' => $this->formatOrderPrice(($purchase->tax ?? 0) / $currencyValue, $currencySign),
            'discount_formatted' => $this->formatOrderPrice($purchase->discount_amount ?? 0, $currencySign),
            'wallet_price_formatted' => $this->formatOrderPrice(($purchase->wallet_price ?? 0) * $currencyValue, $currencySign),
            'pay_amount_formatted' => $this->formatOrderPrice(($purchase->pay_amount ?? 0) * $currencyValue, $currencySign),
            'total_formatted' => $this->formatOrderPrice(
                (($purchase->pay_amount ?? 0) + ($purchase->wallet_price ?? 0)) * $currencyValue,
                $currencySign
            ),

            // Actions
            'can_cancel' => in_array($status, ['pending', 'processing']),
            'can_track' => !empty($purchase->tracking_number),
            'tracking_number' => $purchase->tracking_number,
            'tracking_url' => $purchase->tracking_number
                ? route('front.tracking', ['number' => $purchase->tracking_number])
                : null,
        ];
    }

    /**
     * Build display data for cart items
     */
    public function formatCartItems(array $cart, float $currencyValue = 1.0, ?string $currencySign = null): array
    {
        $items = $cart['items'] ?? $cart;

        return collect($items)->map(function ($item, $key) use ($currencyValue, $currencySign) {
            $qty = (int) ($item['qty'] ?? 1);
            $totalPrice = (float) ($item['price'] ?? 0);
            $unitPrice = $qty > 0 ? $totalPrice / $qty : $totalPrice;

            return [
                'key' => $key,
                'name' => getLocalizedCatalogItemName($item),
                'part_number' => $item['part_number'] ?? $item['item']['part_number'] ?? null,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'unit_price_formatted' => $this->formatPriceConverted($unitPrice, $currencyValue, $currencySign),
                'total_price' => $totalPrice,
                'total_price_formatted' => $this->formatPriceConverted($totalPrice, $currencyValue, $currencySign),
                'photo' => $item['photo'] ?? asset('assets/images/noimage.png'),
                'merchant_name' => getMerchantName($item),
                'quality_brand' => $item['quality_brand'] ?? null,
                'condition' => isset($item['item']['item_condition']) && $item['item']['item_condition'] == 1
                    ? __('Used')
                    : __('New'),
                'discount' => $item['discount'] ?? 0,
                'discount_text' => ($item['discount'] ?? 0) > 0
                    ? '(' . $item['discount'] . '% ' . __('Off') . ')'
                    : '',
                'product_url' => !empty($item['item']['part_number'])
                    ? route('front.part-result', $item['item']['part_number'])
                    : '#',
            ];
        })->values()->toArray();
    }

    /**
     * Build timeline data for purchase tracking
     */
    public function buildTimeline(Purchase $purchase): array
    {
        $status = $purchase->status ?? 'pending';

        return [
            [
                'step' => 'placed',
                'label' => __('Order Placed'),
                'date' => $this->formatDateForDetail($purchase->created_at),
                'completed' => true,
                'current' => $status === 'pending',
            ],
            [
                'step' => 'processing',
                'label' => __('Processing'),
                'date' => $this->formatDateForDetail($purchase->processing_at),
                'completed' => in_array($status, ['processing', 'on_delivery', 'shipped', 'delivered', 'completed']),
                'current' => $status === 'processing',
            ],
            [
                'step' => 'shipped',
                'label' => __('Shipped'),
                'date' => $this->formatDateForDetail($purchase->shipped_at),
                'completed' => in_array($status, ['on_delivery', 'shipped', 'delivered', 'completed']),
                'current' => in_array($status, ['on_delivery', 'shipped']),
            ],
            [
                'step' => 'delivered',
                'label' => __('Delivered'),
                'date' => $this->formatDateForDetail($purchase->delivered_at),
                'completed' => in_array($status, ['delivered', 'completed']),
                'current' => in_array($status, ['delivered', 'completed']),
            ],
        ];
    }

    // =========================================================================
    // MERCHANT PURCHASE DISPLAY
    // =========================================================================

    /**
     * Build display data for merchant purchases
     */
    public function formatMerchantPurchases(Collection $merchantPurchases): array
    {
        return $merchantPurchases->map(function (MerchantPurchase $mp) {
            $status = $mp->status ?? 'pending';

            return [
                'id' => $mp->id,
                'merchant_id' => $mp->user_id,
                'merchant_name' => $mp->user ? getLocalizedShopName($mp->user) : __('Unknown'),
                'status' => $status,
                'status_label' => $this->getStatusLabel($status),
                'status_class' => $this->getStatusClass($status),
                'total_formatted' => $this->formatPrice($mp->price ?? 0),
                'items_count' => is_array($mp->cart) ? count($mp->cart['items'] ?? $mp->cart) : 0,
            ];
        })->toArray();
    }

    // =========================================================================
    // COLLECTION HELPERS
    // =========================================================================

    /**
     * Format a collection of purchases for list display
     */
    public function formatCollection($purchases): array
    {
        return collect($purchases)->map(fn($p) => $this->forList($p))->toArray();
    }
}
