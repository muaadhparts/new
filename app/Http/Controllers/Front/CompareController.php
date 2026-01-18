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
        $compareItems = $compare->getItemsWithCatalogItems();

        // Note: 'catalogItems' kept for backward compatibility in views
        return view('frontend.compare', ['catalogItems' => $compareItems]);
    }

    /**
     * Add merchant item to comparison (New standardized method).
     */
    public function addMerchantCompare($merchantItemId)
    {
        return $this->addcompare($merchantItemId);
    }

    /**
     * Remove merchant item from comparison (New standardized method).
     */
    public function removeMerchantCompare(Request $request, $merchantItemId)
    {
        $oldCompare = Session::has('compare') ? Session::get('compare') : null;

        if (!$oldCompare || !isset($oldCompare->items[$merchantItemId])) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'error' => __('Item not found in compare list.')
                ]);
            }
            return back()->with('unsuccess', __('Item not found in compare list.'));
        }

        $compare = new Compare($oldCompare);
        $compare->removeItem($merchantItemId);

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
     * Expects merchant_item_id as parameter.
     */
    public function addcompare($merchantItemId)
    {
        $data[0] = 0;
        $merchantItem = MerchantItem::with(['catalogItem', 'user', 'qualityBrand'])->findOrFail($merchantItemId);
        $oldCompare = Session::has('compare') ? Session::get('compare') : null;

        $compare = new Compare($oldCompare);
        $compare->add($merchantItem, $merchantItemId);
        Session::put('compare', $compare);

        if ($compare->items[$merchantItemId]['ck'] == 1) {
            $data[0] = 1;
        }

        $data[1] = count($compare->items);
        $data['success'] = __('Successfully Added To Compare.');
        $data['error'] = __('Already Added To Compare.');
        return response()->json($data);
    }

    public function removecompare(Request $request, $merchantItemId)
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
        $itemExists = isset($oldCompare->items[$merchantItemId]) ||
                      isset($oldCompare->items[(int)$merchantItemId]) ||
                      isset($oldCompare->items[(string)$merchantItemId]);

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
        $compare->removeItem($merchantItemId);
        // Also try removing with type casting
        $compare->removeItem((int)$merchantItemId);
        $compare->removeItem((string)$merchantItemId);

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
