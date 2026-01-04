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

    public static function license_check($cart)
    {

        foreach ($cart->items as $key => $cartItem) {
            if (!empty($cartItem['item']['license']) && !empty($cartItem['item']['license_qty'])) {
                foreach ($cartItem['item']['license_qty'] as $ttl => $dtl) {
                    if ($dtl != 0) {
                        $dtl--;
                        $catalogItem = CatalogItem::find($cartItem['item']['id']);
                        $temp = $catalogItem->license_qty;
                        $temp[$ttl] = $dtl;
                        $final = implode(',', $temp);
                        $catalogItem->license_qty = $final;
                        $catalogItem->update();
                        $temp =  $catalogItem->license;
                        $license = $temp[$ttl];
                        $oldCart = Session::has('cart') ? Session::get('cart') : null;
                        $cart = new Cart($oldCart);
                        $cart->updateLicense($cartItem['item']['id'], $license);

                        Session::put('cart', $cart);
                        break;
                    }
                }
            }
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


    public static function affilate_check($id, $sub, $dp)
    {
        try {
            $user = User::find($id);
            if ($dp == 1) {
                $referral_commission = new ReferralCommission();
                $referral_commission->refer_id = $user->id;
                $referral_commission->bonus = $sub;
                $referral_commission->type = 'Purchase';
                if (Auth::user()) {
                    $referral_commission->refer_id = Auth::user()->id;
                }
                $referral_commission->save();
                $user->affilate_income += $sub;
                $user->update();
            }
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

    public static function merchant_purchase_check($cart, $purchase)
    {

        try {
            $notf = array();

            foreach ($cart->items as $cartItem) {
                if ($cartItem['item']['user_id'] != 0) {
                    $merchantPurchase =  new MerchantPurchase();
                    $merchantPurchase->purchase_id = $purchase->id;
                    $merchantPurchase->user_id = $cartItem['item']['user_id'];
                    $merchantPurchase->qty = $cartItem['qty'];
                    $merchantPurchase->price = $cartItem['price'];
                    $merchantPurchase->purchase_number = $purchase->purchase_number;
                    $merchantPurchase->save();
                    $notf[] = $cartItem['item']['user_id'];
                }
            }

            if (!empty($notf)) {
                $users = array_unique($notf);
                foreach ($users as $user) {
                    $notification = new UserCatalogEvent;
                    $notification->user_id = $user;
                    $notification->purchase_number = $purchase->purchase_number;
                    $notification->save();
                }
            }
        } catch (\Exception $e) {
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
