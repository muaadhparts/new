<?php

namespace App\Traits;

use App\Helpers\PriceHelper;
use App\Domain\Commerce\Models\Purchase;
use App\Domain\Catalog\Models\UserCatalogEvent;
use App\Domain\Shipping\Services\TryotoService;
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
     * Create Tryoto shipment(s) after the purchase is successfully created.
     * Store the results in merchant_shipping_id as JSON, and update the shipping/shipping_name for the view.
     *
     * @param Purchase $purchase
     * @param array $input
     * @return void
     */
    protected function createOtoShipments(Purchase $purchase, array $input): void
    {
        // Check shipping selection — supports array (multi-merchant) or single-value scenarios
        $shippingInput = $input['shipping'] ?? $input['merchant_shipping_id'] ?? null;
        if (!$shippingInput) {
            return;
        }

        // If it's already JSON string, decode it
        if (is_string($shippingInput) && str_starts_with($shippingInput, '{')) {
            $shippingInput = json_decode($shippingInput, true);
        }

        $selections = is_array($shippingInput) ? $shippingInput : [0 => $shippingInput];

        // System-level TryotoService for city resolution (no merchant credentials needed)
        $systemTryotoService = app(TryotoService::class);

        // Shipment destination: customer city
        // تحويل city ID إلى city name باستخدام TryotoService
        $destinationCityValue = $purchase->customer_city;
        $destinationCity = $systemTryotoService->resolveCityName($destinationCityValue);

        // Preparing cart items for dimension/weight calculations
        // Use model method that handles legacy double-encoded data
        $items = $purchase->getCartItems();

        // Simple normalization to pass to PriceHelper::calculateShippingDimensions
        $itemsForDims = [];
        foreach ($items as $ci) {
            $qty = (int)($ci['qty'] ?? $ci['quantity'] ?? 1);
            $item = $ci['item'] ?? $ci;

            $itemsForDims[] = [
                'qty' => max(1, $qty),
                'item' => [
                    'weight' => (float)($item['weight'] ?? 1),
                    'size' => $item['size'] ?? null,
                ],
            ];
        }
        if (!$itemsForDims) {
            // Minimum safe limit
            $itemsForDims = [['qty' => 1, 'item' => ['weight' => 1, 'size' => null]]];
        }

        $dims = PriceHelper::calculateShippingDimensions($itemsForDims);

        $otoPayloads = [];
        foreach ($selections as $merchantId => $value) {
            // OTO option is in the form: deliveryOptionId#Company#price
            if (!is_string($value) || strpos($value, '#') === false) {
                continue; // Not OTO, could be an internal shipping ID
            }
            [$deliveryOptionId, $company, $price] = explode('#', $value);

            // ✅ createShipment يجلب بيانات التاجر والمستودع من merchant_branches داخلياً
            $merchantTryotoService = app(TryotoService::class)->forMerchant((int)$merchantId);
            $result = $merchantTryotoService->createShipment(
                $purchase,
                (int)$merchantId,
                $deliveryOptionId,
                $company,
                (float)$price,
                'express'
            );

            if ($result['success']) {
                $otoPayloads[] = [
                    'merchant_id' => (string)$merchantId,
                    'company' => $company,
                    'price' => (float)$price,
                    'deliveryOptionId'=> $deliveryOptionId,
                    'shipmentId' => $result['shipment_id'] ?? null,
                    'trackingNumber' => $result['tracking_number'] ?? null,
                ];

                Log::debug('Tryoto Shipment Created via TryotoService', [
                    'purchase_id' => $purchase->id,
                    'merchant_id' => $merchantId,
                    'tracking_number' => $result['tracking_number'],
                    'company' => $company,
                ]);
            } else {
                Log::error('Tryoto createShipment failed', [
                    'purchase_id' => $purchase->id,
                    'merchant_id' => $merchantId,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }
        }

        if ($otoPayloads) {
            // 1) Store the details in merchant_shipping_id as JSON text (no migration required)
            $purchase->merchant_shipping_id = json_encode(['oto' => $otoPayloads], JSON_UNESCAPED_UNICODE);

            // 2) Quick display and explanation
            $first = $otoPayloads[0];

            // لا نُعيد تعيين $purchase->shipping لأنها تحتوي على طريقة التوصيل (shipto)
            // بدلاً من ذلك، نحفظ معلومات Tryoto في shipping_name فقط
            $purchase->shipping_name = 'Tryoto - ' . ($first['company'] ?? 'N/A') . ' (Tracking: ' . ($first['trackingNumber'] ?? 'N/A') . ')';

            // إذا كانت shipping فارغة أو غير محددة، نضع 'shipto' كقيمة افتراضية
            if (empty($purchase->shipping) || $purchase->shipping !== 'shipto') {
                $purchase->shipping = 'shipto';
            }

            $purchase->save();
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
