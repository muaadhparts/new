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
use App\Models\StockReservation;
use App\Traits\CreatesTryotoShipments;
use App\Traits\HandlesVendorCheckout;
use App\Traits\SavesCustomerShippingChoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CashOnDeliveryController extends CheckoutBaseControlller
{
    use CreatesTryotoShipments, HandlesVendorCheckout, SavesCustomerShippingChoice;

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
    /**
     * POLICY: vendor_id comes from ROUTE only
     * Route: /checkout/vendor/{vendorId}/payment/cod
     */
    public function store(Request $request, $vendorId)
    {
        // ====================================================================
        // STRICT POLICY: vendor_id FROM ROUTE ONLY
        // No session, no POST, no fallback - fail immediately if missing
        // ====================================================================
        $vendorId = (int)$vendorId;

        if (!$vendorId) {
            return redirect()->route('front.cart')
                ->with('unsuccess', __('خطأ: لم يتم تحديد التاجر في مسار الدفع.'));
        }

        $input = $request->all();

        // Load step data from vendor-specific session
        $steps = $this->getCheckoutSteps($vendorId);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')
                ->with('unsuccess', __('انتهت صلاحية جلسة الدفع. يرجى إعادة المحاولة.'));
        }

        $input = array_merge($step1, $step2, $input);

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
        $originalCart = new Cart($oldCart);

        // CRITICAL: Filter cart to include ONLY this vendor's products
        // vendor_id comes from route, so we ALWAYS filter
        $cart = $this->filterCartForVendor($originalCart, $vendorId);

        OrderHelper::license_check($cart); // For License Checking
        $t_cart = $cart;
        $new_cart = [];
        $new_cart['totalQty'] = $t_cart->totalQty;
        $new_cart['totalPrice'] = $t_cart->totalPrice;
        $new_cart['items'] = $t_cart->items;
        $new_cart = json_encode($new_cart);
        $temp_affilate_users = OrderHelper::product_affilate_check($cart); // For Product Based Affilate Checking
        $affilate_users = $temp_affilate_users == null ? null : json_encode($temp_affilate_users);

        // ✅ استخدام المبلغ القادم من step3 مباشرة (المبلغ الصحيح المحسوب مسبقاً)
        // بدلاً من إعادة الحساب باستخدام PriceHelper::getOrderTotal
        $orderTotal = (float) ($input['total'] ?? 0) / $this->curr->value;

        // Prepare vendor IDs from cart
        $vendor_ids = [];
        foreach ($cart->items as $item) {
            if (!in_array($item['item']['user_id'], $vendor_ids)) {
                $vendor_ids[] = $item['item']['user_id'];
            }
        }

        // تحضير بيانات الشحن والتغليف - تأكد من تحويل كل القيم إلى JSON
        $input['vendor_ids'] = json_encode($vendor_ids);

        // ✅ حفظ طريقة الشحن الأصلية (shipto/pickup) قبل أي معالجة
        $originalShippingMethod = $steps['step1']['shipping'] ?? 'shipto';

        // إذا كان shipping string (shipto/pickup) وليس array، نحفظه
        if (isset($input['shipping']) && is_string($input['shipping']) && in_array($input['shipping'], ['shipto', 'pickup'])) {
            $originalShippingMethod = $input['shipping'];
        }

        // VENDOR CHECKOUT ONLY (regular checkout is disabled)
        // vendor_id comes from route, so this is ALWAYS vendor checkout
        $input['shipping_title'] = '';
        $input['packing_title'] = '';
        $input['shipping_cost'] = 0;
        $input['packing_cost'] = 0;
        $input['vendor_shipping_ids'] = json_encode([$vendorId => (int)($input['vendor_shipping_id'] ?? 0)]);
        $input['vendor_packing_ids'] = json_encode([$vendorId => (int)($input['vendor_packing_id'] ?? 0)]);

        /* DISABLED: Regular checkout logic (kept for reference)
        if ($this->gs->multiple_shipping == 0) {
            // Single shipping
                $input['shipping_title'] = $input['shipping_title'] ?? '';
                $input['packing_title'] = $input['packing_title'] ?? '';
                $input['shipping_cost'] = (float)($input['shipping_cost'] ?? 0);
                $input['packing_cost'] = (float)($input['packing_cost'] ?? 0);

                // تحويل إلى JSON إذا كانت مصفوفات
                if (isset($input['vendor_shipping_ids']) && is_array($input['vendor_shipping_ids'])) {
                    $input['vendor_shipping_ids'] = json_encode($input['vendor_shipping_ids']);
                } elseif (!isset($input['vendor_shipping_ids'])) {
                    $input['vendor_shipping_ids'] = json_encode([]);
                }

                if (isset($input['vendor_packing_ids']) && is_array($input['vendor_packing_ids'])) {
                    $input['vendor_packing_ids'] = json_encode($input['vendor_packing_ids']);
                } elseif (!isset($input['vendor_packing_ids'])) {
                    $input['vendor_packing_ids'] = json_encode([]);
                }
            } else {
                // Multi shipping
                $input['shipping_cost'] = (float)($input['shipping_cost'] ?? 0);
                $input['packing_cost'] = (float)($input['packing_cost'] ?? 0);

                // تحويل المصفوفات إلى JSON
                if (isset($input['shipping']) && is_array($input['shipping'])) {
                    $input['vendor_shipping_ids'] = json_encode($input['shipping']);
                    $input['shipping_title'] = json_encode($input['shipping']);
                    $input['vendor_shipping_id'] = json_encode($input['shipping']);
                    unset($input['shipping']);
                } elseif (isset($input['vendor_shipping_ids'])) {
                    if (is_array($input['vendor_shipping_ids'])) {
                        $input['vendor_shipping_ids'] = json_encode($input['vendor_shipping_ids']);
                    }
                    $input['shipping_title'] = $input['vendor_shipping_ids'];
                    $input['vendor_shipping_id'] = $input['vendor_shipping_ids'];
                } else {
                    $input['vendor_shipping_ids'] = json_encode([]);
                    $input['shipping_title'] = json_encode([]);
                    $input['vendor_shipping_id'] = json_encode([]);
                }

                if (isset($input['packeging']) && is_array($input['packeging'])) {
                    $input['vendor_packing_ids'] = json_encode($input['packeging']);
                    $input['packing_title'] = json_encode($input['packeging']);
                    $input['vendor_packing_id'] = json_encode($input['packeging']);
                    unset($input['packeging']);
                } elseif (isset($input['vendor_packing_ids'])) {
                    if (is_array($input['vendor_packing_ids'])) {
                        $input['vendor_packing_ids'] = json_encode($input['vendor_packing_ids']);
                    }
                    $input['packing_title'] = $input['vendor_packing_ids'];
                    $input['vendor_packing_id'] = $input['vendor_packing_ids'];
                } else {
                    $input['vendor_packing_ids'] = json_encode([]);
                    $input['packing_title'] = json_encode([]);
                    $input['vendor_packing_id'] = json_encode([]);
                }
            }
        }
        END OF DISABLED REGULAR CHECKOUT LOGIC */

        // تأكد من إزالة أي مصفوفات متبقية
        unset($input['packeging']);

        // ✅ إعادة تعيين قيمة shipping الأصلية (shipto/pickup) للعرض في الفاتورة
        $input['shipping'] = $originalShippingMethod;

        // ✅ حفظ بيانات شركة الشحن المختارة من العميل
        $input['customer_shipping_choice'] = $this->extractCustomerShippingChoice($step2, $vendorId);

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

        // Get tax data from step2 (already retrieved at method start)
        $input['tax'] = $step2['tax_amount'] ?? 0;
        $input['tax_location'] = $step2['tax_location'] ?? '';

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

        // ⭐ Clear stock reservations after successful order (stock already sold)
        StockReservation::clearAfterPurchase();

        // ⭐ Create Tryoto shipment for COD orders
        $this->createOtoShipments($order, $input);

        $order->tracks()->create(['title' => 'Pending', 'text' => 'You have successfully placed your order.']);
        $order->notifications()->create();

        if ($input['discount_code_id'] != "") {
            OrderHelper::discount_code_check($input['discount_code_id']); // For Discount Code Checking
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
