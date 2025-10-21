<?php

/**
 * ====================================================================
 * MULTI-VENDOR CASH ON DELIVERY PAYMENT CONTROLLER
 * ====================================================================
 *
 * This controller handles COD payment in a multi-vendor system:
 *
 * Key Features:
 * 1. Uses HandlesVendorCheckout trait for vendor isolation
 * 2. Detects if checkout is vendor-specific via checkout_vendor_id session
 * 3. Filters cart to process ONLY vendor's products
 * 4. Creates order with ONLY vendor's products
 * 5. Removes ONLY vendor's products from cart after order
 * 6. Redirects to /carts if other vendors remain, else to success page
 *
 * Multi-Vendor Logic Flow:
 * 1. getVendorCheckoutData() - Checks if vendor checkout
 * 2. getCheckoutSteps() - Gets vendor_step1_{id} and vendor_step2_{id}
 * 3. filterCartForVendor() - Filters cart items
 * 4. Order creation - Uses only filtered products
 * 5. removeVendorProductsFromCart() - Removes vendor products only
 * 6. getSuccessUrl() - Determines redirect based on remaining items
 *
 * Modified: 2025-01-XX for Multi-Vendor Checkout System
 * ====================================================================
 */

namespace App\Http\Controllers\Payment\Checkout;

use App\Classes\MuaadhMailer;use App\Helpers\OrderHelper;use App\Helpers\PriceHelper;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Order;
use App\Models\Reward;
use App\Models\State;
use App\Traits\CreatesTryotoShipments;
use App\Traits\HandlesVendorCheckout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CashOnDeliveryController extends CheckoutBaseControlller
{
    use CreatesTryotoShipments, HandlesVendorCheckout;

    /**
     * Process COD payment for single vendor or complete cart
     *
     * MULTI-VENDOR LOGIC:
     * 1. Detects vendor checkout via checkout_vendor_id session
     * 2. Loads vendor-specific session data (vendor_step1_{id}, vendor_step2_{id})
     * 3. Filters cart to include ONLY this vendor's products
     * 4. Creates order with filtered products only
     * 5. Removes only this vendor's products from cart
     * 6. Redirects to /carts if other vendors remain
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $input = $request->all();

        // Detect if this is a vendor-specific checkout
        // Uses HandlesVendorCheckout trait methods
        $vendorData = $this->getVendorCheckoutData();
        $vendorId = $vendorData['vendor_id'];
        $isVendorCheckout = $vendorData['is_vendor_checkout'];

        // Load step data from vendor-specific or global session
        $steps = $this->getCheckoutSteps($vendorId, $isVendorCheckout);
        $input = array_merge($steps['step1'], $steps['step2'], $input);

        if ($request->pass_check) {
            $auth = OrderHelper::auth_check($input); // For Authentication Checking
            if (!$auth['auth_success']) {
                return redirect()->back()->with('unsuccess', $auth['error_message']);
            }
        }

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', __("You don't have any product to checkout."));
        }

        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        // CRITICAL: Filter cart to include ONLY this vendor's products
        // This ensures order contains only vendor's items
        if ($isVendorCheckout) {
            $cart = $this->filterCartForVendor($cart, $vendorId);
        }

        OrderHelper::license_check($cart); // For License Checking
        $t_cart = $cart;
        $new_cart = [];
        $new_cart['totalQty'] = $t_cart->totalQty;
        $new_cart['totalPrice'] = $t_cart->totalPrice;
        $new_cart['items'] = $t_cart->items;
        $new_cart = json_encode($new_cart);
        $temp_affilate_users = OrderHelper::product_affilate_check($cart); // For Product Based Affilate Checking
        $affilate_users = $temp_affilate_users == null ? null : json_encode($temp_affilate_users);

        $orderCalculate = PriceHelper::getOrderTotal($input, $cart);

        if (isset($orderCalculate['success']) && $orderCalculate['success'] == false) {
            return redirect()->back()->with('unsuccess', $orderCalculate['message']);
        }

        if ($this->gs->multiple_shipping == 0) {
            $orderTotal = $orderCalculate['total_amount'];
            $shipping = $orderCalculate['shipping'];
            $packeing = $orderCalculate['packeing'];
            $is_shipping = $orderCalculate['is_shipping'];
            $vendor_shipping_ids = $orderCalculate['vendor_shipping_ids'];
            $vendor_packing_ids = $orderCalculate['vendor_packing_ids'];
            $vendor_ids = $orderCalculate['vendor_ids'];

            $input['shipping_title'] = @$shipping->title;
            $input['vendor_shipping_id'] = @$shipping->id;
            $input['packing_title'] = @$packeing->title;
            $input['vendor_packing_id'] = @$packeing->id;
            $input['shipping_cost'] = @$packeing->price ?? 0;
            $input['packing_cost'] = @$packeing->price ?? 0;
            $input['is_shipping'] = $is_shipping;
            $input['vendor_shipping_ids'] = $vendor_shipping_ids;
            $input['vendor_packing_ids'] = $vendor_packing_ids;
            $input['vendor_ids'] = $vendor_ids;
        } else {

            // multi shipping

            $orderTotal = $orderCalculate['total_amount'];
            $shipping = $orderCalculate['shipping'];
            $packeing = $orderCalculate['packeing'];
            $is_shipping = $orderCalculate['is_shipping'];
            $vendor_shipping_ids = $orderCalculate['vendor_shipping_ids'];
            $vendor_packing_ids = $orderCalculate['vendor_packing_ids'];
            $vendor_ids = $orderCalculate['vendor_ids'];
            $shipping_cost = $orderCalculate['shipping_cost'];
            $packing_cost = $orderCalculate['packing_cost'];

            $input['shipping_title'] = $vendor_shipping_ids;
            $input['vendor_shipping_id'] = $vendor_shipping_ids;
            $input['packing_title'] = $vendor_packing_ids;
            $input['vendor_packing_id'] = $vendor_packing_ids;
            $input['shipping_cost'] = $shipping_cost;
            $input['packing_cost'] = $packing_cost;
            $input['is_shipping'] = $is_shipping;
            $input['vendor_shipping_ids'] = $vendor_shipping_ids;
            $input['vendor_packing_ids'] = $vendor_packing_ids;
            $input['vendor_ids'] = $vendor_ids;
            unset($input['shipping']);
            unset($input['packeging']);
        }

        $order = new Order;

        // Determine redirect URL:
        // - If other vendors remain in cart: Redirect to /carts
        // - If cart is now empty: Redirect to success page
        $success_url = $this->getSuccessUrl($vendorId, $oldCart);
        $input['user_id'] = Auth::check() ? Auth::user()->id : null;
        $input['cart'] = $new_cart;
        $input['affilate_users'] = $affilate_users;
        $input['pay_amount'] = $orderTotal;
        $input['order_number'] = Str::random(4) . time();
        $input['wallet_price'] = $request->wallet_price / $this->curr->value;
        if ($input['tax_type'] == 'state_tax') {
            $input['tax_location'] = State::findOrFail($input['tax'])->state;
        } else {
            $input['tax_location'] = Country::findOrFail($input['tax'])->country_name;
        }
        $input['tax'] = Session::get('current_tax');

        if (Session::has('affilate')) {
            $val = $request->total / $this->curr->value;
            $val = $val / 100;
            $sub = $val * $this->gs->affilate_charge;
            if ($temp_affilate_users != null) {
                $t_sub = 0;
                if (is_array($temp_affilate_users)) {
                    foreach ($temp_affilate_users as $t_cost) {
                        $t_sub += $t_cost['charge'];
                    }
                }
                $sub = $sub - $t_sub;
            }
            if ($sub > 0) {
                OrderHelper::affilate_check(Session::get('affilate'), $sub, $input['dp']); // For Affiliate Checking
                $input['affilate_user'] = Session::get('affilate');
                $input['affilate_charge'] = $sub;
            }
        }

        $order->fill($input)->save();

        // ⭐ Create Tryoto shipment for COD orders
        $this->createOtoShipments($order, $input);

        $order->tracks()->create(['title' => 'Pending', 'text' => 'You have successfully placed your order.']);
        $order->notifications()->create();

        if ($input['coupon_id'] != "") {
            OrderHelper::coupon_check($input['coupon_id']); // For Coupon Checking
        }

        if (Auth::check()) {
            if ($this->gs->is_reward == 1) {
                $num = $order->pay_amount;
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

        OrderHelper::size_qty_check($cart); // For Size Quantiy Checking
        OrderHelper::stock_check($cart); // For Stock Checking
        OrderHelper::vendor_order_check($cart, $order); // For Vendor Order Checking

        Session::put('temporder', $order);
        Session::put('tempcart', $cart);

        // CRITICAL: Remove ONLY this vendor's products from cart
        // Other vendors' products remain for separate checkout
        // Uses HandlesVendorCheckout trait method
        $this->removeVendorProductsFromCart($vendorId, $oldCart);

        if ($order->user_id != 0 && $order->wallet_price != 0) {
            OrderHelper::add_to_transaction($order, $order->wallet_price); // Store To Transactions
        }

        //Sending Email To Buyer
        $data = [
            'to' => $order->customer_email,
            'type' => "new_order",
            'cname' => $order->customer_name,
            'oamount' => "",
            'aname' => "",
            'aemail' => "",
            'wtitle' => "",
            'onumber' => $order->order_number,
        ];

        $mailer = new MuaadhMailer();
        $mailer->sendAutoOrderMail($data, $order->id);

        //Sending Email To Admin
        $data = [
            'to' => $this->ps->contact_email,
            'subject' => "New Order Recieved!!",
            'body' => "Hello Admin!<br>Your store has received a new order.<br>Order Number is " . $order->order_number . ".Please login to your panel to check. <br>Thank you.",
        ];
        $mailer = new MuaadhMailer();
        $mailer->sendCustomMail($data);

        return redirect($success_url);
    }
}
