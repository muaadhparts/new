<?php

namespace App\Services;

use App\Models\ShipmentTracking;
use App\Models\Purchase;
use App\Models\Shipping;
use App\Models\MerchantPurchase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * خدمة التتبع الموحدة
 *
 * المصدر الوحيد لإنشاء وتحديث سجلات التتبع
 * لا منطق تتبع خارج هذه الخدمة
 */
class ShipmentTrackingService
{
    // =====================
    // إنشاء سجل تتبع جديد
    // =====================

    /**
     * إنشاء سجل تتبع جديد (لكل تحديث حالة)
     *
     * @param int $purchaseId
     * @param int $merchantId
     * @param string $status
     * @param array $data بيانات إضافية
     * @return ShipmentTracking
     */
    public function createTrackingRecord(
        int $purchaseId,
        int $merchantId,
        string $status,
        array $data = []
    ): ShipmentTracking {

        $tracking = ShipmentTracking::create([
            'purchase_id' => $purchaseId,
            'merchant_id' => $merchantId,
            'shipping_id' => $data['shipping_id'] ?? null,
            'integration_type' => $data['integration_type'] ?? ShipmentTracking::INTEGRATION_MANUAL,
            'provider' => $data['provider'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
            'external_shipment_id' => $data['external_shipment_id'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'status' => $status,
            'status_ar' => $data['status_ar'] ?? ShipmentTracking::getStatusTranslation($status),
            'status_en' => $data['status_en'] ?? ShipmentTracking::getStatusTranslationEn($status),
            'message' => $data['message'] ?? null,
            'message_ar' => $data['message_ar'] ?? null,
            'location' => $data['location'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? now(),
            'source' => $data['source'] ?? ShipmentTracking::SOURCE_SYSTEM,
            'raw_payload' => $data['raw_payload'] ?? null,
            'awb_url' => $data['awb_url'] ?? null,
            'shipping_cost' => $data['shipping_cost'] ?? null,
            'cod_amount' => $data['cod_amount'] ?? null,
        ]);

        Log::info('ShipmentTracking: Record created', [
            'tracking_id' => $tracking->id,
            'purchase_id' => $purchaseId,
            'merchant_id' => $merchantId,
            'status' => $status,
            'source' => $data['source'] ?? 'system',
        ]);

        // Note: Purchase status is now updated automatically via ShipmentTrackingObserver
        // calling OrderStatusResolverService - NO direct status modification here

        return $tracking;
    }

    // =====================
    // إنشاء شحنة جديدة (أول سجل)
    // =====================

    /**
     * إنشاء شحنة API (مثل Tryoto)
     */
    public function createApiShipment(
        int $purchaseId,
        int $merchantId,
        int $shippingId,
        string $provider,
        string $trackingNumber,
        string $externalShipmentId,
        string $companyName,
        float $shippingCost = 0,
        float $codAmount = 0,
        ?string $awbUrl = null,
        ?array $rawPayload = null
    ): ShipmentTracking {

        return $this->createTrackingRecord($purchaseId, $merchantId, ShipmentTracking::STATUS_CREATED, [
            'shipping_id' => $shippingId,
            'integration_type' => ShipmentTracking::INTEGRATION_API,
            'provider' => $provider,
            'tracking_number' => $trackingNumber,
            'external_shipment_id' => $externalShipmentId,
            'company_name' => $companyName,
            'source' => ShipmentTracking::SOURCE_API,
            'shipping_cost' => $shippingCost,
            'cod_amount' => $codAmount,
            'awb_url' => $awbUrl,
            'raw_payload' => $rawPayload,
            'message' => 'Shipment created via ' . $provider,
            'message_ar' => 'تم إنشاء الشحنة عبر ' . $companyName,
        ]);
    }

    /**
     * إنشاء شحنة يدوية
     */
    public function createManualShipment(
        int $purchaseId,
        int $merchantId,
        int $shippingId,
        string $provider,
        ?string $trackingNumber = null,
        ?string $companyName = null,
        float $shippingCost = 0,
        float $codAmount = 0,
        ?int $merchantLocationId = null
    ): ShipmentTracking {

        // ✅ جلب بيانات موقع الاستلام لتضمينها في raw_payload
        $pickupLocationData = null;
        if ($merchantLocationId) {
            $location = \DB::table('merchant_locations')->find($merchantLocationId);
            if ($location) {
                $pickupLocationData = [
                    'merchant_location_id' => $merchantLocationId,
                    'warehouse_name' => $location->warehouse_name,
                    'address' => $location->location,
                ];
            }
        }

        return $this->createTrackingRecord($purchaseId, $merchantId, ShipmentTracking::STATUS_CREATED, [
            'shipping_id' => $shippingId,
            'integration_type' => ShipmentTracking::INTEGRATION_MANUAL,
            'provider' => $provider,
            'tracking_number' => $trackingNumber,
            'company_name' => $companyName ?: $provider,
            'source' => ShipmentTracking::SOURCE_MERCHANT,
            'shipping_cost' => $shippingCost,
            'cod_amount' => $codAmount,
            'message' => 'Manual shipment created',
            'message_ar' => 'تم إنشاء الشحنة يدوياً',
            'raw_payload' => $pickupLocationData ? ['pickup_location' => $pickupLocationData] : null,
        ]);
    }

    // =====================
    // تحديث الحالة
    // =====================

    /**
     * تحديث حالة من API (Webhook أو Polling)
     */
    public function updateFromApi(
        string $trackingNumber,
        string $newStatus,
        array $data = []
    ): ?ShipmentTracking {

        // الحصول على آخر سجل
        $latest = ShipmentTracking::getLatestByTracking($trackingNumber);

        if (!$latest) {
            Log::warning('ShipmentTracking: No record found for tracking', [
                'tracking_number' => $trackingNumber,
            ]);
            return null;
        }

        // تجاهل إذا نفس الحالة
        if ($latest->status === $newStatus) {
            Log::debug('ShipmentTracking: Same status, skipping', [
                'tracking_number' => $trackingNumber,
                'status' => $newStatus,
            ]);
            return $latest;
        }

        // تجاهل إذا حالة نهائية
        if ($latest->is_final) {
            Log::warning('ShipmentTracking: Cannot update final status', [
                'tracking_number' => $trackingNumber,
                'current_status' => $latest->status,
                'new_status' => $newStatus,
            ]);
            return $latest;
        }

        // إنشاء سجل جديد
        return $this->createTrackingRecord(
            $latest->purchase_id,
            $latest->merchant_id,
            $newStatus,
            array_merge([
                'shipping_id' => $latest->shipping_id,
                'integration_type' => $latest->integration_type,
                'provider' => $latest->provider,
                'tracking_number' => $trackingNumber,
                'external_shipment_id' => $latest->external_shipment_id,
                'company_name' => $latest->company_name,
                'source' => ShipmentTracking::SOURCE_API,
                'shipping_cost' => $latest->shipping_cost,
                'cod_amount' => $latest->cod_amount,
            ], $data)
        );
    }

    /**
     * تحديث حالة يدوياً من التاجر
     */
    public function updateManually(
        int $purchaseId,
        int $merchantId,
        string $newStatus,
        ?string $location = null,
        ?string $message = null
    ): ?ShipmentTracking {

        // الحصول على آخر سجل
        $latest = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

        if (!$latest) {
            Log::warning('ShipmentTracking: No record found for manual update', [
                'purchase_id' => $purchaseId,
                'merchant_id' => $merchantId,
            ]);
            return null;
        }

        // التحقق من أن الشحنة يدوية
        if ($latest->integration_type !== ShipmentTracking::INTEGRATION_MANUAL) {
            Log::warning('ShipmentTracking: Cannot manually update API shipment', [
                'purchase_id' => $purchaseId,
                'integration_type' => $latest->integration_type,
            ]);
            return null;
        }

        // تجاهل إذا حالة نهائية
        if ($latest->is_final) {
            Log::warning('ShipmentTracking: Cannot update final status', [
                'purchase_id' => $purchaseId,
                'current_status' => $latest->status,
            ]);
            return $latest;
        }

        // التحقق من صحة الانتقال
        if (!ShipmentTracking::canTransition($latest->status, $newStatus)) {
            Log::warning('ShipmentTracking: Invalid status transition', [
                'from' => $latest->status,
                'to' => $newStatus,
            ]);
            return null;
        }

        return $this->createTrackingRecord($purchaseId, $merchantId, $newStatus, [
            'shipping_id' => $latest->shipping_id,
            'integration_type' => ShipmentTracking::INTEGRATION_MANUAL,
            'provider' => $latest->provider,
            'tracking_number' => $latest->tracking_number,
            'company_name' => $latest->company_name,
            'source' => ShipmentTracking::SOURCE_MERCHANT,
            'location' => $location,
            'message' => $message,
            'message_ar' => $message,
            'shipping_cost' => $latest->shipping_cost,
            'cod_amount' => $latest->cod_amount,
        ]);
    }

    /**
     * تحديث من المشغل (يمكنه تحديث أي نوع)
     */
    public function updateByOperator(
        int $purchaseId,
        int $merchantId,
        string $newStatus,
        ?string $location = null,
        ?string $message = null
    ): ?ShipmentTracking {

        $latest = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

        if (!$latest) {
            return null;
        }

        return $this->createTrackingRecord($purchaseId, $merchantId, $newStatus, [
            'shipping_id' => $latest->shipping_id,
            'integration_type' => $latest->integration_type,
            'provider' => $latest->provider,
            'tracking_number' => $latest->tracking_number,
            'external_shipment_id' => $latest->external_shipment_id,
            'company_name' => $latest->company_name,
            'source' => ShipmentTracking::SOURCE_OPERATOR,
            'location' => $location,
            'message' => $message,
            'message_ar' => $message,
            'shipping_cost' => $latest->shipping_cost,
            'cod_amount' => $latest->cod_amount,
        ]);
    }

    // =====================
    // إلغاء الشحنة
    // =====================

    /**
     * إلغاء شحنة
     */
    public function cancelShipment(
        int $purchaseId,
        int $merchantId,
        string $source,
        ?string $reason = null
    ): ?ShipmentTracking {

        $latest = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

        if (!$latest || $latest->is_final) {
            return null;
        }

        return $this->createTrackingRecord($purchaseId, $merchantId, ShipmentTracking::STATUS_CANCELLED, [
            'shipping_id' => $latest->shipping_id,
            'integration_type' => $latest->integration_type,
            'provider' => $latest->provider,
            'tracking_number' => $latest->tracking_number,
            'external_shipment_id' => $latest->external_shipment_id,
            'company_name' => $latest->company_name,
            'source' => $source,
            'message' => $reason ?: 'Shipment cancelled',
            'message_ar' => $reason ?: 'تم إلغاء الشحنة',
        ]);
    }

    // =====================
    // الاستعلامات
    // =====================

    /**
     * الحصول على حالة الشحنة الحالية
     */
    public function getCurrentStatus(int $purchaseId, ?int $merchantId = null): ?array
    {
        $latest = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

        if (!$latest) {
            return null;
        }

        return [
            'status' => $latest->status,
            'status_ar' => $latest->status_ar,
            'status_en' => $latest->status_en,
            'tracking_number' => $latest->tracking_number,
            'company_name' => $latest->company_name,
            'location' => $latest->location,
            'occurred_at' => $latest->occurred_at,
            'is_final' => $latest->is_final,
            'progress_percent' => $latest->progress_percent,
            'status_color' => $latest->status_color,
            'status_icon' => $latest->status_icon,
        ];
    }

    /**
     * الحصول على تاريخ التتبع الكامل
     */
    public function getTrackingHistory(int $purchaseId, ?int $merchantId = null): array
    {
        $records = ShipmentTracking::getHistoryForPurchase($purchaseId, $merchantId);

        return $records->map(function ($record) {
            return [
                'id' => $record->id,
                'status' => $record->status,
                'status_ar' => $record->status_ar,
                'status_en' => $record->status_en,
                'message' => $record->message,
                'message_ar' => $record->message_ar,
                'location' => $record->location,
                'occurred_at' => $record->occurred_at,
                'source' => $record->source,
                'status_color' => $record->status_color,
                'status_icon' => $record->status_icon,
            ];
        })->toArray();
    }

    /**
     * الحصول على معلومات الشحنة الكاملة
     */
    public function getShipmentInfo(int $purchaseId, ?int $merchantId = null): ?array
    {
        $latest = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

        if (!$latest) {
            return null;
        }

        $history = $this->getTrackingHistory($purchaseId, $merchantId);

        return [
            'current' => [
                'status' => $latest->status,
                'status_ar' => $latest->status_ar,
                'status_en' => $latest->status_en,
                'tracking_number' => $latest->tracking_number,
                'company_name' => $latest->company_name,
                'provider' => $latest->provider,
                'integration_type' => $latest->integration_type,
                'location' => $latest->location,
                'occurred_at' => $latest->occurred_at,
                'is_final' => $latest->is_final,
                'progress_percent' => $latest->progress_percent,
                'status_color' => $latest->status_color,
                'status_icon' => $latest->status_icon,
                'awb_url' => $latest->awb_url,
                'shipping_cost' => $latest->shipping_cost,
                'cod_amount' => $latest->cod_amount,
            ],
            'history' => $history,
            'can_update' => $latest->integration_type === ShipmentTracking::INTEGRATION_MANUAL && !$latest->is_final,
        ];
    }

    // =====================
    // التحقق من نوع الشحن
    // =====================

    /**
     * تحديد نوع التتبع من شركة الشحن
     */
    public function resolveIntegrationType(int $shippingId): string
    {
        $shipping = Shipping::find($shippingId);

        if (!$shipping) {
            return 'none';
        }

        return $shipping->integration_type ?? 'none';
    }

    /**
     * هل يوجد تتبع لهذا النوع من الشحن؟
     */
    public function hasTrackingSupport(int $shippingId): bool
    {
        $type = $this->resolveIntegrationType($shippingId);
        return in_array($type, ['api', 'manual']);
    }

    // =====================
    // إحصائيات
    // =====================

    /**
     * إحصائيات التتبع للتاجر
     */
    public function getMerchantStats(int $merchantId): array
    {
        // آخر حالة لكل شحنة فريدة
        $latestStatuses = DB::table('shipment_trackings as st1')
            ->select('st1.*')
            ->where('st1.merchant_id', $merchantId)
            ->whereRaw('st1.id = (SELECT MAX(st2.id) FROM shipment_trackings st2 WHERE st2.purchase_id = st1.purchase_id AND st2.merchant_id = st1.merchant_id)')
            ->get();

        $stats = [
            'total' => $latestStatuses->count(),
            'created' => 0,
            'in_transit' => 0,
            'delivered' => 0,
            'failed' => 0,
            'returned' => 0,
            'cancelled' => 0,
        ];

        foreach ($latestStatuses as $record) {
            match ($record->status) {
                ShipmentTracking::STATUS_CREATED, ShipmentTracking::STATUS_PICKED_UP => $stats['created']++,
                ShipmentTracking::STATUS_IN_TRANSIT, ShipmentTracking::STATUS_OUT_FOR_DELIVERY => $stats['in_transit']++,
                ShipmentTracking::STATUS_DELIVERED => $stats['delivered']++,
                ShipmentTracking::STATUS_FAILED => $stats['failed']++,
                ShipmentTracking::STATUS_RETURNED => $stats['returned']++,
                ShipmentTracking::STATUS_CANCELLED => $stats['cancelled']++,
                default => null,
            };
        }

        return $stats;
    }

    /**
     * إحصائيات التتبع للمشغل
     */
    public function getOperatorStats(): array
    {
        $latestStatuses = DB::table('shipment_trackings as st1')
            ->select('st1.*')
            ->whereRaw('st1.id = (SELECT MAX(st2.id) FROM shipment_trackings st2 WHERE st2.purchase_id = st1.purchase_id AND st2.merchant_id = st1.merchant_id)')
            ->get();

        $stats = [
            'total' => $latestStatuses->count(),
            'by_status' => [],
            'by_provider' => [],
            'by_integration' => ['api' => 0, 'manual' => 0],
        ];

        foreach ($latestStatuses as $record) {
            // بالحالة
            $stats['by_status'][$record->status] = ($stats['by_status'][$record->status] ?? 0) + 1;

            // بالمزود
            $provider = $record->provider ?: 'unknown';
            $stats['by_provider'][$provider] = ($stats['by_provider'][$provider] ?? 0) + 1;

            // بنوع التكامل
            $stats['by_integration'][$record->integration_type]++;
        }

        return $stats;
    }
}
