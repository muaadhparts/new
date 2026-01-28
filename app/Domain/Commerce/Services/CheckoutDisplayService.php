<?php

namespace App\Domain\Commerce\Services;

/**
 * CheckoutDisplayService - Centralized formatting for checkout display
 *
 * API-Ready: All formatting in one place for Web and API consumption.
 * DATA FLOW POLICY: Controller → Service → DTO → View/API
 *
 * @see docs/rules/DATA_FLOW_POLICY.md
 */
class CheckoutDisplayService
{
    // =========================================================================
    // CART FORMATTING
    // =========================================================================

    /**
     * Format cart data for display
     */
    public function formatCart(array $cartData): array
    {
        $cartData['total_price_formatted'] = monetaryUnit()->format($cartData['total_price'] ?? 0);
        return $cartData;
    }

    // =========================================================================
    // TOTALS FORMATTING
    // =========================================================================

    /**
     * Format totals for address step
     */
    public function formatTotalsForAddress(array $totalsData): array
    {
        return array_merge($totalsData, [
            'grand_total_formatted' => monetaryUnit()->format($totalsData['grand_total'] ?? 0),
        ]);
    }

    /**
     * Format totals for shipping step
     */
    public function formatTotalsForShipping(array $totalsData, array $cartData = []): array
    {
        return array_merge($totalsData, [
            'discount_amount_formatted' => monetaryUnit()->format($totalsData['discount_amount'] ?? 0),
            'tax_amount_formatted' => monetaryUnit()->format($totalsData['tax_amount'] ?? 0),
            'grand_total_formatted' => monetaryUnit()->format($totalsData['grand_total'] ?? $cartData['total_price'] ?? 0),
        ]);
    }

    /**
     * Format totals for payment step
     */
    public function formatTotalsForPayment(array $totalsData): array
    {
        return array_merge($totalsData, [
            'discount_amount_formatted' => monetaryUnit()->format($totalsData['discount_amount'] ?? 0),
            'tax_amount_formatted' => monetaryUnit()->format($totalsData['tax_amount'] ?? 0),
            'shipping_cost_formatted' => monetaryUnit()->format($totalsData['shipping_cost'] ?? 0),
            'courier_fee_formatted' => monetaryUnit()->format($totalsData['courier_fee'] ?? 0),
            'grand_total_formatted' => monetaryUnit()->format($totalsData['grand_total'] ?? 0),
        ]);
    }

    // =========================================================================
    // SHIPPING FORMATTING
    // =========================================================================

    /**
     * Format shipping providers with method prices
     */
    public function formatShippingProviders(array $providers): array
    {
        foreach ($providers as &$provider) {
            if (!empty($provider['methods'])) {
                foreach ($provider['methods'] as &$method) {
                    $method['free_above_formatted'] = monetaryUnit()->format($method['free_above'] ?? 0);
                    $method['original_price_formatted'] = monetaryUnit()->format($method['original_price'] ?? 0);
                }
            }
        }
        return $providers;
    }

    /**
     * Format courier options with prices
     */
    public function formatCourierOptions(array $couriers): array
    {
        foreach ($couriers as &$courier) {
            $courier['delivery_fee_formatted'] = monetaryUnit()->format($courier['delivery_fee'] ?? 0);
        }
        return $couriers;
    }

    /**
     * Format shipping data for payment step
     */
    public function formatShippingForPayment(array $shippingData): array
    {
        return array_merge($shippingData, [
            'courier_fee_formatted' => monetaryUnit()->format($shippingData['courier_fee'] ?? 0),
            'shipping_cost_formatted' => monetaryUnit()->format($shippingData['shipping_cost'] ?? 0),
            'original_shipping_cost_formatted' => monetaryUnit()->format($shippingData['original_shipping_cost'] ?? 0),
        ]);
    }

    // =========================================================================
    // PREVIEW/CALCULATION FORMATTING
    // =========================================================================

    /**
     * Format preview totals for AJAX response
     */
    public function formatPreviewTotals(array $totals, string $currencySign): array
    {
        return [
            'subtotal' => $currencySign . number_format($totals['subtotal'], 2),
            'shipping_cost' => $currencySign . number_format($totals['shipping_cost'], 2),
            'courier_fee' => $currencySign . number_format($totals['courier_fee'], 2),
            'tax_amount' => $currencySign . number_format($totals['tax_amount'], 2),
            'grand_total' => $currencySign . number_format($totals['grand_total'], 2),
        ];
    }

    /**
     * Format tax calculation for AJAX response
     */
    public function formatTaxCalculation(float $subtotal, float $taxAmount, float $total, string $currencySign): array
    {
        return [
            'subtotal' => $currencySign . number_format($subtotal, 2),
            'tax_amount' => $currencySign . number_format($taxAmount, 2),
            'total' => $currencySign . number_format($total, 2),
        ];
    }

    // =========================================================================
    // COMPLETE STEP FORMATTING
    // =========================================================================

    /**
     * Format all data for address step view
     */
    public function formatForAddressStep(array $result): array
    {
        $cartData = $result['data']['cart'] ?? [];

        return [
            'cart' => $this->formatCart($cartData),
        ];
    }

    /**
     * Format all data for shipping step view
     */
    public function formatForShippingStep(array $result): array
    {
        $cartData = $result['data']['cart'] ?? [];
        $totalsData = $result['data']['totals'] ?? [];
        $shippingProviders = $result['data']['shipping_options'] ?? [];
        $couriers = $result['data']['courier_options'] ?? [];

        return [
            'cart' => $this->formatCart($cartData),
            'totals' => $this->formatTotalsForShipping($totalsData, $cartData),
            'shipping_providers' => $this->formatShippingProviders($shippingProviders),
            'couriers' => $this->formatCourierOptions($couriers),
        ];
    }

    /**
     * Format all data for payment step view
     */
    public function formatForPaymentStep(array $result): array
    {
        $cartData = $result['data']['cart'] ?? [];
        $totalsData = $result['data']['totals'] ?? [];
        $shippingData = $result['data']['shipping'] ?? [];

        return [
            'cart' => $this->formatCart($cartData),
            'totals' => $this->formatTotalsForPayment($totalsData),
            'shipping' => $this->formatShippingForPayment($shippingData),
        ];
    }
}
