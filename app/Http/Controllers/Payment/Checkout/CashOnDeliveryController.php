<?php

/**
 * ====================================================================
 * MULTI-MERCHANT CASH ON DELIVERY PAYMENT CONTROLLER
 * ====================================================================
 *
 * STRICT POLICY (2025-12):
 * - merchant_id comes from ROUTE only: /checkout/merchant/{merchantId}/payment/cod
 * - NO session-based merchant tracking
 *
 * Key Features:
 * 1. Uses HandlesMerchantCheckout trait for merchant isolation
 * 2. Gets merchant_id from route parameter (not session)
 * 3. Filters cart to process ONLY merchant's items
 * 4. Creates purchase with ONLY merchant's items
 * 5. Removes ONLY merchant's items from cart after purchase
 * 6. Redirects to /carts if other merchants remain, else to success page
 *
 * Multi-Merchant Logic Flow:
 * 1. merchant_id from route parameter
 * 2. getCheckoutSteps() - Gets merchant_step1_{id} and merchant_step2_{id}
 * 3. filterCartForMerchant() - Filters cart items
 * 4. Purchase creation - Uses only filtered items
 * 5. removeMerchantItemsFromCart() - Removes merchant items only
 * 6. getSuccessUrl() - Determines redirect based on remaining items
 *
 * Modified: 2025-12 for Route-based Merchant Checkout
 * ====================================================================
 */

namespace App\Http\Controllers\Payment\Checkout;

use App\Classes\MuaadhMailer;use App\Helpers\PurchaseHelper;use App\Helpers\PriceHelper;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Purchase;
use App\Models\Reward;
use App\Models\StockReservation;
use App\Traits\CreatesTryotoShipments;
use App\Traits\HandlesMerchantCheckout;
use App\Traits\SavesCustomerShippingChoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CashOnDeliveryController extends CheckoutBaseControlller
{
    use CreatesTryotoShipments, HandlesMerchantCheckout, SavesCustomerShippingChoice;

    /**
     * Process COD payment for single merchant
     *
     * POLICY: merchant_id comes from ROUTE only
     * Route: /checkout/merchant/{merchantId}/payment/cod
     *
     * MULTI-MERCHANT LOGIC:
     * 1. Gets merchant_id from route parameter
     * 2. Loads merchant-specific session data (merchant_step1_{id}, merchant_step2_{id})
     * 3. Filters cart to include ONLY this merchant's items
     * 4. Creates purchase with filtered items only
     * 5. Removes only this merchant's items from cart
     * 6. Redirects to /carts if other merchants remain
     */
    public function store(Request $request, $merchantId)
    {
        // ====================================================================
        // STRICT POLICY: merchant_id FROM ROUTE ONLY
        // No session, no POST, no fallback - fail immediately if missing
        // ====================================================================
        $merchantId = (int)$merchantId;

        if (!$merchantId) {
            return redirect()->route('front.cart')
                ->with('unsuccess', __('خطأ: لم يتم تحديد التاجر في مسار الدفع.'));
        }

        $input = $request->all();

        // Load step data from merchant-specific session
        $steps = $this->getCheckoutSteps($merchantId);
        $step1 = $steps['step1'];
        $step2 = $steps['step2'];

        if (!$step1 || !$step2) {
            return redirect()->route('front.cart')
                ->with('unsuccess', __('انتهت صلاحية جلسة الدفع. يرجى إعادة المحاولة.'));
        }

        $input = array_merge($step1, $step2, $input);

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

        // CRITICAL: Filter cart to include ONLY this merchant's items
        // merchant_id comes from route, so we ALWAYS filter
        $cart = $this->filterCartForMerchant($originalCart, $merchantId);

        $t_cart = $cart;
        $new_cart = [];
        $new_cart['totalQty'] = $t_cart->totalQty;
        $new_cart['totalPrice'] = $t_cart->totalPrice;
        $new_cart['items'] = $t_cart->items;
        $new_cart = json_encode($new_cart);
        $temp_affilate_users = PurchaseHelper::item_affilate_check($cart); // For CatalogItem Based Affilate Checking
        $affilate_users = $temp_affilate_users == null ? null : json_encode($temp_affilate_users);

        // ✅ استخدام المبلغ القادم من step3 مباشرة (المبلغ الصحيح المحسوب مسبقاً)
        // بدلاً من إعادة الحساب باستخدام PriceHelper::getPurchaseTotal
        $purchaseTotal = (float) ($input['total'] ?? 0) / $this->curr->value;

        // Prepare merchant IDs from cart
        $merchant_ids = [];
        foreach ($cart->items as $item) {
            if (!in_array($item['item']['user_id'], $merchant_ids)) {
                $merchant_ids[] = $item['item']['user_id'];
            }
        }

        // تحضير بيانات الشحن والتغليف - تأكد من تحويل كل القيم إلى JSON
        $input['merchant_ids'] = json_encode($merchant_ids);

        // ✅ حفظ طريقة الشحن الأصلية (shipto) قبل أي معالجة
        $originalShippingMethod = $steps['step1']['shipping'] ?? 'shipto';

        // إذا كان shipping string (shipto) وليس array، نحفظه
        if (isset($input['shipping']) && is_string($input['shipping']) && $input['shipping'] === 'shipto') {
            $originalShippingMethod = $input['shipping'];
        }

        // MERCHANT CHECKOUT ONLY (regular checkout is disabled)
        // merchant_id comes from route, so this is ALWAYS merchant checkout
        $input['shipping_title'] = $step2['shipping_company'] ?? '';
        $input['packing_title'] = $step2['packing_company'] ?? '';

        // ✅ FIX (2026-01-09): Extract shipping cost from step2 data
        // Step2 already has shipping_cost and courier_fee calculated by CheckoutController
        $deliveryType = $step2['delivery_type'] ?? 'shipping';
        if ($deliveryType === 'local_courier') {
            // Local courier - use courier_fee
            $input['shipping_cost'] = (float)($step2['courier_fee'] ?? 0);
            $input['couriers'] = json_encode([$merchantId => (int)($step2['courier_id'] ?? 0)]);
        } else {
            // Shipping company - use shipping_cost
            $input['shipping_cost'] = (float)($step2['shipping_cost'] ?? 0);
        }
        $input['packing_cost'] = (float)($step2['packing_cost'] ?? 0);

        $input['merchant_shipping_ids'] = json_encode([$merchantId => (int)($input['merchant_shipping_id'] ?? 0)]);
        $input['merchant_packing_ids'] = json_encode([$merchantId => (int)($input['merchant_packing_id'] ?? 0)]);

        /* DISABLED: Regular checkout logic (kept for reference)
        if ($this->gs->multiple_shipping == 0) {
            // Single shipping
                $input['shipping_title'] = $input['shipping_title'] ?? '';
                $input['packing_title'] = $input['packing_title'] ?? '';
                $input['shipping_cost'] = (float)($input['shipping_cost'] ?? 0);
                $input['packing_cost'] = (float)($input['packing_cost'] ?? 0);

                // تحويل إلى JSON إذا كانت مصفوفات
                if (isset($input['merchant_shipping_ids']) && is_array($input['merchant_shipping_ids'])) {
                    $input['merchant_shipping_ids'] = json_encode($input['merchant_shipping_ids']);
                } elseif (!isset($input['merchant_shipping_ids'])) {
                    $input['merchant_shipping_ids'] = json_encode([]);
                }

                if (isset($input['merchant_packing_ids']) && is_array($input['merchant_packing_ids'])) {
                    $input['merchant_packing_ids'] = json_encode($input['merchant_packing_ids']);
                } elseif (!isset($input['merchant_packing_ids'])) {
                    $input['merchant_packing_ids'] = json_encode([]);
                }
            } else {
                // Multi shipping
                $input['shipping_cost'] = (float)($input['shipping_cost'] ?? 0);
                $input['packing_cost'] = (float)($input['packing_cost'] ?? 0);

                // تحويل المصفوفات إلى JSON
                if (isset($input['shipping']) && is_array($input['shipping'])) {
                    $input['merchant_shipping_ids'] = json_encode($input['shipping']);
                    $input['shipping_title'] = json_encode($input['shipping']);
                    $input['merchant_shipping_id'] = json_encode($input['shipping']);
                    unset($input['shipping']);
                } elseif (isset($input['merchant_shipping_ids'])) {
                    if (is_array($input['merchant_shipping_ids'])) {
                        $input['merchant_shipping_ids'] = json_encode($input['merchant_shipping_ids']);
                    }
                    $input['shipping_title'] = $input['merchant_shipping_ids'];
                    $input['merchant_shipping_id'] = $input['merchant_shipping_ids'];
                } else {
                    $input['merchant_shipping_ids'] = json_encode([]);
                    $input['shipping_title'] = json_encode([]);
                    $input['merchant_shipping_id'] = json_encode([]);
                }

                if (isset($input['packeging']) && is_array($input['packeging'])) {
                    $input['merchant_packing_ids'] = json_encode($input['packeging']);
                    $input['packing_title'] = json_encode($input['packeging']);
                    $input['merchant_packing_id'] = json_encode($input['packeging']);
                    unset($input['packeging']);
                } elseif (isset($input['merchant_packing_ids'])) {
                    if (is_array($input['merchant_packing_ids'])) {
                        $input['merchant_packing_ids'] = json_encode($input['merchant_packing_ids']);
                    }
                    $input['packing_title'] = $input['merchant_packing_ids'];
                    $input['merchant_packing_id'] = $input['merchant_packing_ids'];
                } else {
                    $input['merchant_packing_ids'] = json_encode([]);
                    $input['packing_title'] = json_encode([]);
                    $input['merchant_packing_id'] = json_encode([]);
                }
            }
        }
        END OF DISABLED REGULAR CHECKOUT LOGIC */

        // تأكد من إزالة أي مصفوفات متبقية
        unset($input['packeging']);

        // ✅ إعادة تعيين قيمة shipping الأصلية (shipto) للعرض في الفاتورة
        $input['shipping'] = $originalShippingMethod;

        // ✅ حفظ بيانات شركة الشحن المختارة من العميل
        $input['customer_shipping_choice'] = $this->extractCustomerShippingChoice($step2, $merchantId);

        $purchase = new Purchase;

        // Determine redirect URL:
        // - If other merchants remain in cart: Redirect to /carts
        // - If cart is now empty: Redirect to success page
        $success_url = $this->getSuccessUrl($merchantId, $oldCart);
        $input['user_id'] = Auth::check() ? Auth::user()->id : null;
        $input['cart'] = $new_cart;
        $input['affilate_users'] = $affilate_users;
        $input['pay_amount'] = $purchaseTotal;
        $input['purchase_number'] = Str::random(4) . time();
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
                PurchaseHelper::affilate_check(Session::get('affilate'), $sub, 0); // For Affiliate Checking
                $input['affilate_user'] = Session::get('affilate');
                $input['affilate_charge'] = $sub;
            }
        }

        $purchase->fill($input)->save();

        // Clear stock reservations after successful purchase (stock already sold)
        StockReservation::clearAfterPurchase();

        // Create DeliveryCourier record if using local courier
        $this->createDeliveryCourier($purchase, $merchantId, $step2, 'cod');

        // Create Tryoto shipment for COD purchases
        $this->createOtoShipments($purchase, $input);

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
                    $smallest[$i->purchase_amount] = abs($i->purchase_amount - $num);
                }

                if (isset($smallest)) {
                    asort($smallest);
                    $final_reword = Reward::where('purchase_amount', key($smallest))->first();
                    Auth::user()->update(['reward' => (Auth::user()->reward + $final_reword->reward)]);
                }

            }
        }

        PurchaseHelper::size_qty_check($cart); // For Size Quantiy Checking
        PurchaseHelper::stock_check($cart); // For Stock Checking
        PurchaseHelper::merchant_purchase_check($cart, $purchase); // For Merchant Purchase Checking

        Session::put('temporder', $purchase);
        Session::put('tempcart', $cart);

        // CRITICAL: Remove ONLY this merchant's items from cart
        // Other merchants' items remain for separate checkout
        // Uses HandlesMerchantCheckout trait method
        $this->removeMerchantItemsFromCart($merchantId, $oldCart);

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

        return redirect($success_url);
    }
}
