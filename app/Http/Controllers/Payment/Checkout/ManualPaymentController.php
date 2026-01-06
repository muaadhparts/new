<?php

namespace App\Http\Controllers\Payment\Checkout;

use App\{
    Models\Cart,
    Models\Purchase,
    Classes\MuaadhMailer
};
use App\Helpers\PriceHelper;
use App\Models\Country;
use App\Models\Reward;
use App\Models\StockReservation;
use App\Traits\HandlesMerchantCheckout;
use App\Traits\SavesCustomerShippingChoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;
use PurchaseHelper;
use Illuminate\Support\Str;

class ManualPaymentController extends CheckoutBaseControlller
{
    use HandlesMerchantCheckout, SavesCustomerShippingChoice;

    public function store(Request $request)
    {
        $merchantData = $this->getMerchantCheckoutData();
        $merchantId = $merchantData['merchant_id'];
        $steps = $this->getCheckoutSteps($merchantId, $merchantData['is_merchant_checkout']);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')->with('unsuccess', __('Checkout session expired.'));
        }

        $input = $request->all();
        $input = array_merge($step1, $step2, $input);
        
        $rules = ['txnid' => 'required'];
        $messages = ['required' => __('The Transaction ID field is required.')];
        \Validator::make($input, $rules, $messages);
        if ($request->pass_check) {
            $auth = PurchaseHelper::auth_check($input); // For Authentication Checking
            if (!$auth['auth_success']) {
                return redirect()->back()->with('unsuccess', $auth['error_message']);
            }
        }

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any catalogItem to checkout."));
        }

        $oldCart = Session::get('cart');
        $originalCart = new Cart($oldCart);
        $cart = $this->filterCartForMerchant($originalCart, $merchantId);
        PurchaseHelper::license_check($cart); // For License Checking

        $new_cart = [];
        $new_cart['totalQty'] = $cart->totalQty;
        $new_cart['totalPrice'] = $cart->totalPrice;
        $new_cart['items'] = $cart->items;
        $new_cart = json_encode($new_cart);
        $temp_affilate_users = PurchaseHelper::item_affilate_check($cart); // For CatalogItem Based Affilate Checking
        $affilate_users = $temp_affilate_users == null ? null : json_encode($temp_affilate_users);
        // ✅ استخدام الدالة الموحدة من CheckoutBaseControlller
        $prepared = $this->prepareOrderData($input, $cart);
        $input = $prepared['input'];
        $purchaseTotal = $prepared['order_total'];

        $purchase = new Purchase;
        $input['user_id'] = Auth::check() ? Auth::user()->id : NULL;
        $input['cart'] = $new_cart;
        $input['affilate_users'] = $affilate_users;
        $input['pay_amount'] = $purchaseTotal;
        $input['purchase_number'] = Str::random(4) . time();
        $input['wallet_price'] = $request->wallet_price / $this->curr->value;

        // Get tax data from merchant step2
        $input['tax'] = $step2['tax_amount'] ?? 0;
        $input['tax_location'] = $step2['tax_location'] ?? '';


        if (Session::has('affilate')) {
            $val = $request->total / $this->curr->value;
            $val = $val / 100;
            $sub = $val * $this->gs->affilate_charge;
            if ($temp_affilate_users != null) {
                $t_sub = 0;
                foreach ($temp_affilate_users as $t_cost) {
                    $t_sub += $t_cost['charge'];
                }
                $sub = $sub - $t_sub;
            }
            if ($sub > 0) {
                $user = PurchaseHelper::affilate_check(Session::get('affilate'), $sub, $input['dp']); // For Affiliate Checking
                $input['affilate_user'] = Session::get('affilate');
                $input['affilate_charge'] = $sub;
            }
        }

        $purchase->fill($input)->save();

        // Clear stock reservations after successful purchase
        StockReservation::clearAfterPurchase();

        // Create DeliveryCourier record if using local courier or pickup
        if ($merchantId) {
            $this->createDeliveryCourier($purchase, $merchantId, $step2, 'online');
        }

        $purchase->tracks()->create(['title' => 'Pending', 'text' => 'You have successfully placed your purchase.']);
        $purchase->notifications()->create();

        if ($input['discount_code_id'] != "") {
            PurchaseHelper::discount_code_check($input['discount_code_id']); // For Discount Code Checking
        }

        if (Auth::check()) {
            if ($this->gs->is_reward == 1) {
                $num = $purchase->pay_amount;
                $rewards = Reward::get();
                foreach ($rewards as $i) {
                    $smallest[$i->order_amount] = abs($i->order_amount - $num);
                }

                if (isset($smallest)) {
                    asort($smallest);
                    $final_reword = Reward::where('order_amount', key($smallest))->first();
                    Auth::user()->update(['reward' => (Auth::user()->reward + $final_reword->reward)]);
                }
            }
        }

        PurchaseHelper::size_qty_check($cart); // For Size Quantiy Checking
        PurchaseHelper::stock_check($cart); // For Stock Checking
        PurchaseHelper::merchant_purchase_check($cart, $purchase); // For Merchant Purchase Checking

        Session::put('temporder', $purchase);
        Session::put('tempcart', $cart);

        // Remove only merchant's items from cart
        $this->removeMerchantItemsFromCart($merchantId, $originalCart);

        if ($purchase->user_id != 0 && $purchase->wallet_price != 0) {
            PurchaseHelper::add_to_wallet_log($purchase, $purchase->wallet_price); // Store To Wallet Log
        }

        //Sending Email To Buyer
        $data = [
            'to' => $purchase->customer_email,
            'type' => "new_order",
            'cname' => $purchase->customer_name,
            'oamount' => "",
            'aname' => "",
            'aemail' => "",
            'wtitle' => "",
            'onumber' => $purchase->purchase_number,
        ];

        $mailer = new MuaadhMailer();
        $mailer->sendAutoPurchaseMail($data, $purchase->id);

        //Sending Email To Admin
        $data = [
            'to' => $this->ps->contact_email,
            'subject' => "New Purchase Recieved!!",
            'body' => "Hello Operator!<br>Your store has received a new purchase.<br>Purchase Number is " . $purchase->purchase_number . ".Please login to your panel to check. <br>Thank you.",
        ];
        $mailer = new MuaadhMailer();
        $mailer->sendCustomMail($data);

        // Determine success URL based on remaining cart items
        $success_url = $this->getSuccessUrl($merchantId, $originalCart);
        return redirect($success_url);
    }
}
