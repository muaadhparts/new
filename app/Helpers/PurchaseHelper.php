<?php

namespace App\Helpers;

use App\Domain\Identity\Models\User;
use App\Domain\Catalog\Models\CatalogEvent;
use App\Domain\Catalog\Models\UserCatalogEvent;
use App\Domain\Commerce\Models\MerchantPurchase;
use App\Domain\Merchant\Models\MerchantCommission;
use App\Domain\Accounting\Models\ReferralCommission;
use App\Domain\Accounting\Services\PaymentAccountingService;
use App\Domain\Accounting\Services\AccountLedgerService;
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

    /**
     * Check affiliate users for cart items
     * NEW CART SYSTEM ONLY - No fallbacks
     *
     * @param array $cartItems Cart items from MerchantCartManager->getItems()
     * @return array|null
     * @throws \InvalidArgumentException If cart items format is invalid
     */
    public static function item_affilate_check(array $cartItems): ?array
    {
        if (!is_array($cartItems)) {
            throw new \InvalidArgumentException('Cart items must be array from MerchantCartManager->getItems()');
        }

        $affilate_users = null;
        $i = 0;
        $percentage = setting('affilate_charge', 0) / 100;

        foreach ($cartItems as $key => $cartItem) {
            // NEW FORMAT ONLY - No fallbacks
            if (!isset($cartItem['catalog_item_id'])) {
                throw new \InvalidArgumentException("Cart item '{$key}' missing required field: catalog_item_id");
            }
            if (!isset($cartItem['total_price'])) {
                throw new \InvalidArgumentException("Cart item '{$key}' missing required field: total_price");
            }

            $affiliateUser = $cartItem['affiliate_user_id'] ?? 0;
            $catalogItemId = $cartItem['catalog_item_id'];
            $price = $cartItem['total_price'];

            if ($affiliateUser != 0) {
                if (Auth::check() && Auth::user()->id != $affiliateUser) {
                    $affilate_users[$i]['user_id'] = $affiliateUser;
                    $affilate_users[$i]['catalog_item_id'] = $catalogItemId;
                    $affilate_users[$i]['charge'] = $price * $percentage;
                    $i++;
                }
            }
        }
        return $affilate_users;
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

    public static function stock_check($cart)
    {
        try {
            foreach ($cart->items as $cartItem) {
                $x = (string)$cartItem['stock'];
                if ($x != null) {
                    // Update stock in merchant_items instead of catalog_items
                    $merchantItem = \App\Domain\Merchant\Models\MerchantItem::where('catalog_item_id', $cartItem['item']['id'])
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
            // Group cart items by merchant
            // FAIL-FAST: Required fields must exist
            $merchantGroups = [];
            foreach ($cart->items as $key => $cartItem) {
                // FAIL-FAST: merchant_id must exist
                if (!isset($cartItem['merchant_id']) && !isset($cartItem['user_id']) && !isset($cartItem['item']['user_id'])) {
                    throw new \RuntimeException(
                        "Cart item '{$key}' missing required field: merchant_id. " .
                        "Purchase ID: {$purchase->id}"
                    );
                }
                $merchantId = (int)($cartItem['merchant_id'] ?? $cartItem['user_id'] ?? $cartItem['item']['user_id']);
                if ($merchantId <= 0) {
                    throw new \RuntimeException(
                        "Cart item '{$key}' has invalid merchant_id: {$merchantId}. " .
                        "Purchase ID: {$purchase->id}"
                    );
                }

                // FAIL-FAST: qty must exist
                if (!isset($cartItem['qty'])) {
                    throw new \RuntimeException(
                        "Cart item '{$key}' missing required field: qty. " .
                        "Purchase ID: {$purchase->id}"
                    );
                }

                // FAIL-FAST: price must exist (total_price or price)
                if (!isset($cartItem['total_price']) && !isset($cartItem['price'])) {
                    throw new \RuntimeException(
                        "Cart item '{$key}' missing required field: total_price. " .
                        "Purchase ID: {$purchase->id}"
                    );
                }

                if (!isset($merchantGroups[$merchantId])) {
                    $merchantGroups[$merchantId] = [
                        'items' => [],
                        'totalQty' => 0,
                        'totalPrice' => 0,
                    ];
                }
                $merchantGroups[$merchantId]['items'][$key] = $cartItem;
                $merchantGroups[$merchantId]['totalQty'] += (int)$cartItem['qty'];
                $merchantGroups[$merchantId]['totalPrice'] += (float)($cartItem['total_price'] ?? $cartItem['price']);
            }

            // Get shipping choice data from purchase
            $customerShippingChoice = $purchase->customer_shipping_choice;
            if (is_string($customerShippingChoice)) {
                $customerShippingChoice = json_decode($customerShippingChoice, true) ?? [];
            }

            $paymentMethod = strtolower($purchase->method ?? '');
            $isCOD = (strpos($paymentMethod, 'cod') !== false || strpos($paymentMethod, 'cash') !== false);

            // Get accounting service
            $accountingService = app(PaymentAccountingService::class);

            // Create one MerchantPurchase per merchant
            foreach ($merchantGroups as $merchantId => $merchantData) {
                // Calculate commission (per-merchant from merchant_commissions table)
                $itemsTotal = $merchantData['totalPrice'];
                $merchantCommission = MerchantCommission::getOrCreateForMerchant($merchantId);
                $commissionAmount = $merchantCommission->calculateCommission($itemsTotal);

                // Calculate tax (proportional to merchant's share)
                $purchaseTax = (float)$purchase->tax;
                $cartTotalPrice = 0;
                foreach ($cart->items as $itemKey => $item) {
                    // Already validated above - use total_price or price
                    if (!isset($item['total_price']) && !isset($item['price'])) {
                        throw new \RuntimeException(
                            "Cart item '{$itemKey}' missing required field: total_price. " .
                            "Purchase ID: {$purchase->id}"
                        );
                    }
                    $cartTotalPrice += (float)($item['total_price'] ?? $item['price']);
                }
                $taxAmount = $cartTotalPrice > 0 ? ($itemsTotal / $cartTotalPrice) * $purchaseTax : 0;

                // Get merchant-specific shipping choice
                $shippingChoice = $customerShippingChoice[$merchantId] ?? $customerShippingChoice[(string)$merchantId] ?? null;
                $shippingData = self::processShippingChoice($shippingChoice, $merchantId);

                // Determine payment owner based on strict rules
                $paymentOwnerId = self::determinePaymentOwner($purchase, $checkoutData, $shippingData, $isCOD);
                $paymentType = ($paymentOwnerId === 0) ? 'platform' : 'merchant';

                // Calculate net amount (what merchant should receive after deductions)
                $netAmount = $itemsTotal - $commissionAmount;

                // Determine delivery method for accounting service
                $deliveryMethod = match ($shippingData['type']) {
                    'courier' => MerchantPurchase::DELIVERY_LOCAL_COURIER,
                    'platform', 'merchant' => MerchantPurchase::DELIVERY_SHIPPING_COMPANY,
                    'pickup' => MerchantPurchase::DELIVERY_PICKUP,
                    default => MerchantPurchase::DELIVERY_NONE,
                };

                // Determine delivery provider
                $deliveryProvider = ($shippingData['type'] === 'courier')
                    ? 'courier_' . ($shippingData['courier_id'] ?? 0)
                    : ($shippingChoice['provider'] ?? null);

                // Calculate platform services fees (only if platform provides the service)
                $platformShippingFee = ($shippingData['owner_id'] === 0) ? $shippingData['cost'] : 0;

                // === Calculate Debt Ledger via Accounting Service ===
                $accountingData = $accountingService->calculateDebtLedger([
                    'payment_method' => $isCOD ? 'cod' : 'online',
                    'payment_owner_id' => $paymentOwnerId,
                    'delivery_method' => $deliveryMethod,
                    'delivery_provider' => $deliveryProvider,
                    'price' => $itemsTotal,
                    'commission_amount' => $commissionAmount,
                    'tax_amount' => $taxAmount,
                    'shipping_cost' => $shippingData['cost'],
                    'courier_fee' => ($shippingData['type'] === 'courier') ? $shippingData['cost'] : 0,
                    'platform_shipping_fee' => $platformShippingFee,
                ]);

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
                $merchantPurchase->courier_fee = ($shippingData['type'] === 'courier') ? $shippingData['cost'] : 0;
                $merchantPurchase->platform_shipping_fee = $platformShippingFee;
                $merchantPurchase->net_amount = $netAmount;
                $merchantPurchase->payment_type = $paymentType;
                $merchantPurchase->shipping_type = $shippingData['type'];
                $merchantPurchase->payment_owner_id = $paymentOwnerId;
                $merchantPurchase->shipping_owner_id = $shippingData['owner_id'];
                $merchantPurchase->shipping_id = $shippingData['shipping_id'];
                $merchantPurchase->courier_id = $shippingData['courier_id'];

                // === Accounting Fields from Service ===
                $merchantPurchase->money_holder = $accountingData['money_holder'];
                $merchantPurchase->delivery_method = $accountingData['delivery_method'];
                $merchantPurchase->delivery_provider = $accountingData['delivery_provider'];
                $merchantPurchase->cod_amount = $accountingData['cod_amount'];
                $merchantPurchase->collection_status = $accountingData['collection_status'];

                // === Debt Ledger from Service ===
                $merchantPurchase->platform_owes_merchant = $accountingData['platform_owes_merchant'];
                $merchantPurchase->merchant_owes_platform = $accountingData['merchant_owes_platform'];
                $merchantPurchase->courier_owes_platform = $accountingData['courier_owes_platform'];
                $merchantPurchase->shipping_company_owes_merchant = $accountingData['shipping_company_owes_merchant'];
                $merchantPurchase->shipping_company_owes_platform = $accountingData['shipping_company_owes_platform'];

                $merchantPurchase->settlement_status = 'pending';
                $merchantPurchase->save();

                // === Record Ledger Entries ===
                try {
                    $ledgerService = app(AccountLedgerService::class);
                    $ledgerService->recordDebtsForMerchantPurchase($merchantPurchase);
                } catch (\Exception $e) {
                    \Log::warning('Failed to record ledger entries for purchase #' . $merchantPurchase->purchase_number . ': ' . $e->getMessage());
                }

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
            $credential = \App\Domain\Merchant\Models\MerchantCredential::where('id', $merchantPaymentGatewayId)
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
            $hasMerchantCredentials = \App\Domain\Merchant\Models\MerchantCredential::where('user_id', $merchantId)
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
            // NOTE: shipping_type ENUM only allows: merchant, platform, courier, pickup
            // Tryoto/shipping companies use 'platform' since platform owns the integration
            $result['type'] = 'platform';
            $result['owner_id'] = 0; // Platform owns Tryoto integration
            $result['shipping_id'] = (int)($shippingChoice['delivery_option_id'] ?? 0);
        } elseif ($provider === 'local_courier' || $provider === 'courier') {
            // Local courier
            $result['type'] = 'courier';
            $result['courier_id'] = (int)($shippingChoice['courier_id'] ?? 0);

            // Courier owner from database
            if ($result['courier_id'] > 0) {
                $courier = \App\Domain\Identity\Models\Courier::find($result['courier_id']);
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
                $shipping = \App\Domain\Shipping\Models\Shipping::find($shippingId);
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

    public static function mollieAcceptedCodes()
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

    public static function flutterwaveAcceptedCodes()
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

    public static function mercadopagoAcceptedCodes()
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
