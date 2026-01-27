<?php

namespace App\Http\Controllers\Operator;

use App\Domain\Catalog\Models\CatalogEvent;

class CatalogEventController extends OperatorBaseController
{
    /**
     * Get all event counts for dashboard
     */
    public function allEventCount()
    {
        $user_count = CatalogEvent::countRegistration();
        $purchase_count = CatalogEvent::countPurchase();
        $product_count = CatalogEvent::countCatalogItem();
        $conv_count = CatalogEvent::countChatThread();

        return response()->json([
            'user_count' => $user_count,
            'conv_count' => $conv_count,
            'order_count' => $purchase_count,
            'product_count' => $product_count,
        ]);
    }

    /**
     * Clear user registration events
     */
    public function clearUserEvents()
    {
        CatalogEvent::whereNotNull('user_id')->delete();
    }

    /**
     * Show user registration events
     */
    public function showUserEvents()
    {
        $datas = CatalogEvent::whereNotNull('user_id')->latest('id')->get();
        foreach ($datas as $data) {
            $data->is_read = 1;
            $data->save();
        }
        return view('operator.catalog-event.register', compact('datas'));
    }

    /**
     * Clear purchase events
     */
    public function clearPurchaseEvents()
    {
        CatalogEvent::whereNotNull('purchase_id')->delete();
    }

    /**
     * Show purchase events
     */
    public function showPurchaseEvents()
    {
        $datas = CatalogEvent::whereNotNull('purchase_id')->latest('id')->get();
        foreach ($datas as $data) {
            $data->is_read = 1;
            $data->save();
        }
        return view('operator.catalog-event.purchase', compact('datas'));
    }

    /**
     * Clear catalog item events
     */
    public function clearCatalogItemEvents()
    {
        CatalogEvent::whereNotNull('catalog_item_id')->delete();
    }

    /**
     * Show catalog item events
     */
    public function showCatalogItemEvents()
    {
        $datas = CatalogEvent::whereNotNull('catalog_item_id')
            ->with(['catalogItem.merchantItems' => function ($query) {
                $query->where('status', 1);
            }])
            ->latest('id')
            ->get();

        // Pre-compute stock totals
        foreach ($datas as $data) {
            $data->is_read = 1;
            $data->save();

            // Calculate total stock from active merchant items
            if ($data->catalogItem) {
                $data->total_stock = $data->catalogItem->merchantItems->sum('stock');
            } else {
                $data->total_stock = 0;
            }
        }

        return view('operator.catalog-event.catalogItem', compact('datas'));
    }

    /**
     * Clear conversation events
     */
    public function clearConversationEvents()
    {
        CatalogEvent::whereNotNull('conversation_id')->delete();
    }

    /**
     * Show conversation events
     */
    public function showConversationEvents()
    {
        $datas = CatalogEvent::whereNotNull('conversation_id')->latest('id')->get();
        foreach ($datas as $data) {
            $data->is_read = 1;
            $data->save();
        }
        return view('operator.catalog-event.message', compact('datas'));
    }
}
