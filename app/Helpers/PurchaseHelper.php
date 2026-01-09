<?php

namespace App\Helpers;

use App\{
    Models\Cart,
    Models\User,
    Models\DiscountCode,
    Models\CatalogItem,
    Models\WalletLog,
    Models\MerchantPurchase,
    Models\CatalogEvent,
    Models\UserCatalogEvent
};
use App\Models\ReferralCommission;
use Auth;
use Session;
use Illuminate\Support\Str;

class PurchaseHelper
{
    public static function auth_check($data)
    {
        try {
            $resdata = array();
            $users = User::where('email', '=', $data['personal_email'])->get();
            if (count($users) == 0) {
                if ($data['personal_pass'] == $data['personal_confirm']) {
                    $user = new User;
                    $user->name = $data['personal_name'];
                    $user->email = $data['personal_email'];
                    $user->password = bcrypt($data['personal_pass']);
                    $token = md5(time() . $data['personal_name'] . $data['personal_email']);
                    $user->verification_link = $token;
                    $user->affilate_code = md5($data['personal_name'] . $data['personal_email']);
                    $user->email_verified = 'Yes';
                    $user->save();
                    Auth::login($user);
                    $resdata['auth_success'] = true;
                } else {
                    $resdata['auth_success'] = false;
                    $resdata['error_message'] = __("Confirm Password Doesn't Match.");
                }
            } else {
                $resdata['auth_success'] = false;
                $resdata['error_message'] = __("This Email Already Exist.");
            }
            return $resdata;
        } catch (\Exception $e) {
        }
    }

    public static function item_affilate_check($cart)
    {
        $affilate_users = null;
        $i = 0;
        $gs = \App\Models\Muaadhsetting::find(1);
        $percentage = $gs->affilate_charge / 100;
        foreach ($cart->items as $cartItem) {

            if ($cartItem['affilate_user'] != 0) {
                if (Auth::user()->id != $cartItem['affilate_user']) {
                    $affilate_users[$i]['user_id'] = $cartItem['affilate_user'];
                    $affilate_users[$i]['catalog_item_id'] = $cartItem['item']['id'];
                    $price = $cartItem['price'] * $percentage;
                    $affilate_users[$i]['charge'] = $price;
                    $i++;
                }
            }
        }
        return $affilate_users;
    }


    public static function set_currency($new_value)
    {

        try {
            $oldCart = Session::get('cart');
            $cart = new Cart($oldCart);

            foreach ($cart->items as $key => $cartItem) {

                $cart->items[$key]['price'] = $cart->items[$key]['price'] * $new_value;
                $cart->items[$key]['item']['price'] = $cart->items[$key]['item']['price'] * $new_value;
            }
            Session::put('cart', $cart);
        } catch (\Exception $e) {
        }
    }


    public static function affilate_check($id, $sub, $dp = 0)
    {
        try {
            $user = User::find($id);
            // Physical-only system - referral commission handled elsewhere
            return $user;
        } catch (\Exception $e) {
        }
    }

    public static function discount_code_check($id)
    {
        try {
            $discountCode = DiscountCode::find($id);
            $discountCode->used++;
            if ($discountCode->times != null) {
                $i = (int)$discountCode->times;
                $i--;
                $discountCode->times = (string)$i;
            }
            $discountCode->update();
        } catch (\Exception $e) {
        }
    }

    public static function size_qty_check($cart)
    {
        try {
            foreach ($cart->items as $cartItem) {
                $x = (string)$cartItem['size_qty'];

                if (!empty($x) && $x != "undefined") {
                    // Update size_qty in merchant_items instead of catalog_items
                    $merchantItem = \App\Models\MerchantItem::where('catalog_item_id', $cartItem['item']['id'])
                        ->where('user_id', $cartItem['user_id'])
                        ->first();

                    if ($merchantItem) {
                        $x = (int)$x;
                        $x = $x - $cartItem['qty'];
                        $temp = $merchantItem->size_qty;
                        $temp[$cartItem['size_key']] = $x;
                        $temp1 = implode(',', $temp);
                        $merchantItem->size_qty = $temp1;
                        $merchantItem->update();
                    }
                }
            }
        } catch (\Exception $e) {
        }
    }

    public static function stock_check($cart)
    {
        try {
            foreach ($cart->items as $cartItem) {
                $x = (string)$cartItem['stock'];
                if ($x != null) {
                    // Update stock in merchant_items instead of catalog_items
                    $merchantItem = \App\Models\MerchantItem::where('catalog_item_id', $cartItem['item']['id'])
                        ->where('user_id', $cartItem['user_id'])
                        ->first();

                    if ($merchantItem) {
                        $merchantItem->stock = $cartItem['stock'];
                        $merchantItem->update();

                        // Send low stock notification for this merchant's listing
                        if ($merchantItem->stock <= 5) {
                            $catalogEvent = new CatalogEvent;
                            $catalogEvent->catalog_item_id = $merchantItem->catalog_item_id;
                            $catalogEvent->user_id = $merchantItem->user_id; // Add merchant context
                            $catalogEvent->save();
                        }
                    }
                }
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * Create MerchantPurchase records grouped by merchant
     *
     * CRITICAL FIX (2026-01-09):
     * - Groups cart items by merchant (not one record per item)
     * - Stores merchant-specific cart in each MerchantPurchase
     * - Calculates financial fields (commission, tax, shipping, net_amount)
     * - Properly sets payment_type, shipping_type, money_received_by
     *
     * @param mixed $cart The cart object with items
     * @param Purchase $purchase The main purchase record
     * @param array $checkoutData Optional checkout data with shipping/payment info
     */
    public static function merchant_purchase_check($cart, $purchase, $checkoutData = [])
    {
        try {
            $gs = \App\Models\Muaadhsetting::find(1);

            // Group cart items by merchant
            $merchantGroups = [];
            foreach ($cart->items as $key => $cartItem) {
                $merchantId = $cartItem['item']['user_id'] ?? $cartItem['user_id'] ?? 0;
                if ($merchantId != 0) {
                    if (!isset($merchantGroups[$merchantId])) {
                        $merchantGroups[$merchantId] = [
                            'items' => [],
                            'totalQty' => 0,
                            'totalPrice' => 0,
                        ];
                    }
                    $merchantGroups[$merchantId]['items'][$key] = $cartItem;
                    $merchantGroups[$merchantId]['totalQty'] += (int)($cartItem['qty'] ?? 1);
                    $merchantGroups[$merchantId]['totalPrice'] += (float)($cartItem['price'] ?? 0);
                }
            }

            // Get shipping choice data from purchase
            $customerShippingChoice = $purchase->customer_shipping_choice;
            if (is_string($customerShippingChoice)) {
                $customerShippingChoice = json_decode($customerShippingChoice, true) ?? [];
            }

            // Create one MerchantPurchase per merchant
            foreach ($merchantGroups as $merchantId => $merchantData) {
                // Calculate commission
                $itemsTotal = $merchantData['totalPrice'];
                $commissionAmount = 0;
                if ($gs) {
                    $commissionAmount = $gs->fixed_commission + ($itemsTotal * $gs->percentage_commission / 100);
                }

                // Calculate tax (proportional to merchant's share)
                $purchaseTax = (float)$purchase->tax;
                $cartTotalPrice = 0;
                foreach ($cart->items as $item) {
                    $cartTotalPrice += (float)($item['price'] ?? 0);
                }
                $taxAmount = $cartTotalPrice > 0 ? ($itemsTotal / $cartTotalPrice) * $purchaseTax : 0;

                // Get merchant-specific shipping choice
                $shippingChoice = $customerShippingChoice[$merchantId] ?? $customerShippingChoice[(string)$merchantId] ?? null;
                $shippingCost = 0;
                $shippingType = null;
                $courierId = null;
                $shippingId = null;

                if ($shippingChoice) {
                    $shippingCost = (float)($shippingChoice['price'] ?? 0);
                    $provider = $shippingChoice['provider'] ?? '';

                    if ($provider === 'tryoto' || $provider === 'shipping_company') {
                        $shippingType = 'platform'; // Third-party shipping
                        $shippingId = $shippingChoice['delivery_option_id'] ?? null;
                    } elseif ($provider === 'local_courier' || $provider === 'courier') {
                        $shippingType = 'courier';
                        $courierId = $shippingChoice['courier_id'] ?? null;
                    } elseif ($provider === 'pickup') {
                        $shippingType = 'pickup';
                    } else {
                        $shippingType = 'merchant'; // Merchant's own shipping
                        $shippingId = $shippingChoice['shipping_id'] ?? null;
                    }
                }

                // Determine payment type (COD = platform collects, online = depends on gateway)
                $paymentMethod = strtolower($purchase->method ?? '');
                $paymentType = 'platform'; // Default: platform handles payment
                $moneyReceivedBy = 'platform';

                if (strpos($paymentMethod, 'cod') !== false || strpos($paymentMethod, 'cash') !== false) {
                    // For COD, money is received by courier or merchant
                    if ($shippingType === 'courier') {
                        $moneyReceivedBy = 'courier';
                    } else {
                        $moneyReceivedBy = 'merchant';
                    }
                }

                // Calculate net amount (what merchant receives)
                $netAmount = $itemsTotal - $commissionAmount;

                $merchantPurchase = new MerchantPurchase();
                $merchantPurchase->purchase_id = $purchase->id;
                $merchantPurchase->user_id = $merchantId;
                $merchantPurchase->cart = $merchantData['items']; // Store merchant-specific cart
                $merchantPurchase->qty = $merchantData['totalQty'];
                $merchantPurchase->price = $itemsTotal;
                $merchantPurchase->purchase_number = $purchase->purchase_number;
                $merchantPurchase->status = 'pending';
                $merchantPurchase->commission_amount = $commissionAmount;
                $merchantPurchase->tax_amount = $taxAmount;
                $merchantPurchase->shipping_cost = $shippingCost;
                $merchantPurchase->packing_cost = 0; // TODO: Calculate if packing is used
                $merchantPurchase->courier_fee = $shippingType === 'courier' ? $shippingCost : 0;
                $merchantPurchase->net_amount = $netAmount;
                $merchantPurchase->payment_type = $paymentType;
                $merchantPurchase->shipping_type = $shippingType;
                $merchantPurchase->money_received_by = $moneyReceivedBy;
                $merchantPurchase->shipping_id = $shippingId;
                $merchantPurchase->courier_id = $courierId;
                $merchantPurchase->save();

                // Create notification for merchant
                $notification = new UserCatalogEvent;
                $notification->user_id = $merchantId;
                $notification->purchase_number = $purchase->purchase_number;
                $notification->save();
            }
        } catch (\Exception $e) {
            \Log::error('merchant_purchase_check error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public static function add_to_wallet_log($data, $price)
    {
        try {
            $walletLog = new WalletLog;
            $walletLog->txn_number = Str::random(3) . substr(time(), 6, 8) . Str::random(3);
            $walletLog->user_id = $data->user_id;
            $walletLog->amount = $price;
            $walletLog->currency_sign = $data->currency_sign;
            $walletLog->currency_code = $data->currency_name;
            $walletLog->currency_value = $data->currency_value;
            $walletLog->details = 'Payment Via Wallet';
            $walletLog->type = 'minus';
            $walletLog->save();
            $balance = $price;
            $user = $walletLog->user;
            $user->balance = $user->balance - $balance;
            $user->update();
        } catch (\Exception $e) {
        }
    }

    public static function mollie_currencies()
    {
        return array(
            'AED',
            'AUD',
            'BGN',
            'BRL',
            'CAD',
            'CHF',
            'CZK',
            'DKK',
            'EUR',
            'GBP',
            'HKD',
            'HRK',
            'HUF',
            'ILS',
            'ISK',
            'JPY',
            'MXN',
            'MYR',
            'NOK',
            'NZD',
            'PHP',
            'PLN',
            'RON',
            'RUB',
            'SEK',
            'SGD',
            'THB',
            'TWD',
            'USD',
            'ZAR'
        );
    }

    public static function flutter_currencies()
    {
        return array(
            'BIF',
            'CAD',
            'CDF',
            'CVE',
            'EUR',
            'GBP',
            'GHS',
            'GMD',
            'GNF',
            'KES',
            'LRD',
            'MWK',
            'NGN',
            'RWF',
            'SLL',
            'STD',
            'TZS',
            'UGX',
            'USD',
            'XAF',
            'XOF',
            'ZMK',
            'ZMW',
            'ZWD'
        );
    }

    public static function mercadopago_currencies()
    {
        return array(
            'ARS',
            'BRL',
            'CLP',
            'MXN',
            'PEN',
            'UYU',
            'VEF'
        );
    }
}
