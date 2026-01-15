<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\MerchantPurchase;
use App\Models\ShipmentTracking;
use Illuminate\Support\Facades\Log;

/**
 * OrderStatusResolverService
 *
 * المصدر الوحيد للحقيقة لحالة الطلب
 * يُستدعى تلقائيًا بعد كل إدخال تتبع لحساب حالة الطلب مركزيًا
 *
 * قواعد حساب الحالة:
 * - إذا كانت جميع الشحنات delivered → الطلب completed
 * - إذا كانت أي شحنة failed/returned → الطلب requires_attention
 * - إذا كانت أي شحنة cancelled وكل البقية cancelled → الطلب cancelled
 * - إذا كانت أي شحنة in_transit/out_for_delivery → الطلب on_delivery
 * - إذا كانت أي شحنة picked_up → الطلب processing
 * - إذا كانت أي شحنة created → الطلب processing
 * - الحالة الافتراضية pending
 */
class OrderStatusResolverService
{
    /**
     * Purchase status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_ON_DELIVERY = 'on delivery';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REQUIRES_ATTENTION = 'requires_attention';

    /**
     * Mapping from tracking status to purchase status priority
     * Higher number = higher priority
     */
    private const STATUS_PRIORITY = [
        ShipmentTracking::STATUS_CREATED => 1,
        ShipmentTracking::STATUS_PICKED_UP => 3,
        ShipmentTracking::STATUS_IN_TRANSIT => 4,
        ShipmentTracking::STATUS_OUT_FOR_DELIVERY => 5,
        ShipmentTracking::STATUS_DELIVERED => 10, // Final positive
        ShipmentTracking::STATUS_FAILED => 8,     // Needs attention
        ShipmentTracking::STATUS_RETURNED => 9,   // Needs attention
        ShipmentTracking::STATUS_CANCELLED => 7,  // Final negative
    ];

    /**
     * حساب وتحديث حالة الطلب بناءً على حالات التتبع
     *
     * @param int $purchaseId
     * @return string الحالة الجديدة
     */
    public function resolveAndUpdate(int $purchaseId): string
    {
        $purchase = Purchase::find($purchaseId);

        if (!$purchase) {
            Log::warning('OrderStatusResolver: Purchase not found', ['purchase_id' => $purchaseId]);
            return self::STATUS_PENDING;
        }

        $newStatus = $this->calculateStatus($purchaseId);

        // تحديث فقط إذا تغيرت الحالة
        if ($purchase->status !== $newStatus) {
            $oldStatus = $purchase->status;

            // استخدام updateQuietly لتجنب infinite loops
            $purchase->updateQuietly(['status' => $newStatus]);

            Log::info('OrderStatusResolver: Purchase status updated', [
                'purchase_id' => $purchaseId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);

            // إضافة سجل التتبع
            $this->addPurchaseTrack($purchase, $oldStatus, $newStatus);
        }

        return $newStatus;
    }

    /**
     * حساب حالة الطلب بناءً على حالات التتبع
     *
     * @param int $purchaseId
     * @return string
     */
    public function calculateStatus(int $purchaseId): string
    {
        // جلب آخر حالة لكل تاجر في هذا الطلب
        $latestTrackings = ShipmentTracking::where('purchase_id', $purchaseId)
            ->whereIn('id', function ($sub) use ($purchaseId) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_trackings')
                    ->where('purchase_id', $purchaseId)
                    ->groupBy('merchant_id');
            })
            ->get();

        // إذا لم توجد شحنات، الحالة pending
        if ($latestTrackings->isEmpty()) {
            return self::STATUS_PENDING;
        }

        $statuses = $latestTrackings->pluck('status')->toArray();

        return $this->determineStatusFromTrackings($statuses);
    }

    /**
     * تحديد حالة الطلب من مجموعة حالات التتبع
     *
     * @param array $statuses
     * @return string
     */
    private function determineStatusFromTrackings(array $statuses): string
    {
        $totalShipments = count($statuses);

        // حساب عدد كل حالة
        $delivered = count(array_filter($statuses, fn($s) => $s === ShipmentTracking::STATUS_DELIVERED));
        $cancelled = count(array_filter($statuses, fn($s) => $s === ShipmentTracking::STATUS_CANCELLED));
        $failed = count(array_filter($statuses, fn($s) => $s === ShipmentTracking::STATUS_FAILED));
        $returned = count(array_filter($statuses, fn($s) => $s === ShipmentTracking::STATUS_RETURNED));
        $inTransit = count(array_filter($statuses, fn($s) => in_array($s, [
            ShipmentTracking::STATUS_IN_TRANSIT,
            ShipmentTracking::STATUS_OUT_FOR_DELIVERY
        ])));
        $pickedUp = count(array_filter($statuses, fn($s) => $s === ShipmentTracking::STATUS_PICKED_UP));
        $created = count(array_filter($statuses, fn($s) => $s === ShipmentTracking::STATUS_CREATED));

        // القاعدة 1: جميع الشحنات تم تسليمها
        if ($delivered === $totalShipments) {
            return self::STATUS_COMPLETED;
        }

        // القاعدة 2: جميع الشحنات ملغاة
        if ($cancelled === $totalShipments) {
            return self::STATUS_CANCELLED;
        }

        // القاعدة 3: أي شحنة فاشلة أو مرتجعة تحتاج انتباه
        if ($failed > 0 || $returned > 0) {
            return self::STATUS_REQUIRES_ATTENTION;
        }

        // القاعدة 4: أي شحنة في الطريق
        if ($inTransit > 0) {
            return self::STATUS_ON_DELIVERY;
        }

        // القاعدة 5: أي شحنة تم استلامها أو تم إنشاؤها
        if ($pickedUp > 0 || $created > 0) {
            return self::STATUS_PROCESSING;
        }

        // القاعدة 6: مزيج من تم التسليم وملغى (بعض الشحنات تم تسليمها)
        if ($delivered > 0 && ($delivered + $cancelled) === $totalShipments) {
            return self::STATUS_COMPLETED; // نعتبره مكتمل إذا البقية ملغاة
        }

        return self::STATUS_PENDING;
    }

    /**
     * حساب وتحديث حالة طلب التاجر (MerchantPurchase)
     *
     * @param int $purchaseId
     * @param int $merchantId
     * @return string
     */
    public function resolveAndUpdateMerchant(int $purchaseId, int $merchantId): string
    {
        $merchantPurchase = MerchantPurchase::where('purchase_id', $purchaseId)
            ->where('user_id', $merchantId)
            ->first();

        if (!$merchantPurchase) {
            return self::STATUS_PENDING;
        }

        $newStatus = $this->calculateMerchantStatus($purchaseId, $merchantId);

        if ($merchantPurchase->status !== $newStatus) {
            $merchantPurchase->updateQuietly(['status' => $newStatus]);

            Log::info('OrderStatusResolver: MerchantPurchase status updated', [
                'purchase_id' => $purchaseId,
                'merchant_id' => $merchantId,
                'new_status' => $newStatus,
            ]);
        }

        return $newStatus;
    }

    /**
     * حساب حالة طلب التاجر
     *
     * @param int $purchaseId
     * @param int $merchantId
     * @return string
     */
    public function calculateMerchantStatus(int $purchaseId, int $merchantId): string
    {
        $latestTracking = ShipmentTracking::getLatestForPurchase($purchaseId, $merchantId);

        if (!$latestTracking) {
            return self::STATUS_PENDING;
        }

        return $this->mapTrackingStatusToPurchaseStatus($latestTracking->status);
    }

    /**
     * تحويل حالة التتبع لحالة الطلب
     *
     * @param string $trackingStatus
     * @return string
     */
    private function mapTrackingStatusToPurchaseStatus(string $trackingStatus): string
    {
        return match($trackingStatus) {
            ShipmentTracking::STATUS_CREATED => self::STATUS_PROCESSING,

            ShipmentTracking::STATUS_PICKED_UP => self::STATUS_PROCESSING,

            ShipmentTracking::STATUS_IN_TRANSIT,
            ShipmentTracking::STATUS_OUT_FOR_DELIVERY => self::STATUS_ON_DELIVERY,

            ShipmentTracking::STATUS_DELIVERED => self::STATUS_COMPLETED,

            ShipmentTracking::STATUS_CANCELLED => self::STATUS_CANCELLED,

            ShipmentTracking::STATUS_FAILED,
            ShipmentTracking::STATUS_RETURNED => self::STATUS_REQUIRES_ATTENTION,

            default => self::STATUS_PENDING,
        };
    }

    /**
     * إضافة سجل تتبع للطلب عند تغير الحالة
     */
    private function addPurchaseTrack(Purchase $purchase, string $oldStatus, string $newStatus): void
    {
        $statusLabels = [
            self::STATUS_PENDING => 'قيد الانتظار',
            self::STATUS_PROCESSING => 'قيد التجهيز',
            self::STATUS_ON_DELIVERY => 'في الطريق',
            self::STATUS_COMPLETED => 'مكتمل',
            self::STATUS_CANCELLED => 'ملغى',
            self::STATUS_DECLINED => 'مرفوض',
            self::STATUS_REQUIRES_ATTENTION => 'يحتاج انتباه',
        ];

        try {
            $purchase->tracks()->create([
                'name' => $statusLabels[$newStatus] ?? $newStatus,
                'text' => sprintf(
                    'تم تحديث حالة الطلب من "%s" إلى "%s" بناءً على حالة الشحن',
                    $statusLabels[$oldStatus] ?? $oldStatus,
                    $statusLabels[$newStatus] ?? $newStatus
                ),
            ]);
        } catch (\Exception $e) {
            Log::warning('OrderStatusResolver: Failed to add track', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * حساب حالة جميع الطلبات (للـ cron job)
     */
    public function resolveAllPending(): array
    {
        $updated = 0;
        $failed = 0;

        // جلب الطلبات التي لها شحنات نشطة وليست مكتملة/ملغاة
        $purchaseIds = ShipmentTracking::whereIn('id', function ($sub) {
                $sub->selectRaw('MAX(id)')
                    ->from('shipment_trackings')
                    ->groupBy('purchase_id');
            })
            ->whereNotIn('status', [
                ShipmentTracking::STATUS_DELIVERED,
                ShipmentTracking::STATUS_CANCELLED,
            ])
            ->pluck('purchase_id')
            ->unique();

        foreach ($purchaseIds as $purchaseId) {
            try {
                $this->resolveAndUpdate($purchaseId);
                $updated++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('OrderStatusResolver: Failed to resolve', [
                    'purchase_id' => $purchaseId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['updated' => $updated, 'failed' => $failed];
    }
}
