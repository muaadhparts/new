<?php

namespace App\Http\Controllers\Merchant;

use App\Models\UserCatalogEvent;

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
        return back()->with("success", "Events cleared successfully");
    }

    /**
     * Show all events for merchant
     */
    public function showPurchaseEvents($id)
    {
        $datas = UserCatalogEvent::where('user_id', '=', $id)->get();
        foreach ($datas as $data) {
            $data->is_read = 1;
            $data->save();
        }
        return view('merchant.catalog-event.purchase', compact('datas'));
    }
}
