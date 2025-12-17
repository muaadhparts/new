<?php

namespace App\View\Composers;

use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * HeaderComposer
 *
 * Provides pre-loaded user data for header-related views.
 * Uses lightweight cached wishlist COUNT query (not full IDs).
 *
 * NOTE: This is separate from ProductCardDataBuilder.
 * - HeaderComposer: loads wishlist COUNT only (for header badge)
 * - ProductCardDataBuilder: loads wishlist IDs (for product pages)
 */
class HeaderComposer
{
    public function compose(View $view): void
    {
        // Get authenticated user (Laravel caches this per-request)
        $authUser = Auth::guard('web')->check() ? Auth::guard('web')->user() : null;
        $riderUser = Auth::guard('rider')->check() ? Auth::guard('rider')->user() : null;

        // Get wishlist count from lightweight cached query
        $wishlistCount = 0;
        if ($authUser) {
            $wishlistCount = Cache::remember(
                'user_wishlist_count_' . $authUser->id,
                300, // 5 minutes
                fn() => Wishlist::where('user_id', $authUser->id)->count()
            );
        }

        $view->with([
            'authUser' => $authUser,
            'riderUser' => $riderUser,
            'wishlistCount' => $wishlistCount,
        ]);
    }

    /**
     * Invalidate wishlist cache when items are added/removed.
     */
    public static function invalidateWishlistCache(int $userId): void
    {
        Cache::forget('user_wishlist_count_' . $userId);
        Cache::forget('user_wishlists_' . $userId);
    }
}
