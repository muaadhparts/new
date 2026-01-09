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
     * STRICT ARCHITECTURAL RULES (2026-01-09):
     * =========================================
     * 1. owner_id = 0 → Platform service (NEVER NULL)
     * 2. owner_id > 0 → Merchant/Other service
     * 3. Sales ALWAYS registered to merchant in MerchantPurchase
     *
     * COD PAYMENT RULES:
     * ==================
     * - COD + Courier shipping → payment_owner_id = 0 (money to platform via courier)
     * - COD + Shipping Company → payment_owner_id = shipping_owner_id (money to shipping owner)
     *
     * MONEY FLOW:
     * ===========
     * - payment_owner_id = 0 → Platform receives money → platform_owes_merchant
     * - payment_owner_id > 0 → Owner receives money → merchant_owes_platform
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

            $paymentMethod = strtolower($purchase->method ?? '');
            $isCOD = (strpos($paymentMethod, 'cod') !== false || strpos($paymentMethod, 'cash') !== false);

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
                $shippingData = self::processShippingChoice($shippingChoice, $merchantId);

                // Determine payment owner based on strict rules
                $paymentOwnerId = self::determinePaymentOwner($purchase, $checkoutData, $shippingData, $isCOD);
                $paymentType = ($paymentOwnerId === 0) ? 'platform' : 'merchant';

                // Determine who physically receives the money
                $moneyReceivedBy = self::determineMoneyReceiver($isCOD, $shippingData, $paymentOwnerId);

                // Calculate net amount (what merchant should receive after deductions)
                // IMPORTANT: Tax is NOT deducted from net - it's informational only!
                // Tax is a pass-through (collected from customer, paid to tax authority)
                // It should NOT affect merchant-platform settlements
                $netAmount = $itemsTotal - $commissionAmount;

                // Calculate platform services fees (only if platform provides the service)
                $platformShippingFee = ($shippingData['owner_id'] === 0) ? $shippingData['cost'] : 0;
                $platformPackingFee = 0; // Packing owner handled separately

                // Calculate financial balances based on payment owner
                // CRITICAL: Tax is NOT included in settlements!
                // Tax is accounting information, not money for platform or merchant
                $merchantOwesPlatform = 0;
                $platformOwesMerchant = 0;

                if ($paymentOwnerId > 0) {
                    // Payment owner receives money directly (including tax)
                    // Merchant owes platform ONLY: commission + platform services
                    // Tax is NOT owed - it's a pass-through to tax authority
                    $merchantOwesPlatform = $commissionAmount + $platformShippingFee + $platformPackingFee;
                } else {
                    // Platform receives money (payment_owner_id = 0)
                    // Platform owes merchant: net amount (sales minus commission)
                    // Tax is NOT part of this - platform collects tax separately for tax authority
                    $platformOwesMerchant = $netAmount;
                }

                $merchantPurchase = new MerchantPurchase();
                $merchantPurchase->purchase_id = $purchase->id;
                $merchantPurchase->user_id = $merchantId;
                $merchantPurchase->cart = $merchantData['items'];
                $merchantPurchase->qty = $merchantData['totalQty'];
                $merchantPurchase->price = $itemsTotal;
                $merchantPurchase->purchase_number = $purchase->purchase_number;
                $merchantPurchase->status = 'pending';
                $merchantPurchase->commission_amount = $commissionAmount;
                $merchantPurchase->tax_amount = $taxAmount;
                $merchantPurchase->shipping_cost = $shippingData['cost'];
                $merchantPurchase->packing_cost = 0;
                $merchantPurchase->courier_fee = ($shippingData['type'] === 'courier') ? $shippingData['cost'] : 0;
                $merchantPurchase->platform_shipping_fee = $platformShippingFee;
                $merchantPurchase->platform_packing_fee = $platformPackingFee;
                $merchantPurchase->net_amount = $netAmount;
                $merchantPurchase->merchant_owes_platform = $merchantOwesPlatform;
                $merchantPurchase->platform_owes_merchant = $platformOwesMerchant;
                $merchantPurchase->payment_type = $paymentType;
                $merchantPurchase->shipping_type = $shippingData['type'];
                $merchantPurchase->money_received_by = $moneyReceivedBy;
                $merchantPurchase->payment_owner_id = $paymentOwnerId;
                $merchantPurchase->shipping_owner_id = $shippingData['owner_id'];
                $merchantPurchase->packing_owner_id = 0; // Default to platform
                $merchantPurchase->shipping_id = $shippingData['shipping_id'];
                $merchantPurchase->courier_id = $shippingData['courier_id'];
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

    /**
     * Determine payment owner based on strict rules
     *
     * STRICT RULES:
     * =============
     * 1. Online payment → Check merchant credentials, else platform (0)
     * 2. COD + Courier → Platform (0) - Courier delivers to platform
     * 3. COD + Shipping Company → shipping_owner_id - Money to shipping owner
     *
     * @param Purchase $purchase
     * @param array $checkoutData
     * @param array $shippingData
     * @param bool $isCOD
     * @return int Owner user_id (0 = platform, > 0 = merchant/owner)
     */
    private static function determinePaymentOwner($purchase, array $checkoutData, array $shippingData, bool $isCOD): int
    {
        // COD has special rules based on shipping type
        if ($isCOD) {
            // Rule: COD + Courier → money always goes to platform (0)
            if ($shippingData['type'] === 'courier') {
                return 0; // Platform receives via courier
            }

            // Rule: COD + Shipping Company → money goes to shipping owner
            // (could be platform or merchant depending on who owns the shipping service)
            return $shippingData['owner_id'];
        }

        // Online payment - check if merchant has their own payment gateway
        $paymentMethod = strtolower($purchase->method ?? '');

        // Check if merchant-specific payment gateway was used
        $merchantPaymentGatewayId = $checkoutData['merchant_payment_gateway_id'] ?? 0;
        if ($merchantPaymentGatewayId > 0) {
            $credential = \App\Models\MerchantCredential::where('id', $merchantPaymentGatewayId)
                ->where('is_active', true)
                ->first();

            if ($credential && $credential->user_id > 0) {
                return $credential->user_id; // Merchant owns this gateway
            }
        }

        // Check for merchant payment info in checkout data
        $merchantId = $checkoutData['merchant_id'] ?? 0;
        if ($merchantId > 0) {
            $serviceName = self::getPaymentServiceName($paymentMethod);
            $hasMerchantCredentials = \App\Models\MerchantCredential::where('user_id', $merchantId)
                ->where('service_name', $serviceName)
                ->where('is_active', true)
                ->exists();

            if ($hasMerchantCredentials) {
                return $merchantId; // Merchant receives money directly
            }
        }

        // Default: Platform gateway (0)
        return 0;
    }

    /**
     * Determine who physically receives the money
     *
     * @param bool $isCOD
     * @param array $shippingData
     * @param int $paymentOwnerId
     * @return string 'platform', 'merchant', or 'courier'
     */
    private static function determineMoneyReceiver(bool $isCOD, array $shippingData, int $paymentOwnerId): string
    {
        if ($isCOD) {
            // COD: physical money receiver depends on delivery method
            if ($shippingData['type'] === 'courier') {
                return 'courier'; // Courier collects and delivers to platform
            }
            // Shipping company collects
            return ($shippingData['owner_id'] === 0) ? 'platform' : 'merchant';
        }

        // Online payment: receiver is the payment owner
        return ($paymentOwnerId === 0) ? 'platform' : 'merchant';
    }

    /**
     * Get payment service name from payment method
     */
    private static function getPaymentServiceName(string $paymentMethod): string
    {
        $methodMap = [
            'myfatoorah' => 'myfatoorah',
            'stripe' => 'stripe',
            'paypal' => 'paypal',
            'razorpay' => 'razorpay',
            'tap' => 'tap',
            'moyasar' => 'moyasar',
        ];

        foreach ($methodMap as $key => $service) {
            if (strpos($paymentMethod, $key) !== false) {
                return $service;
            }
        }

        return $paymentMethod;
    }

    /**
     * Process shipping choice to determine owner and type
     *
     * STRICT RULE: owner_id = 0 → Platform, owner_id > 0 → Owner
     * NOTE: NULL is NEVER returned - always 0 for platform
     *
     * @param array|null $shippingChoice
     * @param int $merchantId
     * @return array ['type', 'cost', 'owner_id', 'shipping_id', 'courier_id']
     */
    private static function processShippingChoice($shippingChoice, int $merchantId): array
    {
        $result = [
            'type' => 'platform', // Default type
            'cost' => 0,
            'owner_id' => 0, // Default: platform (NEVER NULL)
            'shipping_id' => 0,
            'courier_id' => 0,
        ];

        if (!$shippingChoice) {
            return $result;
        }

        $result['cost'] = (float)($shippingChoice['price'] ?? 0);
        $provider = $shippingChoice['provider'] ?? '';

        if ($provider === 'tryoto' || $provider === 'shipping_company') {
            // Third-party shipping (Tryoto) - Platform service
            $result['type'] = 'shipping_company';
            $result['owner_id'] = 0; // Platform owns Tryoto integration
            $result['shipping_id'] = (int)($shippingChoice['delivery_option_id'] ?? 0);
        } elseif ($provider === 'local_courier' || $provider === 'courier') {
            // Local courier
            $result['type'] = 'courier';
            $result['courier_id'] = (int)($shippingChoice['courier_id'] ?? 0);

            // Courier owner from database
            if ($result['courier_id'] > 0) {
                $courier = \App\Models\Courier::find($result['courier_id']);
                $result['owner_id'] = $courier ? (int)($courier->user_id ?? 0) : 0;
            }
        } elseif ($provider === 'pickup') {
            // Pickup - Merchant handles, no shipping cost
            $result['type'] = 'pickup';
            $result['owner_id'] = $merchantId;
            $result['cost'] = 0;
        } else {
            // Check Shipping table to determine owner
            $shippingId = (int)($shippingChoice['shipping_id'] ?? 0);
            if ($shippingId > 0) {
                $shipping = \App\Models\Shipping::find($shippingId);
                if ($shipping) {
                    $result['owner_id'] = (int)($shipping->user_id ?? 0);
                    $result['type'] = ($result['owner_id'] === 0) ? 'platform' : 'merchant';
                    $result['shipping_id'] = $shippingId;
                } else {
                    // Shipping record not found - default to merchant
                    $result['type'] = 'merchant';
                    $result['owner_id'] = $merchantId;
                }
            } else {
                // No shipping ID - default to merchant
                $result['type'] = 'merchant';
                $result['owner_id'] = $merchantId;
            }
        }

        return $result;
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
