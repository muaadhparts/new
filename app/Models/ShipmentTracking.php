<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نظام التتبع الموحد
 *
 * كل سجل = حدث تتبع واحد
 * لا يتم تحديث السجلات - فقط INSERT جديد لكل تغيير حالة
 */
class ShipmentTracking extends Model
{
    protected $table = 'shipment_trackings';

    protected $fillable = [
        'purchase_id',
        'merchant_id',
        'shipping_id',
        'integration_type',
        'provider',
        'tracking_number',
        'external_shipment_id',
        'company_name',
        'status',
        'status_ar',
        'status_en',
        'message',
        'message_ar',
        'location',
        'latitude',
        'longitude',
        'occurred_at',
        'source',
        'raw_payload',
        'awb_url',
        'shipping_cost',
        'cod_amount',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'occurred_at' => 'datetime',
        'shipping_cost' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    // =====================
    // الثوابت
    // =====================

    // أنواع التكامل
    const INTEGRATION_API = 'api';
    const INTEGRATION_MANUAL = 'manual';

    // مصادر التحديث
    const SOURCE_API = 'api';
    const SOURCE_MERCHANT = 'merchant';
    const SOURCE_SYSTEM = 'system';
    const SOURCE_OPERATOR = 'operator';

    // الحالات الموحدة
    const STATUS_CREATED = 'created';
    const STATUS_PICKED_UP = 'picked_up';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_RETURNED = 'returned';
    const STATUS_CANCELLED = 'cancelled';

    // الحالات النهائية (لا يمكن التغيير بعدها)
    const FINAL_STATUSES = [
        self::STATUS_DELIVERED,
        self::STATUS_RETURNED,
        self::STATUS_CANCELLED,
    ];

    // =====================
    // العلاقات
    // =====================

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function shipping(): BelongsTo
    {
        return $this->belongsTo(Shipping::class);
    }

    // =====================
    // Scopes
    // =====================

    public function scopeForPurchase($query, int $purchaseId)
    {
        return $query->where('purchase_id', $purchaseId);
    }

    public function scopeForMerchant($query, int $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    public function scopeByTracking($query, string $trackingNumber)
    {
        return $query->where('tracking_number', $trackingNumber);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeApiIntegration($query)
    {
        return $query->where('integration_type', self::INTEGRATION_API);
    }

    public function scopeManualIntegration($query)
    {
        return $query->where('integration_type', self::INTEGRATION_MANUAL);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('occurred_at', 'desc')->orderBy('id', 'desc');
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', self::FINAL_STATUSES);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', self::FINAL_STATUSES);
    }

    // =====================
    // Accessors
    // =====================

    /**
     * الحصول على الحالة المترجمة
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status_ar ?: self::getStatusTranslation($this->status);
    }

    /**
     * هل الشحنة في حالة نهائية؟
     */
    public function getIsFinalAttribute(): bool
    {
        return in_array($this->status, self::FINAL_STATUSES);
    }

    /**
     * رقم خطوة التقدم (1-5)
     */
    public function getProgressStepAttribute(): int
    {
        return match ($this->status) {
            self::STATUS_CREATED => 1,
            self::STATUS_PICKED_UP => 2,
            self::STATUS_IN_TRANSIT => 3,
            self::STATUS_OUT_FOR_DELIVERY => 4,
            self::STATUS_DELIVERED => 5,
            self::STATUS_FAILED, self::STATUS_RETURNED, self::STATUS_CANCELLED => 0,
            default => 1,
        };
    }

    /**
     * نسبة التقدم (0-100)
     */
    public function getProgressPercentAttribute(): int
    {
        return match ($this->status) {
            self::STATUS_CREATED => 20,
            self::STATUS_PICKED_UP => 40,
            self::STATUS_IN_TRANSIT => 60,
            self::STATUS_OUT_FOR_DELIVERY => 80,
            self::STATUS_DELIVERED => 100,
            self::STATUS_FAILED, self::STATUS_RETURNED, self::STATUS_CANCELLED => 0,
            default => 0,
        };
    }

    /**
     * لون الحالة للعرض
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_CREATED => 'info',
            self::STATUS_PICKED_UP => 'primary',
            self::STATUS_IN_TRANSIT => 'warning',
            self::STATUS_OUT_FOR_DELIVERY => 'info',
            self::STATUS_DELIVERED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_RETURNED => 'secondary',
            self::STATUS_CANCELLED => 'dark',
            default => 'secondary',
        };
    }

    /**
     * أيقونة الحالة
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_CREATED => 'fas fa-box',
            self::STATUS_PICKED_UP => 'fas fa-truck-loading',
            self::STATUS_IN_TRANSIT => 'fas fa-shipping-fast',
            self::STATUS_OUT_FOR_DELIVERY => 'fas fa-truck',
            self::STATUS_DELIVERED => 'fas fa-check-circle',
            self::STATUS_FAILED => 'fas fa-exclamation-circle',
            self::STATUS_RETURNED => 'fas fa-undo',
            self::STATUS_CANCELLED => 'fas fa-times-circle',
            default => 'fas fa-circle',
        };
    }

    // =====================
    // Static Methods
    // =====================

    /**
     * الحصول على ترجمة الحالة
     */
    public static function getStatusTranslation(string $status): string
    {
        return match ($status) {
            self::STATUS_CREATED => 'تم إنشاء الشحنة',
            self::STATUS_PICKED_UP => 'تم الاستلام من المستودع',
            self::STATUS_IN_TRANSIT => 'في الطريق',
            self::STATUS_OUT_FOR_DELIVERY => 'خرج للتوصيل',
            self::STATUS_DELIVERED => 'تم التسليم',
            self::STATUS_FAILED => 'فشل التوصيل',
            self::STATUS_RETURNED => 'مرتجع',
            self::STATUS_CANCELLED => 'ملغي',
            default => $status,
        };
    }

    /**
     * الحصول على ترجمة الحالة بالإنجليزية
     */
    public static function getStatusTranslationEn(string $status): string
    {
        return match ($status) {
            self::STATUS_CREATED => 'Shipment Created',
            self::STATUS_PICKED_UP => 'Picked Up',
            self::STATUS_IN_TRANSIT => 'In Transit',
            self::STATUS_OUT_FOR_DELIVERY => 'Out for Delivery',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_FAILED => 'Delivery Failed',
            self::STATUS_RETURNED => 'Returned',
            self::STATUS_CANCELLED => 'Cancelled',
            default => $status,
        };
    }

    /**
     * جميع الحالات المتاحة
     */
    public static function getAllStatuses(): array
    {
        return [
            self::STATUS_CREATED,
            self::STATUS_PICKED_UP,
            self::STATUS_IN_TRANSIT,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED,
            self::STATUS_FAILED,
            self::STATUS_RETURNED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * الحالات المتاحة للتحديث اليدوي
     */
    public static function getManualUpdateStatuses(): array
    {
        return [
            self::STATUS_PICKED_UP,
            self::STATUS_IN_TRANSIT,
            self::STATUS_OUT_FOR_DELIVERY,
            self::STATUS_DELIVERED,
            self::STATUS_FAILED,
            self::STATUS_RETURNED,
        ];
    }

    /**
     * هل يمكن الانتقال من حالة لأخرى؟
     */
    public static function canTransition(string $from, string $to): bool
    {
        // لا يمكن التغيير من حالة نهائية
        if (in_array($from, self::FINAL_STATUSES)) {
            return false;
        }

        // التحولات المسموحة
        $allowed = [
            self::STATUS_CREATED => [self::STATUS_PICKED_UP, self::STATUS_CANCELLED],
            self::STATUS_PICKED_UP => [self::STATUS_IN_TRANSIT, self::STATUS_RETURNED, self::STATUS_CANCELLED],
            self::STATUS_IN_TRANSIT => [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_RETURNED, self::STATUS_CANCELLED],
            self::STATUS_OUT_FOR_DELIVERY => [self::STATUS_DELIVERED, self::STATUS_FAILED, self::STATUS_RETURNED],
            self::STATUS_FAILED => [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_RETURNED],
        ];

        return in_array($to, $allowed[$from] ?? []);
    }

    // =====================
    // Query Helpers
    // =====================

    /**
     * الحصول على آخر حالة لشحنة معينة
     */
    public static function getLatestForPurchase(int $purchaseId, ?int $merchantId = null): ?self
    {
        $query = static::where('purchase_id', $purchaseId);

        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        return $query->latestFirst()->first();
    }

    /**
     * الحصول على آخر حالة برقم التتبع
     */
    public static function getLatestByTracking(string $trackingNumber): ?self
    {
        return static::byTracking($trackingNumber)->latestFirst()->first();
    }

    /**
     * الحصول على تاريخ التتبع الكامل
     */
    public static function getHistoryForPurchase(int $purchaseId, ?int $merchantId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('purchase_id', $purchaseId);

        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        return $query->latestFirst()->get();
    }

    /**
     * الحصول على تاريخ التتبع برقم التتبع
     */
    public static function getHistoryByTracking(string $trackingNumber): \Illuminate\Database\Eloquent\Collection
    {
        return static::byTracking($trackingNumber)->latestFirst()->get();
    }

    /**
     * هل الشحنة تم تسليمها؟
     */
    public static function isDelivered(int $purchaseId, ?int $merchantId = null): bool
    {
        $latest = static::getLatestForPurchase($purchaseId, $merchantId);
        return $latest && $latest->status === self::STATUS_DELIVERED;
    }

    /**
     * هل يوجد تتبع لهذا الطلب؟
     */
    public static function hasTracking(int $purchaseId, ?int $merchantId = null): bool
    {
        $query = static::where('purchase_id', $purchaseId);

        if ($merchantId) {
            $query->where('merchant_id', $merchantId);
        }

        return $query->exists();
    }
}
