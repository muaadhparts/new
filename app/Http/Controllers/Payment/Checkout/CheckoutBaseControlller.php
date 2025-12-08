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

        $this->gs = DB::table('generalsettings')->find(1);

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
     * Prepare order data using total from step3 (no recalculation)
     * Ensures vendor isolation and correct total handling
     *
     * @param array $input Request input data
     * @param \App\Models\Cart $cart Cart object
     * @return array Prepared order data
     */
    protected function prepareOrderData($input, $cart)
    {
        // ✅ استخدام المبلغ من step3 مباشرة (لا إعادة حساب)
        $orderTotal = (float)($input['total'] ?? 0) / $this->curr->value;

        // ✅ حفظ طريقة الشحن الأصلية (shipto/pickup) قبل أي معالجة
        $step1 = Session::get('step1', []);
        $originalShippingMethod = $step1['shipping'] ?? 'shipto';

        // إذا كان shipping string (shipto/pickup) وليس array، نحفظه
        if (isset($input['shipping']) && is_string($input['shipping']) && in_array($input['shipping'], ['shipto', 'pickup'])) {
            $originalShippingMethod = $input['shipping'];
        }

        // تحضير vendor_ids من السلة
        $vendor_ids = [];
        foreach ($cart->items as $item) {
            $vid = $item['item']['user_id'] ?? 0;
            if (!in_array($vid, $vendor_ids)) {
                $vendor_ids[] = $vid;
            }
        }
        $input['vendor_ids'] = json_encode($vendor_ids);

        // تحضير بيانات الشحن والتغليف
        if ($this->gs->multiple_shipping == 0) {
            // Single shipping
            $input['is_shipping'] = 0;

            if (!isset($input['vendor_shipping_ids'])) {
                $input['vendor_shipping_ids'] = json_encode([]);
            } elseif (is_array($input['vendor_shipping_ids'])) {
                $input['vendor_shipping_ids'] = json_encode($input['vendor_shipping_ids']);
            }

            if (!isset($input['vendor_packing_ids'])) {
                $input['vendor_packing_ids'] = json_encode([]);
            } elseif (is_array($input['vendor_packing_ids'])) {
                $input['vendor_packing_ids'] = json_encode($input['vendor_packing_ids']);
            }
        } else {
            // Multi shipping
            $input['is_shipping'] = 1;

            // Shipping
            if (isset($input['shipping']) && is_array($input['shipping'])) {
                $input['vendor_shipping_ids'] = json_encode($input['shipping']);
                $input['shipping_title'] = json_encode($input['shipping']);
                $input['vendor_shipping_id'] = json_encode($input['shipping']);
            } elseif (isset($input['vendor_shipping_ids']) && is_array($input['vendor_shipping_ids'])) {
                $input['vendor_shipping_ids'] = json_encode($input['vendor_shipping_ids']);
                $input['shipping_title'] = $input['vendor_shipping_ids'];
                $input['vendor_shipping_id'] = $input['vendor_shipping_ids'];
            } else {
                $input['vendor_shipping_ids'] = json_encode([]);
                $input['shipping_title'] = json_encode([]);
                $input['vendor_shipping_id'] = json_encode([]);
            }

            // Packing
            if (isset($input['packeging']) && is_array($input['packeging'])) {
                $input['vendor_packing_ids'] = json_encode($input['packeging']);
                $input['packing_title'] = json_encode($input['packeging']);
                $input['vendor_packing_id'] = json_encode($input['packeging']);
            } elseif (isset($input['vendor_packing_ids']) && is_array($input['vendor_packing_ids'])) {
                $input['vendor_packing_ids'] = json_encode($input['vendor_packing_ids']);
                $input['packing_title'] = $input['vendor_packing_ids'];
                $input['vendor_packing_id'] = $input['vendor_packing_ids'];
            } else {
                $input['vendor_packing_ids'] = json_encode([]);
                $input['packing_title'] = json_encode([]);
                $input['vendor_packing_id'] = json_encode([]);
            }

            unset($input['packeging']);
        }

        // ✅ إعادة تعيين قيمة shipping الأصلية (shipto/pickup) للعرض في الفاتورة
        $input['shipping'] = $originalShippingMethod;

        // ✅ حفظ بيانات شركة الشحن المختارة من العميل (Tryoto وغيرها)
        $input['customer_shipping_choice'] = $this->extractCustomerShippingChoice($input);

        return [
            'input' => $input,
            'order_total' => $orderTotal,
        ];
    }

    /**
     * Extract customer's shipping choice data from input
     * Parses Tryoto format: "deliveryOptionId#companyName#price"
     *
     * @param array $input
     * @return string JSON encoded shipping choices per vendor
     */
    protected function extractCustomerShippingChoice($input)
    {
        $choices = [];
        $step2 = Session::get('step2', []);

        // Get shipping selections from step2 session or input
        $shippingSelections = $step2['shipping'] ?? $input['shipping'] ?? [];

        if (!is_array($shippingSelections)) {
            return json_encode($choices);
        }

        foreach ($shippingSelections as $vendorId => $shippingValue) {
            if (empty($shippingValue)) continue;

            // Check if it's Tryoto format: "deliveryOptionId#companyName#price"
            if (is_string($shippingValue) && strpos($shippingValue, '#') !== false) {
                $parts = explode('#', $shippingValue);

                if (count($parts) >= 3) {
                    $choices[$vendorId] = [
                        'provider' => 'tryoto',
                        'delivery_option_id' => $parts[0],
                        'company_name' => $parts[1],
                        'price' => (float) $parts[2],
                        'selected_at' => now()->toIso8601String(),
                    ];
                }
            } elseif (is_numeric($shippingValue)) {
                // Regular shipping ID (manual/debts)
                $shipping = \DB::table('shippings')->find($shippingValue);

                if ($shipping) {
                    $choices[$vendorId] = [
                        'provider' => $shipping->provider ?? 'manual',
                        'shipping_id' => (int) $shippingValue,
                        'title' => $shipping->title ?? '',
                        'price' => (float) ($shipping->price ?? 0),
                        'selected_at' => now()->toIso8601String(),
                    ];
                }
            }
        }

        return json_encode($choices);
    }
}