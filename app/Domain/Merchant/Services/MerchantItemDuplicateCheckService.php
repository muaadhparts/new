<?php

namespace App\Domain\Merchant\Services;

use App\Domain\Merchant\Models\MerchantItem;

/**
 * Service for checking merchant item duplicates.
 * Centralizes duplicate detection logic for merchant items.
 */
class MerchantItemDuplicateCheckService
{
    /**
     * Check if merchant has any offer for a catalog item.
     *
     * @param int $merchantId
     * @param int $catalogItemId
     * @return MerchantItem|null Returns existing item if found
     */
    public function findExistingOffer(int $merchantId, int $catalogItemId): ?MerchantItem
    {
        return MerchantItem::where('catalog_item_id', $catalogItemId)
            ->where('user_id', $merchantId)
            ->first();
    }

    /**
     * Check if merchant has an offer for catalog item in specific branch.
     *
     * @param int $merchantId
     * @param int $catalogItemId
     * @param int $branchId
     * @return MerchantItem|null Returns existing item if found
     */
    public function findExistingOfferInBranch(int $merchantId, int $catalogItemId, int $branchId): ?MerchantItem
    {
        return MerchantItem::where('catalog_item_id', $catalogItemId)
            ->where('user_id', $merchantId)
            ->where('merchant_branch_id', $branchId)
            ->first();
    }

    /**
     * Check if merchant has duplicate offer (same item + branch + quality brand).
     * Used during updates to ensure no conflicting offers.
     *
     * @param int $merchantId
     * @param int $catalogItemId
     * @param int $branchId
     * @param int $qualityBrandId
     * @param int|null $excludeItemId Item ID to exclude from check (for updates)
     * @return bool
     */
    public function hasConflictingOffer(
        int $merchantId,
        int $catalogItemId,
        int $branchId,
        int $qualityBrandId,
        ?int $excludeItemId = null
    ): bool {
        $query = MerchantItem::where('catalog_item_id', $catalogItemId)
            ->where('user_id', $merchantId)
            ->where('merchant_branch_id', $branchId)
            ->where('quality_brand_id', $qualityBrandId);

        if ($excludeItemId) {
            $query->where('id', '<>', $excludeItemId);
        }

        return $query->exists();
    }

    /**
     * Check if it's safe to create a new merchant item.
     *
     * @param int $merchantId
     * @param int $catalogItemId
     * @param int $branchId
     * @return array ['can_create' => bool, 'existing_item' => MerchantItem|null, 'message' => string|null]
     */
    public function canCreate(int $merchantId, int $catalogItemId, int $branchId): array
    {
        $existing = $this->findExistingOfferInBranch($merchantId, $catalogItemId, $branchId);

        if ($existing) {
            return [
                'can_create' => false,
                'existing_item' => $existing,
                'message' => __('You already have an offer for this catalog item in this branch.'),
            ];
        }

        return [
            'can_create' => true,
            'existing_item' => null,
            'message' => null,
        ];
    }

    /**
     * Check if it's safe to update a merchant item.
     *
     * @param int $merchantItemId
     * @param int $merchantId
     * @param int $catalogItemId
     * @param int $branchId
     * @param int $qualityBrandId
     * @return array ['can_update' => bool, 'message' => string|null]
     */
    public function canUpdate(
        int $merchantItemId,
        int $merchantId,
        int $catalogItemId,
        int $branchId,
        int $qualityBrandId
    ): array {
        $hasConflict = $this->hasConflictingOffer(
            $merchantId,
            $catalogItemId,
            $branchId,
            $qualityBrandId,
            $merchantItemId
        );

        if ($hasConflict) {
            return [
                'can_update' => false,
                'message' => __('You already have an offer for this catalog item in this branch with this quality brand.'),
            ];
        }

        return [
            'can_update' => true,
            'message' => null,
        ];
    }
}
