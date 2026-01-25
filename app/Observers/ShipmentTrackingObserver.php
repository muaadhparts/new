<?php

namespace App\Observers;

use App\Domain\Shipping\Models\ShipmentTracking;
use App\Domain\Commerce\Services\OrderStatusResolverService;
use App\Events\ShipmentStatusChanged;
use Illuminate\Support\Facades\Log;

/**
 * ShipmentTrackingObserver
 *
 * يراقب التغييرات على جدول shipment_trackings
 * ويستدعي OrderStatusResolverService تلقائيًا بعد كل إدخال
 */
class ShipmentTrackingObserver
{
    protected OrderStatusResolverService $statusResolver;

    public function __construct(OrderStatusResolverService $statusResolver)
    {
        $this->statusResolver = $statusResolver;
    }

    /**
     * Handle the ShipmentTracking "created" event.
     * يُستدعى بعد كل إدخال تتبع جديد
     */
    public function created(ShipmentTracking $tracking): void
    {
        // 1. تحديث حالة الطلب الرئيسي
        $this->statusResolver->resolveAndUpdate($tracking->purchase_id);

        // 2. تحديث حالة طلب التاجر
        if ($tracking->merchant_id) {
            $this->statusResolver->resolveAndUpdateMerchant(
                $tracking->purchase_id,
                $tracking->merchant_id
            );
        }

        // 3. إطلاق حدث تغير الحالة (للإشعارات)
        $this->fireStatusChangedEvent($tracking);

        Log::debug('ShipmentTrackingObserver: Tracking created, status resolved', [
            'tracking_id' => $tracking->id,
            'purchase_id' => $tracking->purchase_id,
            'status' => $tracking->status,
        ]);
    }

    /**
     * إطلاق حدث تغير الحالة
     */
    private function fireStatusChangedEvent(ShipmentTracking $tracking): void
    {
        // جلب الحالة السابقة من نفس التتبع
        $previousTracking = ShipmentTracking::where('purchase_id', $tracking->purchase_id)
            ->where('merchant_id', $tracking->merchant_id)
            ->where('id', '<', $tracking->id)
            ->orderBy('id', 'desc')
            ->first();

        $oldStatus = $previousTracking?->status;

        // إطلاق الحدث فقط إذا تغيرت الحالة
        if ($oldStatus !== $tracking->status) {
            try {
                event(new ShipmentStatusChanged($tracking, $oldStatus));
            } catch (\Exception $e) {
                Log::warning('ShipmentTrackingObserver: Failed to fire event', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the ShipmentTracking "updated" event.
     * ملاحظة: التتبعات لا تُحدَّث، كل تغيير هو INSERT جديد
     */
    public function updated(ShipmentTracking $tracking): void
    {
        // لا نتوقع حدوث updates لأن النظام يعمل بـ INSERT فقط
        // لكن إذا حدث، نعيد حساب الحالة
        $this->statusResolver->resolveAndUpdate($tracking->purchase_id);
    }

    /**
     * Handle the ShipmentTracking "deleted" event.
     */
    public function deleted(ShipmentTracking $tracking): void
    {
        // إعادة حساب الحالة بعد الحذف
        $this->statusResolver->resolveAndUpdate($tracking->purchase_id);
    }
}
