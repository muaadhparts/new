<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Country;
use App\Models\Package;
use App\Models\Shipping;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * CheckoutDataService - N+1 Optimized Data Loading for Checkout
 *
 * This service pre-loads all checkout-related data in bulk to avoid
 * N+1 queries inside Blade templates.
 *
 * Performance Impact:
 * - Before: 3-5 queries per vendor (Shipping, Package, User) = O(n) queries
 * - After: 3 bulk queries total = O(1) queries
 */
class CheckoutDataService
{
    /**
     * Pre-load all vendor data needed for checkout step2
     *
     * @param array $products Cart products array
     * @return array Structured data for all vendors
     */
    public static function loadVendorData(array $products): array
    {
        // Extract unique vendor IDs from products
        $merchantIds = collect($products)
            ->pluck('user_id')
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        // Handle case where vendor_id = 0 (admin products)
        $hasAdminProducts = in_array(0, $merchantIds) || empty($merchantIds);

        // Remove 0 from vendor IDs for User query
        $merchantIds = array_filter($merchantIds, fn($id) => $id > 0);

        // Bulk load all data
        $vendors = self::loadVendors($merchantIds);
        $shippingByVendor = self::loadShipping($merchantIds, $hasAdminProducts);
        $packagingByVendor = self::loadPackaging($merchantIds);
        $admin = $hasAdminProducts ? Admin::find(1) : null;

        // Provider labels (static)
        $providerLabels = [
            'manual' => __('Manual Shipping'),
            'debts' => __('Debts Shipping'),
            'tryoto' => __('Smart Shipping (Tryoto)'),
        ];

        // Build result array
        $result = [];

        foreach ($merchantIds as $merchantId) {
            $shipping = $shippingByVendor[$merchantId] ?? collect();
            $result[$merchantId] = [
                'vendor' => $vendors[$merchantId] ?? null,
                'shipping' => $shipping,
                'packaging' => $packagingByVendor[$merchantId] ?? collect(),
                'grouped_shipping' => $shipping->groupBy('provider'),
                'provider_labels' => $providerLabels,
            ];
        }

        // Add admin data if needed
        if ($hasAdminProducts) {
            $adminShipping = $shippingByVendor[0] ?? collect();
            $result[0] = [
                'vendor' => $admin,
                'shipping' => $adminShipping,
                'packaging' => collect(), // No global packaging
                'grouped_shipping' => $adminShipping->groupBy('provider'),
                'provider_labels' => $providerLabels,
            ];
        }

        return $result;
    }

    /**
     * Bulk load vendors by IDs
     */
    private static function loadVendors(array $merchantIds): Collection
    {
        if (empty($merchantIds)) {
            return collect();
        }

        return User::whereIn('id', $merchantIds)
            ->get()
            ->keyBy('id');
    }

    /**
     * Bulk load shipping for all vendors
     * ✅ FIX: Returns Collection grouped by user_id (not array)
     */
    private static function loadShipping(array $merchantIds, bool $includeAdmin = false): Collection
    {
        $allVendorIds = $merchantIds;
        if ($includeAdmin) {
            $allVendorIds[] = 0;
        }

        if (empty($allVendorIds)) {
            return collect();
        }

        // Load all shipping records for all vendors at once
        $allShipping = Shipping::whereIn('user_id', $allVendorIds)->get();

        // Group by user_id - returns Collection of Collections
        return $allShipping->groupBy('user_id');
    }

    /**
     * Bulk load packaging for all vendors
     * ✅ FIX: Returns Collection grouped by user_id
     */
    private static function loadPackaging(array $merchantIds): Collection
    {
        if (empty($merchantIds)) {
            return collect();
        }

        $allPackaging = Package::whereIn('user_id', $merchantIds)->get();

        // Group by user_id - returns Collection of Collections
        return $allPackaging->groupBy('user_id');
    }

    /**
     * Load country data for checkout
     *
     * @param object|null $step1 Step1 session data
     * @return Country|null
     */
    public static function loadCountry($step1): ?Country
    {
        if (!$step1) {
            return null;
        }

        // Try by ID first (from map selection)
        if (!empty($step1->country_id)) {
            $country = Country::find($step1->country_id);
            if ($country) {
                return $country;
            }
        }

        // Fallback to name (legacy)
        if (!empty($step1->customer_country)) {
            return Country::where('country_name', $step1->customer_country)->first();
        }

        return null;
    }

    /**
     * Group products by vendor ID
     *
     * @param array $products
     * @return array
     */
    public static function groupProductsByVendor(array $products): array
    {
        $result = [];

        foreach ($products as $key => $item) {
            $userId = $item['user_id'] ?? 0;
            if (!isset($result[$userId])) {
                $result[$userId] = [];
            }
            $result[$userId][$key] = $item;
        }

        return $result;
    }

    /**
     * Calculate vendor products total
     *
     * @param array $vendorProducts
     * @return float
     */
    public static function calculateVendorTotal(array $vendorProducts): float
    {
        $total = 0;
        foreach ($vendorProducts as $product) {
            $total += $product['price'] ?? 0;
        }
        return $total;
    }
}
