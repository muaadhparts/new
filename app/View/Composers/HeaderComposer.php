<?php

namespace App\View\Composers;

use App\Models\Favorite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * HeaderComposer
 *
 * Provides pre-loaded user data for header-related views.
 * Uses lightweight cached favorite COUNT query (not full IDs).
 *
 * NOTE: This is separate from CatalogItemCardDataBuilder.
 * - HeaderComposer: loads favorite COUNT only (for header badge)
 * - CatalogItemCardDataBuilder: loads favorite IDs (for catalog item pages)
 */
class HeaderComposer
{
    public function compose(View $view): void
    {
        $authUser = Auth::guard('web')->check() ? Auth::guard('web')->user() : null;
        $riderUser = Auth::guard('rider')->check() ? Auth::guard('rider')->user() : null;

        $favoriteCount = 0;
        if ($authUser) {
            $favoriteCount = Cache::remember(
                'user_favorite_count_' . $authUser->id,
                300,
                fn() => Favorite::where('user_id', $authUser->id)->count()
            );
        }

        $view->with([
            'authUser' => $authUser,
            'riderUser' => $riderUser,
            'favoriteCount' => $favoriteCount,
        ]);
    }

    /**
     * Invalidate favorite cache when items are added/removed.
     */
    public static function invalidateFavoriteCache(int $userId): void
    {
        Cache::forget('user_favorite_count_' . $userId);
        Cache::forget('user_favorites_' . $userId);
    }
}
