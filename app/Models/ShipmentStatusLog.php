<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentStatusLog extends Model
{
    protected $fillable = [
        'purchase_id',
        'merchant_id',
        'tracking_number',
        'shipment_id',
        'company_name',
        'provider',
        'status',
        'status_ar',
        'message',
        'message_ar',
        'location',
        'latitude',
        'longitude',
        'status_date',
        'raw_data',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'status_date' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * العلاقة مع Purchase
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    /**
     * العلاقة مع Merchant (User)
     */
    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    /**
     * Get status translations
     */
    public static function getStatusTranslations()
    {
        return [
            'created' => 'تم إنشاء الشحنة',
            'picked_up' => 'تم الاستلام من المستودع',
            'in_transit' => 'في الطريق',
            'out_for_delivery' => 'خرج للتوصيل',
            'delivered' => 'تم التسليم',
            'failed' => 'فشل التوصيل',
            'returned' => 'مرتجع',
            'cancelled' => 'ملغي',
        ];
    }

    /**
     * Get status Arabic translation
     */
    public function getStatusArabic()
    {
        $translations = self::getStatusTranslations();
        return $translations[$this->status] ?? $this->status;
    }

    /**
     * Scope: Latest status for each tracking number
     */
    public function scopeLatestStatus($query)
    {
        return $query->orderBy('status_date', 'desc')
                     ->orderBy('created_at', 'desc');
    }

    /**
     * Scope: By tracking number
     */
    public function scopeByTracking($query, $trackingNumber)
    {
        return $query->where('tracking_number', $trackingNumber);
    }

    /**
     * Scope: By purchase
     */
    public function scopeByPurchase($query, $purchaseId)
    {
        return $query->where('purchase_id', $purchaseId);
    }

    /**
     * Scope: By merchant
     */
    public function scopeByMerchant($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

}
