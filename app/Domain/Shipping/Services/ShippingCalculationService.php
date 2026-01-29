<?php

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Models\Shipping;

/**
 * ShippingCalculationService - Centralized shipping calculation service
 *
 * Single source of truth for all shipping-related calculations.
 * Replaces static methods in Purchase model.
 *
 * Domain: Shipping
 * Responsibility: Calculate shipping data, costs, and availability
 *
 * ARCHITECTURE:
 * - Service Layer Pattern
 * - Single Responsibility Principle
 * - Dependency Injection ready
 */
class ShippingCalculationService
{
    /**
     * Get shipping data for a cart
     *
     * Determines if all cart items belong to a single merchant,
     * and returns available shipping options for that merchant.
     *
     * @param object $cart Cart object with items
     * @return array ['shipping_data' => Collection, 'merchant_shipping_id' => int]
     */
    public function getShippingDataForCart($cart): array
    {
        $merchant_shipping_id = 0;
        $users = [];

        // Collect all unique merchant IDs from cart items
        foreach ($cart->items as $cartItem) {
            $users[] = $cartItem['item']['user_id'];
        }
        $users = array_unique($users);

        // Only show shipping options if all items are from the same merchant
        if (count($users) == 1) {
            $merchantId = (int) $users[0];

            $shipping_data = Shipping::where('status', 1)
                ->where(function ($q) use ($merchantId) {
                    $q->where('user_id', $merchantId)
                      ->orWhere(function ($q2) use ($merchantId) {
                          $q2->where('user_id', 0)
                             ->where('operator', $merchantId);
                      });
                })
                ->get();

            if ($shipping_data->count() > 0) {
                $merchant_shipping_id = $merchantId;
            }
        } else {
            // Multiple merchants - no unified shipping available
            $shipping_data = collect();
        }

        return [
            'shipping_data' => $shipping_data,
            'merchant_shipping_id' => $merchant_shipping_id,
        ];
    }

    /**
     * Check if cart has items from multiple merchants
     *
     * @param object $cart Cart object with items
     * @return bool True if multiple merchants, false otherwise
     */
    public function hasMultipleMerchants($cart): bool
    {
        $users = [];

        foreach ($cart->items as $cartItem) {
            $users[] = $cartItem['item']['user_id'];
        }

        return count(array_unique($users)) > 1;
    }

    /**
     * Get all unique merchant IDs from cart
     *
     * @param object $cart Cart object with items
     * @return array Array of unique merchant IDs
     */
    public function getMerchantIdsFromCart($cart): array
    {
        $users = [];

        foreach ($cart->items as $cartItem) {
            $users[] = $cartItem['item']['user_id'];
        }

        return array_unique($users);
    }

    /**
     * Get shipping options for a specific merchant
     *
     * @param int $merchantId Merchant user ID
     * @return \Illuminate\Database\Eloquent\Collection Shipping options
     */
    public function getShippingOptionsForMerchant(int $merchantId)
    {
        return Shipping::where('status', 1)
            ->where(function ($q) use ($merchantId) {
                $q->where('user_id', $merchantId)
                  ->orWhere(function ($q2) use ($merchantId) {
                      $q2->where('user_id', 0)
                         ->where('operator', $merchantId);
                  });
            })
            ->get();
    }
}
