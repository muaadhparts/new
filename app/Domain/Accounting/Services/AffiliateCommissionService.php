<?php

namespace App\Domain\Accounting\Services;

use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * AffiliateCommissionService
 *
 * Handles affiliate commission calculations for cart items.
 * Tracks referral commissions for affiliate users.
 */
class AffiliateCommissionService
{
    /**
     * Calculate affiliate commissions for cart items
     *
     * @param array $cartItems Cart items from MerchantCartManager->getItems()
     * @return array|null Array of affiliate data or null if no affiliates
     * @throws \InvalidArgumentException If cart items format is invalid
     */
    public function calculateCommissions(array $cartItems): ?array
    {
        $this->validateCartItems($cartItems);

        $affiliateData = [];
        $percentage = $this->getAffiliatePercentage();

        foreach ($cartItems as $key => $cartItem) {
            $affiliateUserId = $cartItem['affiliate_user_id'] ?? 0;

            if ($affiliateUserId > 0 && $this->isValidAffiliate($affiliateUserId)) {
                $affiliateData[] = [
                    'user_id' => $affiliateUserId,
                    'catalog_item_id' => $cartItem['catalog_item_id'],
                    'charge' => $cartItem['total_price'] * $percentage,
                    'item_key' => $key,
                ];
            }
        }

        return empty($affiliateData) ? null : $affiliateData;
    }

    /**
     * Check if user is a valid affiliate (not the same as current user)
     */
    private function isValidAffiliate(int $userId): bool
    {
        if (!Auth::check()) {
            return true;
        }

        return Auth::id() !== $userId;
    }

    /**
     * Get affiliate commission percentage
     */
    private function getAffiliatePercentage(): float
    {
        $charge = setting('affilate_charge', 0);
        return (float) $charge / 100;
    }

    /**
     * Validate cart items format
     */
    private function validateCartItems(array $cartItems): void
    {
        foreach ($cartItems as $key => $cartItem) {
            if (!isset($cartItem['catalog_item_id'])) {
                throw new \InvalidArgumentException(
                    "Cart item '{$key}' missing required field: catalog_item_id"
                );
            }

            if (!isset($cartItem['total_price'])) {
                throw new \InvalidArgumentException(
                    "Cart item '{$key}' missing required field: total_price"
                );
            }
        }
    }

    /**
     * Get affiliate user by ID
     *
     * @param int $userId
     * @return User|null
     */
    public function getAffiliateUser(int $userId): ?User
    {
        if ($userId <= 0) {
            return null;
        }

        return User::find($userId);
    }

    /**
     * Check if affiliate system is enabled
     */
    public function isAffiliateEnabled(): bool
    {
        return platformSettings()->get('is_affilate', 0) == 1;
    }

    /**
     * Calculate total affiliate commission for a cart
     */
    public function calculateTotalCommission(array $cartItems): float
    {
        $commissions = $this->calculateCommissions($cartItems);

        if (!$commissions) {
            return 0.0;
        }

        return array_sum(array_column($commissions, 'charge'));
    }

    /**
     * Record affiliate commission after purchase completion
     *
     * @param array $affiliateData From calculateCommissions()
     * @param string $purchaseNumber
     */
    public function recordCommissions(array $affiliateData, string $purchaseNumber): void
    {
        foreach ($affiliateData as $data) {
            try {
                // Record commission in referral_commissions table
                \App\Domain\Accounting\Models\ReferralCommission::create([
                    'user_id' => $data['user_id'],
                    'catalog_item_id' => $data['catalog_item_id'],
                    'commission' => $data['charge'],
                    'purchase_number' => $purchaseNumber,
                    'status' => 'pending',
                ]);

                Log::info('Affiliate commission recorded', [
                    'user_id' => $data['user_id'],
                    'catalog_item_id' => $data['catalog_item_id'],
                    'commission' => $data['charge'],
                    'purchase_number' => $purchaseNumber,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to record affiliate commission', [
                    'user_id' => $data['user_id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
