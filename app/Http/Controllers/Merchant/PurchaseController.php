<?php

namespace App\Http\Controllers\Merchant;

use App\Models\Purchase;
use App\Models\MerchantPurchase;
use Illuminate\Http\Request;

class PurchaseController extends MerchantBaseController
{

    public function index()
    {
        $user = $this->user;
        $purchases = Purchase::with(array('merchantPurchases' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }))->orderby('id', 'desc')->get()->reject(function ($item) use ($user) {
            if ($item->merchantPurchases()->where('user_id', '=', $user->id)->count() == 0) {
                return true;
            }
            return false;
        })->paginate(3);

        return view('merchant.purchase.index', compact('purchases', 'user'));
    }

    public function show($slug)
    {
        $user = $this->user;
        $purchase = Purchase::where('purchase_number', '=', $slug)->first();

        // Security: Verify vendor has items in this purchase
        if (!$purchase || !$purchase->merchantPurchases()->where('user_id', $user->id)->exists()) {
            abort(403, 'Unauthorized access to purchase');
        }

        $cart = json_decode($purchase->cart, true);
        return view('merchant.purchase.details', compact('user', 'purchase', 'cart'));
    }

    public function license(Request $request, $slug)
    {
        $user = $this->user;
        $purchase = Purchase::where('purchase_number', '=', $slug)->first();

        // Security: Verify vendor has items in this purchase
        if (!$purchase || !$purchase->merchantPurchases()->where('user_id', $user->id)->exists()) {
            abort(403, 'Unauthorized access to purchase');
        }

        $cart = json_decode($purchase->cart, true);
        $cart['items'][$request->license_key]['license'] = $request->license;
        $new_cart = json_encode($cart);
        $purchase->cart = $new_cart;
        $purchase->update();
        $msg = __('Successfully Changed The License Key.');
        return redirect()->back()->with('license', $msg);
    }

    public function invoice($slug)
    {
        $user = $this->user;
        $purchase = Purchase::where('purchase_number', '=', $slug)->first();

        // Security: Verify vendor has items in this purchase
        if (!$purchase || !$purchase->merchantPurchases()->where('user_id', $user->id)->exists()) {
            abort(403, 'Unauthorized access to purchase');
        }

        $cart = json_decode($purchase->cart, true);
        return view('merchant.purchase.invoice', compact('user', 'purchase', 'cart'));
    }

    public function printpage($slug)
    {
        $user = $this->user;
        $purchase = Purchase::where('purchase_number', '=', $slug)->first();

        // Security: Verify vendor has items in this purchase
        if (!$purchase || !$purchase->merchantPurchases()->where('user_id', $user->id)->exists()) {
            abort(403, 'Unauthorized access to purchase');
        }

        $cart = json_decode($purchase->cart, true);
        return view('merchant.purchase.print', compact('user', 'purchase', 'cart'));
    }

    public function status($slug, $status)
    {
        $mainPurchase = MerchantPurchase::where('purchase_number', '=', $slug)->first();
        if ($mainPurchase->status == "completed") {
            return redirect()->back()->with('success', __('This Purchase is Already Completed'));
        } else {
            $user = $this->user;
            MerchantPurchase::where('purchase_number', '=', $slug)->where('user_id', '=', $user->id)->update(['status' => $status]);
            return redirect()->route('merchant-purchase-index')->with('success', __('Purchase Status Updated Successfully'));
        }
    }
}
