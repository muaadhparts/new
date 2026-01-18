<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Trait for saving customer's shipping choice when creating orders
 *
 * This trait extracts and saves the shipping company selected by the customer
 * during checkout. It handles:
 * 1. Tryoto format: "deliveryOptionId#companyName#price"
 * 2. Regular shipping ID
 * 3. Local courier delivery
 *
 * UPDATED: 2026-01-09 - Added support for courier delivery type
 */
trait SavesCustomerShippingChoice
{
    /**
     * Extract customer's shipping choice data from step2 session
     *
     * @param array|null $step2Data Step2 session data (if null, will read from session)
     * @param int|null $merchantId Merchant ID for merchant-specific checkout
     * @param bool $isMerchantCheckout Whether this is merchant-specific checkout (default: true for merchant checkout)
     * @return array|null Shipping choices array per merchant, or null if empty
     */
    protected function extractCustomerShippingChoice($step2Data = null, $merchantId = null, $isMerchantCheckout = true)
    {
        $choices = [];

        // If step2Data not provided, try to get from session
        if ($step2Data === null) {
            if ($merchantId) {
                $step2Data = Session::get('merchant_step2_' . $merchantId, []);
            } else {
                $step2Data = Session::get('step2', []);
            }
        }

        // Convert object to array if needed
        if (is_object($step2Data)) {
            $step2Data = (array) $step2Data;
        }

        // ✅ PRIORITY 1: Check for local courier delivery
        $deliveryType = $step2Data['delivery_type'] ?? null;
        if ($deliveryType === 'local_courier' && $merchantId) {
            $courierId = $step2Data['courier_id'] ?? null;
            $courierFee = (float)($step2Data['courier_fee'] ?? 0);
            $courierName = $step2Data['courier_name'] ?? 'Courier';
            $merchantBranchId = $step2Data['merchant_branch_id'] ?? null;
            $serviceAreaId = $step2Data['selected_service_area_id'] ?? null;

            if ($courierId) {
                $choices[$merchantId] = [
                    'provider' => 'local_courier',
                    'courier_id' => (int) $courierId,
                    'courier_name' => $courierName,
                    'price' => $courierFee,
                    'merchant_branch_id' => $merchantBranchId,
                    'service_area_id' => $serviceAreaId,
                    'selected_at' => now()->toIso8601String(),
                ];
                return $choices;
            }
        }

        // ✅ PRIORITY 2: Check for shipping company selection
        $shippingSelections = $step2Data['shipping'] ?? $step2Data['saved_shipping_selections'] ?? [];

        // For merchant checkout, the shipping might be stored directly
        if ($merchantId) {
            if (is_array($shippingSelections) && isset($shippingSelections[$merchantId])) {
                // Already in correct format
            } elseif (!is_array($shippingSelections) && !empty($shippingSelections)) {
                // Shipping value stored directly, convert to array format
                $shippingSelections = [$merchantId => $shippingSelections];
            }
        }

        if (!is_array($shippingSelections)) {
            // If single value, try to use it with merchantId
            if ($merchantId && !empty($shippingSelections)) {
                $shippingSelections = [$merchantId => $shippingSelections];
            } else {
                // No shipping selection found - check if shipping_cost was already calculated
                $shippingCost = (float)($step2Data['shipping_cost'] ?? 0);
                if ($shippingCost > 0 && $merchantId) {
                    // Use calculated shipping cost from step2
                    $choices[$merchantId] = [
                        'provider' => 'calculated',
                        'price' => $shippingCost,
                        'company_name' => $step2Data['shipping_company'] ?? '',
                        'selected_at' => now()->toIso8601String(),
                    ];
                    return $choices;
                }
                return null;
            }
        }

        foreach ($shippingSelections as $vid => $shippingValue) {
            if (empty($shippingValue)) continue;

            // Check if it's Tryoto format: "deliveryOptionId#companyName#price"
            if (is_string($shippingValue) && strpos($shippingValue, '#') !== false) {
                $parts = explode('#', $shippingValue);

                if (count($parts) >= 3) {
                    $choices[$vid] = [
                        'provider' => 'tryoto',
                        'delivery_option_id' => $parts[0],
                        'company_name' => $parts[1],
                        'price' => (float) $parts[2],
                        'selected_at' => now()->toIso8601String(),
                    ];
                }
            } elseif (is_numeric($shippingValue)) {
                // Regular shipping ID (manual/debts)
                $shipping = DB::table('shippings')->find($shippingValue);

                if ($shipping) {
                    $choices[$vid] = [
                        'provider' => $shipping->provider ?? 'manual',
                        'shipping_id' => (int) $shippingValue,
                        'name' => $shipping->name ?? '',
                        'price' => (float) ($shipping->price ?? 0),
                        'selected_at' => now()->toIso8601String(),
                    ];
                }
            }
        }

        // Return array (Model will handle JSON encoding via cast)
        return !empty($choices) ? $choices : null;
    }

    /**
     * Add customer shipping choice to input array
     *
     * @param array $input The input array to modify
     * @param array|null $step2Data Step2 session data
     * @param int|null $merchantId Merchant ID
     * @param bool $isMerchantCheckout Whether merchant-specific checkout
     * @return array Modified input array
     */
    protected function addCustomerShippingChoiceToInput(array $input, $step2Data = null, $merchantId = null, $isMerchantCheckout = true)
    {
        $input['customer_shipping_choice'] = $this->extractCustomerShippingChoice($step2Data, $merchantId, $isMerchantCheckout);
        return $input;
    }
}
