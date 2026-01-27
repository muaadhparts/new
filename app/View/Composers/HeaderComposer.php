<?php

namespace App\View\Composers;

use App\Domain\Commerce\Models\FavoriteSeller;
use App\Domain\Commerce\Services\Cart\MerchantCartManager;
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
    public function __construct(
        private MerchantCartManager $cartManager
    ) {}

    public function compose(View $view): void
    {
        $authUser = Auth::guard('web')->check() ? Auth::guard('web')->user() : null;
        $courierUser = Auth::guard('courier')->check() ? Auth::guard('courier')->user() : null;

        $favoriteCount = 0;
        if ($authUser) {
            $favoriteCount = Cache::remember(
                'user_favorite_count_' . $authUser->id,
                300,
                fn() => FavoriteSeller::where('user_id', $authUser->id)->count()
            );
        }

        // Cart count from MerchantCartManager (Single Source of Truth)
        $merchantCartCount = $this->cartManager->getHeaderCount();

        // Cart data for header dropdown (DATA_FLOW_POLICY - pre-computed)
        $headerCartData = $this->cartManager->getHeaderCartData();

        // PRE-COMPUTED: Dashboard detection (DATA_FLOW_POLICY - no @php in header)
        $isUserDashboard = request()->routeIs('user-*') || request()->is('user/*');
        $isCourierDashboard = request()->routeIs('courier.*') || request()->is('courier/*');
        $isDashboardPage = $isUserDashboard || $isCourierDashboard;

        // PRE-COMPUTED: Current brand/catalog from URL (DATA_FLOW_POLICY - no @php in mobile_menu)
        $currentBrandSlug = request()->segment(2);
        $currentCatalogSlug = request()->segment(3);

        $view->with([
            'authUser' => $authUser,
            'courierUser' => $courierUser,
            'favoriteCount' => $favoriteCount,
            'merchantCartCount' => $merchantCartCount,
            'headerCartData' => $headerCartData,
            // Dashboard detection
            'isUserDashboard' => $isUserDashboard,
            'isCourierDashboard' => $isCourierDashboard,
            'isDashboardPage' => $isDashboardPage,
            // URL segments for category selector
            'currentBrandSlug' => $currentBrandSlug,
            'currentCatalogSlug' => $currentCatalogSlug,
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
