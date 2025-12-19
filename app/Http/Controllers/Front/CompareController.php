<?php

namespace App\Http\Controllers\Front;

use App\Models\Compare;
use App\Models\Product;
use App\Models\MerchantProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CompareController extends FrontBaseController
{
    public function compare()
    {
        if (!Session::has('compare')) {
            return view('frontend.compare');
        }

        $oldCompare = Session::get('compare');
        $compare = new Compare($oldCompare);
        $products = $compare->getItemsWithProducts();

        return view('frontend.compare', compact('products'));
    }

    /**
     * Add merchant product to comparison (New standardized method)
     */
    public function addMerchantCompare($merchantProductId)
    {
        return $this->addcompare($merchantProductId);
    }

    /**
     * Remove merchant product from comparison (New standardized method)
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
     * Add merchant product to comparison
     * Expects merchant_product_id as parameter
     */
    public function addcompare($merchantProductId)
    {
        $data[0] = 0;
        $merchantProduct = MerchantProduct::with(['product', 'user', 'qualityBrand'])->findOrFail($merchantProductId);
        $oldCompare = Session::has('compare') ? Session::get('compare') : null;

        $compare = new Compare($oldCompare);
        $compare->add($merchantProduct, $merchantProductId);
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
     * Legacy method for backward compatibility
     * Converts product_id to merchant_product_id
     * Can optionally accept user parameter to specify vendor
     */
    public function addcompareLegacy(Request $request, $productId)
    {
        $data[0] = 0;
        $product = Product::findOrFail($productId);
        $oldCompare = Session::has('compare') ? Session::get('compare') : null;

        $userId = $request->get('user');

        // If user parameter is provided, find specific merchant product for that vendor
        if ($userId) {
            $merchantProduct = MerchantProduct::with(['product', 'user', 'qualityBrand'])
                ->where('product_id', $productId)
                ->where('user_id', $userId)
                ->where('status', 1)
                ->first();
        } else {
            // Fallback: find the first active merchant product
            $merchantProduct = MerchantProduct::with(['product', 'user', 'qualityBrand'])
                ->where('product_id', $productId)
                ->where('status', 1)
                ->orderBy('price')
                ->first();
        }

        if (!$merchantProduct) {
            $data[0] = 1;
            $data['error'] = __('Product not available from any vendor.');
            return response()->json($data);
        }

        $compare = new Compare($oldCompare);
        $compare->add($merchantProduct, $merchantProduct->id);
        Session::put('compare', $compare);

        if ($compare->items[$merchantProduct->id]['ck'] == 1) {
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
