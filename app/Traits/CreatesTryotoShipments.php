<?php

namespace App\Traits;

use App\Helpers\PriceHelper;
use App\Models\Order;
use App\Models\User;
use App\Models\City;
use App\Models\UserNotification;
use App\Services\TryotoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Trait for creating Tryoto shipments
 *
 * يستخدم TryotoService الموحد لجميع عمليات Tryoto
 */
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

        // استخدام TryotoService الموحد
        $tryotoService = app(TryotoService::class);

        // Shipment destination: Preferably shipping fields, then customer, then default
        // تحويل city ID إلى city name باستخدام TryotoService
        $destinationCityValue = $order->shipping_city ?: $order->customer_city;
        $destinationCity = $tryotoService->resolveCityName($destinationCityValue);

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

            // الحصول على warehouse address من التاجر
            $vendor = User::find($vendorId);

            // تحويل city ID إلى city name باستخدام TryotoService
            $originCityValue = $vendor->city_id ?? $vendor->warehouse_city ?? $vendor->shop_city;
            $originCity = $tryotoService->resolveCityName($originCityValue);

            // استخدام createShipment من TryotoService بدلاً من الاتصال المباشر
            $result = $tryotoService->createShipment(
                $order,
                (int)$vendorId,
                $deliveryOptionId,
                $company,
                (float)$price,
                'express'
            );

            if ($result['success']) {
                $otoPayloads[] = [
                    'vendor_id' => (string)$vendorId,
                    'company' => $company,
                    'price' => (float)$price,
                    'deliveryOptionId'=> $deliveryOptionId,
                    'shipmentId' => $result['shipment_id'] ?? null,
                    'trackingNumber' => $result['tracking_number'] ?? null,
                ];

                Log::info('Tryoto Shipment Created via TryotoService', [
                    'order_id' => $order->id,
                    'vendor_id' => $vendorId,
                    'tracking_number' => $result['tracking_number'],
                    'company' => $company,
                ]);
            } else {
                Log::error('Tryoto createShipment failed', [
                    'order_id' => $order->id,
                    'vendor_id' => $vendorId,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }
        }

        if ($otoPayloads) {
            // 1) Store the details in vendor_shipping_id as JSON text (no migration required)
            $order->vendor_shipping_id = json_encode(['oto' => $otoPayloads], JSON_UNESCAPED_UNICODE);

            // 2) Quick display and explanation
            $first = $otoPayloads[0];

            // لا نُعيد تعيين $order->shipping لأنها تحتوي على طريقة التوصيل (shipto/pickup)
            // بدلاً من ذلك، نحفظ معلومات Tryoto في shipping_title فقط
            $order->shipping_title = 'Tryoto - ' . ($first['company'] ?? 'N/A') . ' (Tracking: ' . ($first['trackingNumber'] ?? 'N/A') . ')';

            // إذا كانت shipping فارغة أو غير محددة، نضع 'shipto' كقيمة افتراضية
            if (empty($order->shipping) || !in_array($order->shipping, ['shipto', 'pickup'])) {
                $order->shipping = 'shipto';
            }

            $order->save();
        }
    }

    /**
     * @deprecated Use TryotoService::resolveCityName() instead
     * تحويل city ID أو اسم إلى اسم المدينة الصحيح لـ Tryoto
     */
    protected function resolveCityNameForTryoto($cityValue): string
    {
        return app(TryotoService::class)->resolveCityName($cityValue);
    }
}
