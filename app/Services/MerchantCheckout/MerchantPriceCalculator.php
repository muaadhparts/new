<?php

namespace App\Services\MerchantCheckout;

use App\Models\MonetaryUnit;
use App\Models\Shipping;
use App\Models\Country;
use App\Models\State;
use App\Models\CourierServiceArea;
use Illuminate\Support\Facades\Session;

/**
 * Centralized price calculation for Merchant Checkout
 *
 * All price calculations in one place - no scattered logic
 */
class MerchantPriceCalculator
{
    protected ?MonetaryUnit $monetaryUnit = null;

    public function __construct()
    {
        // Currency is loaded lazily to avoid DI issues with Eloquent models
    }

    /**
     * Calculate complete checkout totals
     */
    public function calculateTotals(array $cartItems, array $options = []): array
    {
        $itemsTotal = $this->calculateItemsTotal($cartItems);
        $discountAmount = $options['discount_amount'] ?? 0;
        $shippingCost = $options['shipping_cost'] ?? 0;
        $courierFee = $options['courier_fee'] ?? 0;
        $taxRate = $options['tax_rate'] ?? 0;

        // Calculate tax on items (before discount)
        $taxAmount = $this->calculateTax($itemsTotal, $taxRate);

        // Subtotal = Items - Discount
        $subtotal = $itemsTotal - $discountAmount;

        // Grand Total = Subtotal + Tax + Shipping + Courier
        $grandTotal = $subtotal + $taxAmount + $shippingCost + $courierFee;

        return [
            'items_total' => round($itemsTotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'subtotal' => round($subtotal, 2),
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 2),
            'shipping_cost' => round($shippingCost, 2),
            'courier_fee' => round($courierFee, 2),
            'grand_total' => round($grandTotal, 2),
            'currency' => [
                'code' => $this->getMonetaryUnit()->name,
                'sign' => $this->getMonetaryUnit()->sign,
                'value' => $this->getMonetaryUnit()->value,
            ],
            'formatted' => [
                'items_total' => $this->formatPrice($itemsTotal),
                'discount_amount' => $this->formatPrice($discountAmount),
                'subtotal' => $this->formatPrice($subtotal),
                'tax_amount' => $this->formatPrice($taxAmount),
                'shipping_cost' => $this->formatPrice($shippingCost),
                'courier_fee' => $this->formatPrice($courierFee),
                'grand_total' => $this->formatPrice($grandTotal),
            ],
        ];
    }

    /**
     * Calculate items total from cart items
     */
    public function calculateItemsTotal(array $cartItems): float
    {
        $total = 0;
        foreach ($cartItems as $item) {
            $total += (float)($item['price'] ?? $item['total_price'] ?? 0);
        }
        return $total;
    }

    /**
     * Calculate tax amount
     */
    public function calculateTax(float $amount, float $taxRate): float
    {
        if ($taxRate <= 0) {
            return 0;
        }
        return ($amount * $taxRate) / 100;
    }

    /**
     * Get tax rate for location
     */
    public function getTaxRateForLocation(?int $countryId, ?int $stateId = null): array
    {
        $taxRate = 0;
        $taxLocation = '';

        if ($stateId) {
            $state = State::find($stateId);
            if ($state && $state->tax > 0) {
                $taxRate = $state->tax;
                $taxLocation = $state->name;
            }
        }

        if ($taxRate == 0 && $countryId) {
            $country = Country::find($countryId);
            if ($country && $country->tax > 0) {
                $taxRate = $country->tax;
                $taxLocation = $country->name;
            }
        }

        return [
            'tax_rate' => $taxRate,
            'tax_location' => $taxLocation,
        ];
    }

    /**
     * Calculate shipping cost with free shipping check
     */
    public function calculateShippingCost(int $shippingId, float $itemsTotal): array
    {
        $shipping = Shipping::find($shippingId);

        if (!$shipping) {
            return [
                'shipping_id' => 0,
                'shipping_name' => null,
                'shipping_cost' => 0,
                'original_cost' => 0,
                'is_free' => false,
                'free_threshold' => 0,
            ];
        }

        $originalCost = (float)$shipping->price;
        $freeAbove = (float)($shipping->free_above ?? 0);
        $isFree = $freeAbove > 0 && $itemsTotal >= $freeAbove;
        $finalCost = $isFree ? 0 : $originalCost;

        return [
            'shipping_id' => $shipping->id,
            'shipping_name' => $shipping->name,
            'shipping_cost' => round($finalCost, 2),
            'original_cost' => round($originalCost, 2),
            'is_free' => $isFree,
            'free_threshold' => $freeAbove,
        ];
    }

    /**
     * Calculate courier fee
     */
    public function calculateCourierFee(int $courierId, int $cityId): array
    {
        $serviceArea = CourierServiceArea::where('courier_id', $courierId)
            ->where('city_id', $cityId)
            ->first();

        if (!$serviceArea) {
            return [
                'courier_id' => 0,
                'courier_name' => null,
                'courier_fee' => 0,
                'service_area_id' => 0,
            ];
        }

        return [
            'courier_id' => $courierId,
            'courier_name' => $serviceArea->courier->name ?? 'Courier',
            'courier_fee' => round((float)$serviceArea->price, 2),
            'service_area_id' => $serviceArea->id,
        ];
    }

    /**
     * Convert price to current currency
     */
    public function convertToCurrency(float $amount): float
    {
        return round($amount * $this->getMonetaryUnit()->value, 2);
    }

    /**
     * Convert price from current currency to base
     */
    public function convertToBase(float $amount): float
    {
        $currencyValue = $this->getMonetaryUnit()->value;
        if ($currencyValue == 0) {
            return $amount;
        }
        return round($amount / $currencyValue, 2);
    }

    /**
     * Format price with currency
     */
    public function formatPrice(float $amount): string
    {
        $converted = $this->convertToCurrency($amount);
        $ps = platformSettings();
        $currency = $this->getMonetaryUnit();

        if ($ps->get('currency_format', 0) == 0) {
            return $currency->sign . number_format($converted, 2);
        }
        return number_format($converted, 2) . $currency->sign;
    }

    /**
     * Get current session currency (via MonetaryUnitService - SINGLE SOURCE OF TRUTH)
     */
    protected function getSessionMonetaryUnit(): MonetaryUnit
    {
        $curr = monetaryUnit()->getCurrent();
        if (!$curr) {
            throw new \RuntimeException('No monetary unit configured. Please set a default monetary unit in the database.');
        }
        return $curr;
    }

    /**
     * Set currency
     */
    public function setMonetaryUnit(MonetaryUnit $monetaryUnit): self
    {
        $this->monetaryUnit = $monetaryUnit;
        return $this;
    }

    /**
     * Get currency (lazy loaded)
     */
    public function getMonetaryUnit(): MonetaryUnit
    {
        if ($this->monetaryUnit === null) {
            $this->monetaryUnit = $this->getSessionMonetaryUnit();
        }
        return $this->monetaryUnit;
    }
}
