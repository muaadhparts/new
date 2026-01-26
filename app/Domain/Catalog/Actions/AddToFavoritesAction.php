<?php

namespace App\Domain\Catalog\Actions;

use App\Domain\Commerce\Models\FavoriteSeller;
use App\Domain\Merchant\Models\MerchantItem;
use Illuminate\Support\Facades\Auth;

/**
 * AddToFavoritesAction - Add item to favorites
 *
 * Single-responsibility action for adding items to user favorites.
 */
class AddToFavoritesAction
{
    /**
     * Execute the action
     *
     * @param int $merchantItemId Merchant item ID
     * @param int|null $userId User ID (defaults to current user)
     * @return array{success: bool, message: string}
     */
    public function execute(int $merchantItemId, ?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();

        if (!$userId) {
            return [
                'success' => false,
                'message' => __('Please login to add favorites'),
            ];
        }

        $merchantItem = MerchantItem::find($merchantItemId);

        if (!$merchantItem) {
            return [
                'success' => false,
                'message' => __('Item not found'),
            ];
        }

        // Check if already in favorites
        $exists = FavoriteSeller::where('user_id', $userId)
            ->where('merchant_item_id', $merchantItemId)
            ->exists();

        if ($exists) {
            return [
                'success' => false,
                'message' => __('Item already in favorites'),
            ];
        }

        FavoriteSeller::create([
            'user_id' => $userId,
            'catalog_item_id' => $merchantItem->catalog_item_id,
            'merchant_item_id' => $merchantItemId,
        ]);

        return [
            'success' => true,
            'message' => __('Added to favorites'),
        ];
    }

    /**
     * Toggle favorite status
     *
     * @param int $merchantItemId
     * @param int|null $userId
     * @return array
     */
    public function toggle(int $merchantItemId, ?int $userId = null): array
    {
        $userId = $userId ?? Auth::id();

        if (!$userId) {
            return [
                'success' => false,
                'message' => __('Please login to manage favorites'),
            ];
        }

        $favorite = FavoriteSeller::where('user_id', $userId)
            ->where('merchant_item_id', $merchantItemId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return [
                'success' => true,
                'message' => __('Removed from favorites'),
                'is_favorite' => false,
            ];
        }

        $merchantItem = MerchantItem::find($merchantItemId);

        if (!$merchantItem) {
            return [
                'success' => false,
                'message' => __('Item not found'),
            ];
        }

        FavoriteSeller::create([
            'user_id' => $userId,
            'catalog_item_id' => $merchantItem->catalog_item_id,
            'merchant_item_id' => $merchantItemId,
        ]);

        return [
            'success' => true,
            'message' => __('Added to favorites'),
            'is_favorite' => true,
        ];
    }
}
