<?php

namespace App\Traits;

use App\Helpers\PriceHelper;
use App\Models\Order;
use App\Models\User;
use App\Models\City;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

trait CreatesTryotoShipments
{
    /**
     * Create Tryoto shipment(s) after the order is successfully created.
     * Store the results in vendor_shipping_id as JSON, and update the shipping/shipping_title for the view.
     *
     * @param Order $order
     * @param array $input
     * @return void
     */
    protected function createOtoShipments(Order $order, array $input): void
    {
        // Check shipping selection — supports array (multi-vendor) or single-value scenarios
        $shippingInput = $input['shipping'] ?? $input['vendor_shipping_id'] ?? null;
        if (!$shippingInput) {
            return;
        }

        // If it's already JSON string, decode it
        if (is_string($shippingInput) && str_starts_with($shippingInput, '{')) {
            $shippingInput = json_decode($shippingInput, true);
        }

        $selections = is_array($shippingInput) ? $shippingInput : [0 => $shippingInput];

        // Ensure OTO token exists (we use cache, with fallback to renew the token)
        $token = Cache::get('tryoto-token');
        $isSandbox = config('services.tryoto.sandbox');
        $baseUrl = $isSandbox ? config('services.tryoto.test.url') : config('services.tryoto.live.url');

        if (!$token) {
            $refresh = $isSandbox
                ? (config('services.tryoto.test.token') ?? env('TRYOTO_TEST_REFRESH_TOKEN'))
                : (config('services.tryoto.live.token') ?? env('TRYOTO_REFRESH_TOKEN'));

            $resp = Http::post($baseUrl . '/rest/v2/refreshToken', ['refresh_token' => $refresh]);
            if ($resp->successful()) {
                $token = $resp->json()['access_token'];
                $expiresIn = (int)($resp->json()['expires_in'] ?? 3600);
                Cache::put('tryoto-token', $token, now()->addSeconds(max(300, $expiresIn - 60)));
            } else {
                Log::error('Tryoto token refresh failed on shipment', ['body' => $resp->body()]);
                return; // Don't break the order
            }
        }

        // Shipment destination: Preferably shipping fields, then customer, then default
        // ✅ تحويل city ID إلى city name
        $destinationCityValue = $order->shipping_city ?: $order->customer_city;
        $destinationCity = $this->resolveCityNameForTryoto($destinationCityValue);

        // Preparing cart items for dimension/weight calculations
        $cartRaw = $order->cart;
        $cartArr = is_string($cartRaw) ? (json_decode($cartRaw, true) ?: []) : (is_array($cartRaw) ? $cartRaw : (array) $cartRaw);

        // Trying to extract items in common formats
        $items = [];
        if (isset($cartArr['items']) && is_array($cartArr['items'])) {
            $items = $cartArr['items'];
        } elseif (isset($cartArr[0])) {
            $items = $cartArr; // Direct array
        }

        // Simple normalization to pass to PriceHelper::calculateShippingDimensions
        $productsForDims = [];
        foreach ($items as $ci) {
            $qty = (int)($ci['qty'] ?? $ci['quantity'] ?? 1);
            $item = $ci['item'] ?? $ci;

            $productsForDims[] = [
                'qty' => max(1, $qty),
                'item' => [
                    'weight' => (float)($item['weight'] ?? 1),
                    'size' => $item['size'] ?? null,
                ],
            ];
        }
        if (!$productsForDims) {
            // Minimum safe limit
            $productsForDims = [['qty' => 1, 'item' => ['weight' => 1, 'size' => null]]];
        }

        $dims = PriceHelper::calculateShippingDimensions($productsForDims);

        $otoPayloads = [];
        foreach ($selections as $vendorId => $value) {
            // OTO option is in the form: deliveryOptionId#Company#price
            if (!is_string($value) || strpos($value, '#') === false) {
                continue; // Not OTO, could be an internal shipping ID
            }
            [$deliveryOptionId, $company, $price] = explode('#', $value);
            $codAmount = ($order->method === 'cod' || $order->method === 'Cash On Delivery' || $order->payment_status === 'Cash On Delivery') ? (float)$order->pay_amount : 0.0;

            // ⭐ الحصول على warehouse address من التاجر
            $vendor = User::find($vendorId);
            // ✅ تحويل city ID إلى city name
            $originCityValue = $vendor->city_id ?? $vendor->warehouse_city ?? $vendor->shop_city;
            $originCity = $this->resolveCityNameForTryoto($originCityValue);

            $originAddress = $vendor && $vendor->warehouse_address
                ? $vendor->warehouse_address
                : ($vendor && $vendor->shop_address ? $vendor->shop_address : '');

            $payload = [
                'otoId' => $order->order_number, // ⭐ Using otoId instead of orderId
                'deliveryOptionId' => $deliveryOptionId,
                'originCity' => $originCity,
                'destinationCity' => $destinationCity,
                'receiverName' => $order->shipping_name ?: $order->customer_name,
                'receiverPhone' => $order->shipping_phone ?: $order->customer_phone,
                'receiverAddress' => $order->shipping_address ?: $order->customer_address,
                'weight' => max(0.1, $dims['weight']),
                'xlength' => max(30, $dims['length']),
                'xheight' => max(30, $dims['height']),
                'xwidth' => max(30, $dims['width']),
                'codAmount' => $codAmount,
            ];

            $res = Http::withToken($token)->post($baseUrl . '/rest/v2/createShipment', $payload);

            if ($res->successful()) {
                $data = $res->json();
                $shipmentId = $data['shipmentId'] ?? null;
                $trackingNumber = $data['trackingNumber'] ?? null;

                $otoPayloads[] = [
                    'vendor_id' => (string)$vendorId,
                    'company' => $company,
                    'price' => (float)$price,
                    'deliveryOptionId'=> $deliveryOptionId,
                    'shipmentId' => $shipmentId,
                    'trackingNumber' => $trackingNumber,
                ];

                // ⭐ حفظ Initial Log في shipment_status_logs
                if ($trackingNumber) {
                    DB::table('shipment_status_logs')->insert([
                        'order_id' => $order->id,
                        'vendor_id' => $vendorId,
                        'tracking_number' => $trackingNumber,
                        'shipment_id' => $shipmentId,
                        'company_name' => $company,
                        'status' => 'created',
                        'status_ar' => 'تم إنشاء الشحنة',
                        'message' => 'Shipment created successfully',
                        'message_ar' => 'تم إنشاء الشحنة بنجاح. في انتظار استلام السائق من المستودع.',
                        'location' => $originCity,
                        'status_date' => now(),
                        'raw_data' => json_encode($data),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // ⭐ إرسال Notification للتاجر
                    if ($vendor) {
                        try {
                            $notification = new UserNotification();
                            $notification->user_id = $vendorId;
                            $notification->order_number = $order->order_number;
                            // فقط إضافة order_id إذا كان العمود موجوداً
                            if (\Schema::hasColumn('user_notifications', 'order_id')) {
                                $notification->order_id = $order->id;
                            }
                            $notification->is_read = 0;
                            $notification->save();
                        } catch (\Exception $e) {
                            Log::warning('Failed to create notification', ['error' => $e->getMessage()]);
                        }
                    }
                }

                Log::info('Tryoto Shipment Created', [
                    'order_id' => $order->id,
                    'vendor_id' => $vendorId,
                    'tracking_number' => $trackingNumber,
                    'company' => $company,
                ]);
            } else {
                Log::error('Tryoto createShipment failed', [
                    'order_id' => $order->id,
                    'vendor_id' => $vendorId,
                    'payload' => $payload,
                    'body' => $res->body()
                ]);
            }
        }

        if ($otoPayloads) {
            // 1) Store the details in vendor_shipping_id as JSON text (no migration required)
            $order->vendor_shipping_id = json_encode(['oto' => $otoPayloads], JSON_UNESCAPED_UNICODE);

            // 2) Quick display and explanation
            $first = $otoPayloads[0];

            // ✅ لا نُعيد تعيين $order->shipping لأنها تحتوي على طريقة التوصيل (shipto/pickup)
            // بدلاً من ذلك، نحفظ معلومات Tryoto في shipping_title فقط
            $order->shipping_title = 'Tryoto - ' . ($first['company'] ?? 'N/A') . ' (Tracking: ' . ($first['trackingNumber'] ?? 'N/A') . ')';

            // ✅ إذا كانت shipping فارغة أو غير محددة، نضع 'shipto' كقيمة افتراضية
            if (empty($order->shipping) || !in_array($order->shipping, ['shipto', 'pickup'])) {
                $order->shipping = 'shipto';
            }

            $order->save();
        }
    }

    /**
     * تحويل city ID أو اسم إلى اسم المدينة الصحيح لـ Tryoto
     */
    protected function resolveCityNameForTryoto($cityValue): string
    {
        if (empty($cityValue)) {
            return 'Riyadh';
        }

        // إذا كان رقمياً، ابحث عن المدينة
        if (is_numeric($cityValue)) {
            $city = City::find($cityValue);
            if ($city && $city->city_name) {
                return $city->city_name;
            }
        }

        // إذا كان نصاً غير رقمي، استخدمه مباشرة
        if (!is_numeric($cityValue)) {
            return $cityValue;
        }

        return 'Riyadh';
    }
}
