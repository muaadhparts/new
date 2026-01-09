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

        // Security: Verify merchant has items in this purchase
        if (!$purchase || !$purchase->merchantPurchases()->where('user_id', $user->id)->exists()) {
            abort(403, 'Unauthorized access to purchase');
        }

        $cart = $purchase->cart; // Model cast handles decoding
        return view('merchant.purchase.details', compact('user', 'purchase', 'cart'));
    }

    public function invoice($slug)
    {
        $user = $this->user;
        $purchase = Purchase::where('purchase_number', '=', $slug)->first();

        // Security: Verify merchant has items in this purchase
        if (!$purchase || !$purchase->merchantPurchases()->where('user_id', $user->id)->exists()) {
            abort(403, 'Unauthorized access to purchase');
        }

        $cart = $purchase->cart; // Model cast handles decoding
        return view('merchant.purchase.invoice', compact('user', 'purchase', 'cart'));
    }

    public function printpage($slug)
    {
        $user = $this->user;
        $purchase = Purchase::where('purchase_number', '=', $slug)->first();

        // Security: Verify merchant has items in this purchase
        if (!$purchase || !$purchase->merchantPurchases()->where('user_id', $user->id)->exists()) {
            abort(403, 'Unauthorized access to purchase');
        }

        $cart = $purchase->cart; // Model cast handles decoding
        return view('merchant.purchase.print', compact('user', 'purchase', 'cart'));
    }

    /**
     * Update merchant purchase status
     *
     * @param string $slug Purchase number
     * @param string $status New status (pending, processing, on delivery, completed, declined)
     */
    public function status($slug, $status)
    {
        $user = $this->user;

        // Get THIS merchant's record only (not any merchant with same purchase_number)
        $merchantPurchase = MerchantPurchase::where('purchase_number', $slug)
            ->where('user_id', $user->id)
            ->first();

        if (!$merchantPurchase) {
            return redirect()->back()->with('unsuccess', __('Purchase not found or unauthorized'));
        }

        if ($merchantPurchase->status === 'completed') {
            return redirect()->back()->with('success', __('This Purchase is Already Completed'));
        }

        // Update status
        $merchantPurchase->status = $status;
        $merchantPurchase->save();

        // If all merchant purchases for this purchase are completed, update main purchase
        $purchase = $merchantPurchase->purchase;
        if ($purchase && $status === 'completed') {
            $allCompleted = $purchase->merchantPurchases()
                ->where('status', '!=', 'completed')
                ->count() === 0;

            if ($allCompleted) {
                $purchase->status = 'completed';
                $purchase->save();
            }
        }

        return redirect()->route('merchant-purchase-index')->with('success', __('Purchase Status Updated Successfully'));
    }
}
