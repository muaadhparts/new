<?php

namespace App\Domain\Merchant\ViewComposers;

use App\Domain\Catalog\Models\UserCatalogEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Merchant Header Composer
 *
 * Provides header data (notifications) to merchant views.
 * This follows the "Blade Display Only" rule - no queries in views.
 */
class MerchantHeaderComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $merchantId = Auth::id();

        if (!$merchantId) {
            $view->with('merchantNotifications', collect());
            return;
        }

        // Cached notifications (5 min cache, max 20 items)
        $notifications = Cache::remember(
            'merchant_events_' . $merchantId,
            300,
            fn() => UserCatalogEvent::where('user_id', $merchantId)
                ->orderBy('id', 'desc')
                ->limit(20)
                ->get()
        );

        $view->with('merchantNotifications', $notifications);
    }
}
