<?php

namespace App\Http\Controllers\Front;

use App\Models\Compare;
use App\Models\Product;
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
        $products = $compare->items;
        return view('frontend.compare', compact('products'));
    }

    public function addcompare($id)
    {
        $data[0] = 0;
        $prod = Product::findOrFail($id);
        $oldCompare = Session::has('compare') ? Session::get('compare') : null;

        if ($oldCompare && count($oldCompare->items) == 3) {
            $data[0] = 1;
            $data['error'] = __('You can compare maximum 3 products.');
            return response()->json($data);
        }

        $compare = new Compare($oldCompare);
        $compare->add($prod, $prod->id);
        Session::put('compare', $compare);
        if ($compare->items[$id]['ck'] == 1) {
            $data[0] = 1;
        }
        $data[1] = count($compare->items);
        $data['success'] = __('Successfully Added To Compare.');
        $data['error'] = __('Already Added To Compare.');
        return response()->json($data);
    }

    public function removecompare($id)
    {
        $data[0] = 0;
        $oldCompare = Session::has('compare') ? Session::get('compare') : null;
        $compare = new Compare($oldCompare);
        $compare->removeItem($id);

        if (count($compare->items) > 0) {
            Session::put('compare', $compare);
            return back()->with('success', __('Successfully Removed From Compare.'));
        } else {
            Session::forget('compare');
            return back()->with('error', __('No Product Found In Compare.'));
        }
    }

    public function clearcompare($id)
    {
        Session::forget('compare');
    }
}
