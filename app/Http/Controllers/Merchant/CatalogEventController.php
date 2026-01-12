<?php

namespace App\Http\Controllers\Merchant;

use App\Models\UserCatalogEvent;
use Illuminate\Support\Facades\Cache;

class CatalogEventController extends MerchantBaseController
{
    /**
     * Count unread events for merchant
     */
    public function countPurchaseEvents($id)
    {
        $data = UserCatalogEvent::where('user_id', '=', $id)
            ->where('is_read', '=', 0)
            ->count();
        return response()->json($data);
    }

    /**
     * Clear all events for merchant
     */
    public function clearPurchaseEvents($id)
    {
        UserCatalogEvent::where('user_id', '=', $id)->delete();

        // Clear the cached events for this merchant
        Cache::forget('merchant_events_' . $id);

        return back()->with("success", "Events cleared successfully");
    }

    /**
     * Show all events for merchant
     */
    public function showPurchaseEvents($id)
    {
        $datas = UserCatalogEvent::where('user_id', '=', $id)->get();

        // OPTIMIZED: Use single update query instead of N queries in loop
        UserCatalogEvent::where('user_id', '=', $id)
            ->where('is_read', '=', 0)
            ->update(['is_read' => 1]);

        // Clear the cached events for this merchant
        Cache::forget('merchant_events_' . $id);

        return view('merchant.catalog-event.purchase', compact('datas'));
    }
}
