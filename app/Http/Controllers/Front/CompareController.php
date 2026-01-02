<?php

namespace App\Http\Controllers\Front;

use App\Models\Compare;
use App\Models\CatalogItem;
use App\Models\MerchantItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CompareController extends FrontBaseController
{
    /**
     * Display the compare page with items.
     */
    public function compare()
    {
        if (!Session::has('compare')) {
            return view('frontend.compare');
        }

        $oldCompare = Session::get('compare');
        $compare = new Compare($oldCompare);
        $compareItems = $compare->getItemsWithProducts();

        // Note: 'products' kept for backward compatibility in views
        return view('frontend.compare', ['products' => $compareItems]);
    }

    /**
     * Add merchant item to comparison (New standardized method).
     */
    public function addMerchantCompare($merchantProductId)
    {
        return $this->addcompare($merchantProductId);
    }

    /**
     * Remove merchant item from comparison (New standardized method).
     */
    public function removeMerchantCompare(Request $request, $merchantProductId)
    {
        $oldCompare = Session::has('compare') ? Session::get('compare') : null;

        if (!$oldCompare || !isset($oldCompare->items[$merchantProductId])) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'error' => __('Item not found in compare list.')
                ]);
            }
            return back()->with('unsuccess', __('Item not found in compare list.'));
        }

        $compare = new Compare($oldCompare);
        $compare->removeItem($merchantProductId);

        if (count($compare->items) > 0) {
            Session::put('compare', $compare);
        } else {
            Session::forget('compare');
        }

        // Return JSON for Ajax requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'compare_count' => count($compare->items),
                'success' => __('Successfully Removed From Compare.')
            ]);
        }

        // Return redirect for normal requests
        return back()->with('success', __('Successfully Removed From Compare.'));
    }

    /**
     * Add merchant item to comparison.
     * Expects merchant_product_id (merchant_item_id) as parameter.
     */
    public function addcompare($merchantProductId)
    {
        $data[0] = 0;
        $merchantItem = MerchantItem::with(['catalogItem', 'user', 'qualityBrand'])->findOrFail($merchantProductId);
        $oldCompare = Session::has('compare') ? Session::get('compare') : null;

        $compare = new Compare($oldCompare);
        $compare->add($merchantItem, $merchantProductId);
        Session::put('compare', $compare);

        if ($compare->items[$merchantProductId]['ck'] == 1) {
            $data[0] = 1;
        }

        $data[1] = count($compare->items);
        $data['success'] = __('Successfully Added To Compare.');
        $data['error'] = __('Already Added To Compare.');
        return response()->json($data);
    }

    /**
     * Legacy method for backward compatibility.
     * Converts catalog_item_id to merchant_item_id.
     * Can optionally accept user parameter to specify merchant.
     */
    public function addcompareLegacy(Request $request, $catalogItemId)
    {
        $data[0] = 0;
        $catalogItem = CatalogItem::findOrFail($catalogItemId);
        $oldCompare = Session::has('compare') ? Session::get('compare') : null;

        $userId = $request->get('user');

        // If user parameter is provided, find specific merchant item for that merchant
        if ($userId) {
            $merchantItem = MerchantItem::with(['catalogItem', 'user', 'qualityBrand'])
                ->where('catalog_item_id', $catalogItemId)
                ->where('user_id', $userId)
                ->where('status', 1)
                ->first();
        } else {
            // Fallback: find the first active merchant item
            $merchantItem = MerchantItem::with(['catalogItem', 'user', 'qualityBrand'])
                ->where('catalog_item_id', $catalogItemId)
                ->where('status', 1)
                ->orderBy('price')
                ->first();
        }

        if (!$merchantItem) {
            $data[0] = 1;
            $data['error'] = __('Product not available from any merchant.');
            return response()->json($data);
        }

        $compare = new Compare($oldCompare);
        $compare->add($merchantItem, $merchantItem->id);
        Session::put('compare', $compare);

        if ($compare->items[$merchantItem->id]['ck'] == 1) {
            $data[0] = 1;
        }

        $data[1] = count($compare->items);
        $data['success'] = __('Successfully Added To Compare.');
        $data['error'] = __('Already Added To Compare.');
        return response()->json($data);
    }

    public function removecompare(Request $request, $merchantProductId)
    {
        $oldCompare = Session::has('compare') ? Session::get('compare') : null;
        $isAjax = $request->expectsJson() || $request->ajax();

        // Check if compare session exists
        if (!$oldCompare || !$oldCompare->items) {
            if ($isAjax) {
                return response()->json([
                    'ok' => false,
                    'error' => __('Compare list is empty.')
                ]);
            }
            return back()->with('unsuccess', __('Compare list is empty.'));
        }

        // Check if item exists in compare list (handle both string and integer keys)
        $itemExists = isset($oldCompare->items[$merchantProductId]) ||
                      isset($oldCompare->items[(int)$merchantProductId]) ||
                      isset($oldCompare->items[(string)$merchantProductId]);

        if (!$itemExists) {
            if ($isAjax) {
                return response()->json([
                    'ok' => false,
                    'error' => __('Item not found in compare list.')
                ]);
            }
            return back()->with('unsuccess', __('Item not found in compare list.'));
        }

        $compare = new Compare($oldCompare);
        $compare->removeItem($merchantProductId);
        // Also try removing with type casting
        $compare->removeItem((int)$merchantProductId);
        $compare->removeItem((string)$merchantProductId);

        $remainingCount = $compare->items ? count($compare->items) : 0;

        if ($remainingCount > 0) {
            Session::put('compare', $compare);
        } else {
            Session::forget('compare');
        }

        if ($isAjax) {
            return response()->json([
                'ok' => true,
                'success' => __('Successfully Removed From Compare.'),
                'compare_count' => $remainingCount
            ]);
        }

        return back()->with('success', $remainingCount > 0 ? __('Successfully Removed From Compare.') : __('Compare List Cleared.'));
    }

    public function clearcompare()
    {
        Session::forget('compare');
        return back()->with('success', __('Compare List Cleared.'));
    }
}
