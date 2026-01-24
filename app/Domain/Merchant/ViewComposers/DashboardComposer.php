<?php

namespace App\Domain\Merchant\ViewComposers;

use Illuminate\View\View;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Accounting\Models\AccountBalance;
use Illuminate\Support\Facades\Auth;

/**
 * Merchant Dashboard Composer
 *
 * Provides dashboard statistics to merchant views.
 */
class DashboardComposer
{
    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $merchantId = Auth::id();

        if (!$merchantId) {
            return;
        }

        $stats = [
            'pendingOrders' => MerchantPurchase::where('user_id', $merchantId)
                ->where('status', 'pending')
                ->count(),
            'todayOrders' => MerchantPurchase::where('user_id', $merchantId)
                ->whereDate('created_at', today())
                ->count(),
            'lowStockItems' => MerchantItem::where('user_id', $merchantId)
                ->where('status', 1)
                ->where('stock', '>', 0)
                ->where('stock', '<=', 5)
                ->count(),
            'outOfStockItems' => MerchantItem::where('user_id', $merchantId)
                ->where('status', 1)
                ->where('stock', '<=', 0)
                ->count(),
            'balance' => AccountBalance::where('user_id', $merchantId)
                ->value('current_balance') ?? 0,
        ];

        $view->with('merchantStats', $stats);
    }
}
