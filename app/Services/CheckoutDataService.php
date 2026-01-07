<?php

namespace App\Services;

use App\Models\Operator;
use App\Models\Country;
use App\Models\Package;
use App\Models\PickupPoint;
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
 * - Before: 3-5 queries per merchant (Shipping, Package, User) = O(n) queries
 * - After: 3 bulk queries total = O(1) queries
 */
class CheckoutDataService
{
    /**
     * Pre-load all merchant data needed for checkout step2
     *
     * @param array $cartItems Cart items array
     * @return array Structured data for all merchants
     */
    public static function loadMerchantData(array $cartItems): array
    {
        // Extract unique merchant IDs from cart items
        $merchantIds = collect($cartItems)
            ->pluck('user_id')
            ->unique()
            ->filter()
            ->values()
            ->toArray();

        // Handle case where merchant_id = 0 (admin items)
        $hasAdminItems = in_array(0, $merchantIds) || empty($merchantIds);

        // Remove 0 from merchant IDs for User query
        $merchantIds = array_filter($merchantIds, fn($id) => $id > 0);

        // Bulk load all data
        $merchants = self::loadMerchants($merchantIds);
        $shippingByMerchant = self::loadShipping($merchantIds, $hasAdminItems);
        $packagingByMerchant = self::loadPackaging($merchantIds);
        $pickupPointsByMerchant = self::loadPickupPoints($merchantIds);
        $operator = $hasAdminItems ? Operator::find(1) : null;

        // Provider labels (static)
        $providerLabels = [
            'manual' => __('Manual Shipping'),
            'debts' => __('Debts Shipping'),
            'tryoto' => __('Smart Shipping (Tryoto)'),
        ];

        // Build result array
        $result = [];

        foreach ($merchantIds as $merchantId) {
            $shipping = $shippingByMerchant[$merchantId] ?? collect();
            $result[$merchantId] = [
                'merchant' => $merchants[$merchantId] ?? null,
                'pickup_point' => $pickupPointsByMerchant[$merchantId] ?? null,
                'shipping' => $shipping,
                'packaging' => $packagingByMerchant[$merchantId] ?? collect(),
                'grouped_shipping' => $shipping->groupBy('provider'),
                'provider_labels' => $providerLabels,
            ];
        }

        // Add operator data if needed
        if ($hasAdminItems) {
            $operatorShipping = $shippingByMerchant[0] ?? collect();
            $result[0] = [
                'merchant' => $operator,
                'shipping' => $operatorShipping,
                'packaging' => collect(), // No global packaging
                'grouped_shipping' => $operatorShipping->groupBy('provider'),
                'provider_labels' => $providerLabels,
            ];
        }

        return $result;
    }

    /**
     * Bulk load merchants by IDs
     */
    private static function loadMerchants(array $merchantIds): Collection
    {
        if (empty($merchantIds)) {
            return collect();
        }

        return User::whereIn('id', $merchantIds)
            ->get()
            ->keyBy('id');
    }

    /**
     * Bulk load shipping for all merchants
     * ✅ FIX: Returns Collection grouped by user_id (not array)
     */
    private static function loadShipping(array $merchantIds, bool $includeAdmin = false): Collection
    {
        $allMerchantIds = $merchantIds;
        if ($includeAdmin) {
            $allMerchantIds[] = 0;
        }

        if (empty($allMerchantIds)) {
            return collect();
        }

        // Load all shipping records for all merchants at once
        $allShipping = Shipping::whereIn('user_id', $allMerchantIds)->get();

        // Group by user_id - returns Collection of Collections
        return $allShipping->groupBy('user_id');
    }

    /**
     * Bulk load packaging for all merchants
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
     * Bulk load active pickup points for all merchants
     * Returns first active pickup point per merchant (keyed by user_id)
     */
    private static function loadPickupPoints(array $merchantIds): Collection
    {
        if (empty($merchantIds)) {
            return collect();
        }

        // Load all active pickup points with city and country
        $allPickupPoints = PickupPoint::whereIn('user_id', $merchantIds)
            ->where('status', 1)
            ->with(['city', 'country'])
            ->get();

        // Get first active pickup point per merchant (keyed by user_id)
        return $allPickupPoints->groupBy('user_id')->map(function ($points) {
            return $points->first();
        });
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
     * Group cart items by merchant ID
     *
     * @param array $cartItems
     * @return array
     */
    public static function groupItemsByMerchant(array $cartItems): array
    {
        $result = [];

        foreach ($cartItems as $key => $item) {
            $userId = $item['user_id'] ?? 0;
            if (!isset($result[$userId])) {
                $result[$userId] = [];
            }
            $result[$userId][$key] = $item;
        }

        return $result;
    }

    /**
     * Calculate merchant items total
     *
     * @param array $merchantItems
     * @return float
     */
    public static function calculateMerchantTotal(array $merchantItems): float
    {
        $total = 0;
        foreach ($merchantItems as $item) {
            $total += $item['price'] ?? 0;
        }
        return $total;
    }
}
