<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Trait for saving customer's shipping choice when creating orders
 *
 * This trait extracts and saves the shipping company selected by the customer
 * during checkout. It handles both Tryoto format and regular shipping IDs.
 *
 * Tryoto format: "deliveryOptionId#companyName#price"
 * Regular format: numeric shipping ID
 */
trait SavesCustomerShippingChoice
{
    /**
     * Extract customer's shipping choice data from step2 session
     *
     * @param array|null $step2Data Step2 session data (if null, will read from session)
     * @param int|null $vendorId Vendor ID for vendor-specific checkout
     * @param bool $isVendorCheckout Whether this is vendor-specific checkout
     * @return string|null JSON encoded shipping choices per vendor, or null if empty
     */
    protected function extractCustomerShippingChoice($step2Data = null, $vendorId = null, $isVendorCheckout = false)
    {
        $choices = [];

        // If step2Data not provided, try to get from session
        if ($step2Data === null) {
            if ($isVendorCheckout && $vendorId) {
                $step2Data = Session::get('vendor_step2_' . $vendorId, []);
            } else {
                $step2Data = Session::get('step2', []);
            }
        }

        // Get shipping selections from step2 data
        $shippingSelections = $step2Data['shipping'] ?? [];

        // For vendor checkout, the shipping might be stored directly
        if ($isVendorCheckout && $vendorId) {
            // Check if shipping is stored as vendor_id => value
            if (is_array($shippingSelections) && isset($shippingSelections[$vendorId])) {
                // Already in correct format
            } elseif (!is_array($shippingSelections) && !empty($shippingSelections)) {
                // Shipping value stored directly, convert to array format
                $shippingSelections = [$vendorId => $shippingSelections];
            }
        }

        if (!is_array($shippingSelections)) {
            // If single value, try to use it with vendorId
            if ($vendorId && !empty($shippingSelections)) {
                $shippingSelections = [$vendorId => $shippingSelections];
            } else {
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
                        'title' => $shipping->title ?? '',
                        'price' => (float) ($shipping->price ?? 0),
                        'selected_at' => now()->toIso8601String(),
                    ];
                }
            }
        }

        return !empty($choices) ? json_encode($choices) : null;
    }

    /**
     * Add customer shipping choice to input array
     *
     * @param array $input The input array to modify
     * @param array|null $step2Data Step2 session data
     * @param int|null $vendorId Vendor ID
     * @param bool $isVendorCheckout Whether vendor-specific checkout
     * @return array Modified input array
     */
    protected function addCustomerShippingChoiceToInput(array $input, $step2Data = null, $vendorId = null, $isVendorCheckout = false)
    {
        $input['customer_shipping_choice'] = $this->extractCustomerShippingChoice($step2Data, $vendorId, $isVendorCheckout);
        return $input;
    }
}
