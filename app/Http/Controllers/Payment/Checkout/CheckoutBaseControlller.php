<?php

namespace App\Http\Controllers\Payment\Checkout;

use App\Http\Controllers\Controller;
use DB;
use App;
use Session;

class CheckoutBaseControlller extends Controller
{
    protected $gs;
    protected $ps;
    protected $curr;
    protected $language;

    public function __construct()
    {

        $this->gs = DB::table('muaadhsettings')->find(1);

        $this->ps = DB::table('pagesettings')->find(1);

        $this->middleware(function ($request, $next) {

            if (Session::has('language'))
            {
                $this->language = DB::table('languages')->find(Session::get('language'));
            }
            else
            {
                $this->language = DB::table('languages')->where('is_default','=',1)->first();
            }  

            App::setlocale($this->language->name);
            view()->share('langg', $this->language);
            if (Session::has('currency')) {
                $this->curr = DB::table('currencies')->find(Session::get('currency'));
            }
            else {
                $this->curr = DB::table('currencies')->where('is_default','=',1)->first();
            }
    
            return $next($request);
        });
    }

    /**
     * Prepare purchase data using total from step3 (no recalculation)
     * Ensures merchant isolation and correct total handling
     *
     * @param array $input Request input data
     * @param \App\Models\Cart $cart Cart object
     * @return array Prepared purchase data
     */
    protected function prepareOrderData($input, $cart)
    {
        // ✅ استخدام المبلغ من step3 مباشرة (لا إعادة حساب)
        $purchaseTotal = (float)($input['total'] ?? 0) / $this->curr->value;

        // ✅ حفظ طريقة الشحن الأصلية (shipto/pickup) قبل أي معالجة
        $step1 = Session::get('step1', []);
        $originalShippingMethod = $step1['shipping'] ?? 'shipto';

        // إذا كان shipping string (shipto/pickup) وليس array، نحفظه
        if (isset($input['shipping']) && is_string($input['shipping']) && in_array($input['shipping'], ['shipto', 'pickup'])) {
            $originalShippingMethod = $input['shipping'];
        }

        // تحضير merchant_ids من السلة
        $merchant_ids = [];
        foreach ($cart->items as $item) {
            $merchantId = $item['item']['user_id'] ?? 0;
            if (!in_array($merchantId, $merchant_ids)) {
                $merchant_ids[] = $merchantId;
            }
        }
        $input['merchant_ids'] = json_encode($merchant_ids);

        // تحضير بيانات الشحن والتغليف
        if ($this->gs->multiple_shipping == 0) {
            // Single shipping
            if (!isset($input['merchant_shipping_ids'])) {
                $input['merchant_shipping_ids'] = json_encode([]);
            } elseif (is_array($input['merchant_shipping_ids'])) {
                $input['merchant_shipping_ids'] = json_encode($input['merchant_shipping_ids']);
            }

            if (!isset($input['merchant_packing_ids'])) {
                $input['merchant_packing_ids'] = json_encode([]);
            } elseif (is_array($input['merchant_packing_ids'])) {
                $input['merchant_packing_ids'] = json_encode($input['merchant_packing_ids']);
            }
        } else {
            // Multi shipping
            // Shipping
            if (isset($input['shipping']) && is_array($input['shipping'])) {
                $input['merchant_shipping_ids'] = json_encode($input['shipping']);
                $input['shipping_title'] = json_encode($input['shipping']);
                $input['merchant_shipping_id'] = json_encode($input['shipping']);
            } elseif (isset($input['merchant_shipping_ids']) && is_array($input['merchant_shipping_ids'])) {
                $input['merchant_shipping_ids'] = json_encode($input['merchant_shipping_ids']);
                $input['shipping_title'] = $input['merchant_shipping_ids'];
                $input['merchant_shipping_id'] = $input['merchant_shipping_ids'];
            } else {
                $input['merchant_shipping_ids'] = json_encode([]);
                $input['shipping_title'] = json_encode([]);
                $input['merchant_shipping_id'] = json_encode([]);
            }

            // Packing
            if (isset($input['packeging']) && is_array($input['packeging'])) {
                $input['merchant_packing_ids'] = json_encode($input['packeging']);
                $input['packing_title'] = json_encode($input['packeging']);
                $input['merchant_packing_id'] = json_encode($input['packeging']);
            } elseif (isset($input['merchant_packing_ids']) && is_array($input['merchant_packing_ids'])) {
                $input['merchant_packing_ids'] = json_encode($input['merchant_packing_ids']);
                $input['packing_title'] = $input['merchant_packing_ids'];
                $input['merchant_packing_id'] = $input['merchant_packing_ids'];
            } else {
                $input['merchant_packing_ids'] = json_encode([]);
                $input['packing_title'] = json_encode([]);
                $input['merchant_packing_id'] = json_encode([]);
            }

            unset($input['packeging']);
        }

        // ✅ إعادة تعيين قيمة shipping الأصلية (shipto/pickup) للعرض في الفاتورة
        $input['shipping'] = $originalShippingMethod;

        // ✅ حفظ بيانات شركة الشحن المختارة من العميل (Tryoto وغيرها)
        // نمرر step2 من الجلسة
        $step2 = Session::get('step2', []);
        $input['customer_shipping_choice'] = $this->extractCustomerShippingChoice($step2);

        return [
            'input' => $input,
            'order_total' => $purchaseTotal,
        ];
    }

    /**
     * Extract customer's shipping choice data from step2
     * Parses Tryoto format: "deliveryOptionId#companyName#price"
     *
     * @param array $step2Data Step2 session data
     * @param int|null $merchantId Merchant ID for merchant-specific checkout
     * @param bool $isMerchantCheckout Whether this is merchant-specific checkout
     * @return string JSON encoded shipping choices per merchant
     */
    protected function extractCustomerShippingChoice($step2Data, $merchantId = null, $isMerchantCheckout = false)
    {
        $choices = [];

        // Get shipping selections from step2 data
        $shippingSelections = $step2Data['shipping'] ?? [];

        // ✅ Get free shipping info from step2
        $isFreeShipping = $step2Data['is_free_shipping'] ?? false;
        $originalShippingCost = $step2Data['original_shipping_cost'] ?? 0;
        $freeShippingDiscount = $step2Data['free_shipping_discount'] ?? 0;

        // For merchant checkout, the shipping might be stored directly
        if ($isMerchantCheckout && $merchantId) {
            // Check if shipping is stored as merchant_id => value
            if (isset($shippingSelections[$merchantId])) {
                $shippingValue = $shippingSelections[$merchantId];
            } elseif (!is_array($shippingSelections)) {
                // Shipping value stored directly
                $shippingValue = $shippingSelections;
                $shippingSelections = [$merchantId => $shippingValue];
            }
        }

        if (!is_array($shippingSelections)) {
            // If single value, try to use it with merchantId
            if ($merchantId && !empty($shippingSelections)) {
                $shippingSelections = [$merchantId => $shippingSelections];
            } else {
                return json_encode($choices);
            }
        }

        foreach ($shippingSelections as $mid => $shippingValue) {
            if (empty($shippingValue)) continue;

            // Check if it's Tryoto format: "deliveryOptionId#companyName#price"
            if (is_string($shippingValue) && strpos($shippingValue, '#') !== false) {
                $parts = explode('#', $shippingValue);

                if (count($parts) >= 3) {
                    $originalPrice = (float) $parts[2];

                    // ✅ Check if this merchant qualifies for free shipping
                    $merchantFreeShipping = $this->checkMerchantFreeShipping($mid, $originalPrice);

                    $choices[$mid] = [
                        'provider' => 'tryoto',
                        'delivery_option_id' => $parts[0],
                        'company_name' => $parts[1],
                        'price' => $merchantFreeShipping['is_free'] ? 0 : $originalPrice,
                        'original_price' => $originalPrice,
                        'is_free_shipping' => $merchantFreeShipping['is_free'],
                        'free_shipping_reason' => $merchantFreeShipping['reason'] ?? null,
                        'selected_at' => now()->toIso8601String(),
                    ];
                }
            } elseif (is_numeric($shippingValue)) {
                // Regular shipping ID (manual/debts)
                $shipping = \DB::table('shippings')->find($shippingValue);

                if ($shipping) {
                    $originalPrice = (float) ($shipping->price ?? 0);

                    // ✅ Check if this merchant qualifies for free shipping
                    $merchantFreeShipping = $this->checkMerchantFreeShipping($mid, $originalPrice, $shippingValue);

                    $choices[$mid] = [
                        'provider' => $shipping->provider ?? 'manual',
                        'shipping_id' => (int) $shippingValue,
                        'title' => $shipping->title ?? '',
                        'price' => $merchantFreeShipping['is_free'] ? 0 : $originalPrice,
                        'original_price' => $originalPrice,
                        'is_free_shipping' => $merchantFreeShipping['is_free'],
                        'free_shipping_reason' => $merchantFreeShipping['reason'] ?? null,
                        'selected_at' => now()->toIso8601String(),
                    ];
                }
            }
        }

        // Return array (Model will handle JSON encoding via cast)
        return !empty($choices) ? $choices : null;
    }

    /**
     * Check if merchant qualifies for free shipping based on free_above threshold
     *
     * @param int $merchantId Merchant ID
     * @param float $shippingPrice Original shipping price
     * @param int|null $shippingId Shipping method ID (for manual/debts)
     * @return array ['is_free' => bool, 'reason' => string|null]
     */
    protected function checkMerchantFreeShipping($merchantId, $shippingPrice, $shippingId = null)
    {
        // Get cart from session
        $cart = Session::get('cart');
        if (!$cart || empty($cart->items)) {
            return ['is_free' => false, 'reason' => null];
        }

        // Calculate merchant's items total
        $merchantItemsTotal = 0;
        foreach ($cart->items as $item) {
            $itemMerchantId = $item['item']['user_id'] ?? $item['item']['merchant_user_id'] ?? 0;
            if ($itemMerchantId == $merchantId) {
                $merchantItemsTotal += (float)($item['price'] ?? 0);
            }
        }

        // Get free_above from shipping method
        $freeAbove = 0;
        if ($shippingId) {
            $shipping = \DB::table('shippings')->find($shippingId);
            $freeAbove = (float)($shipping->free_above ?? 0);
        } else {
            // For Tryoto, check merchant's Tryoto shipping entry
            $merchantTryotoShipping = \DB::table('shippings')
                ->where('user_id', $merchantId)
                ->where('provider', 'tryoto')
                ->first();
            $freeAbove = (float)($merchantTryotoShipping->free_above ?? 0);
        }

        // Check if qualifies for free shipping
        if ($freeAbove > 0 && $merchantItemsTotal >= $freeAbove) {
            return [
                'is_free' => true,
                'reason' => "Purchase total ({$merchantItemsTotal}) exceeds free shipping threshold ({$freeAbove})"
            ];
        }

        return ['is_free' => false, 'reason' => null];
    }
}