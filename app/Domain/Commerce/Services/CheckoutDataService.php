<?php

namespace App\Domain\Commerce\Services;

use App\Domain\Shipping\Models\Country;
use App\Domain\Merchant\Models\MerchantBranch;
use App\Domain\Shipping\Models\Shipping;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Collection;

/**
 * CheckoutDataService - N+1 Optimized Data Loading for Checkout
 *
 * This service pre-loads all checkout-related data in bulk to avoid
 * N+1 queries inside Blade templates.
 *
 * Performance Impact:
 * - Before: 3-5 queries per merchant (Shipping, User) = O(n) queries
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
        // RULE: Platform NEVER owns items, all items must have user_id > 0
        $merchantIds = collect($cartItems)
            ->pluck('user_id')
            ->unique()
            ->filter(fn($id) => $id > 0) // Only valid merchant IDs
            ->values()
            ->toArray();

        if (empty($merchantIds)) {
            return [];
        }

        // Bulk load all data
        $merchants = self::loadMerchants($merchantIds);
        $shippingByMerchant = self::loadShipping($merchantIds);
        $merchantBranches = self::loadMerchantBranches($merchantIds);

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
                'merchant_branch' => $merchantBranches[$merchantId] ?? null,
                'shipping' => $shipping,
                'grouped_shipping' => $shipping->groupBy('provider'),
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
     *
     * المنطق:
     * | user_id | operator    | المعنى                                    |
     * |---------|-------------|-------------------------------------------|
     * | 0       | 0           | موقف/معطّل - لا يظهر                      |
     * | 0       | merchant_id | شحنة المنصة مُفعّلة لتاجر معين             |
     * | merchant_id | 0       | شحنة خاصة بالتاجر                         |
     *
     * @return Collection grouped by effective merchant_id
     */
    private static function loadShipping(array $merchantIds): Collection
    {
        if (empty($merchantIds)) {
            return collect();
        }

        // Load shipping records:
        // 1. Merchant's own shipping (user_id = merchantId)
        // 2. Platform shipping enabled for specific merchants (user_id = 0 AND operator IN merchantIds)
        $allShipping = Shipping::where('status', 1)
            ->where(function ($q) use ($merchantIds) {
                // شحنات التجار الخاصة
                $q->whereIn('user_id', $merchantIds)
                  // أو شحنات المنصة المُفعّلة لهؤلاء التجار
                  ->orWhere(function ($q2) use ($merchantIds) {
                      $q2->where('user_id', 0)
                         ->whereIn('operator', $merchantIds);
                  });
            })
            ->get();

        // Group by effective merchant_id:
        // - If user_id > 0 → group by user_id (merchant's own)
        // - If user_id = 0 → group by operator (platform's for specific merchant)
        $grouped = collect();
        foreach ($allShipping as $shipping) {
            $effectiveMerchantId = $shipping->user_id > 0 ? $shipping->user_id : $shipping->operator;
            if (!$grouped->has($effectiveMerchantId)) {
                $grouped[$effectiveMerchantId] = collect();
            }
            $grouped[$effectiveMerchantId]->push($shipping);
        }

        return $grouped;
    }

    /**
     * Bulk load active merchant branches (warehouses) for all merchants
     * Returns first active branch per merchant (keyed by user_id)
     */
    private static function loadMerchantBranches(array $merchantIds): Collection
    {
        if (empty($merchantIds)) {
            return collect();
        }

        // Load all active merchant branches with city and country
        $allBranches = MerchantBranch::whereIn('user_id', $merchantIds)
            ->where('status', 1)
            ->with(['city', 'country'])
            ->get();

        // Get first active branch per merchant (keyed by user_id)
        return $allBranches->groupBy('user_id')->map(function ($branches) {
            return $branches->first();
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
