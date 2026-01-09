<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryCourier extends Model
{
    use HasFactory;

    protected $table = 'delivery_couriers';

    // Enable timestamps (columns added in 2026_01_09 migration)
    public $timestamps = true;

    protected $fillable = [
        'purchase_id',
        'merchant_id',
        'courier_id',
        'merchant_location_id',
        'service_area_id',
        'status',
        'delivery_fee',
        'cod_amount',
        'purchase_amount',
        'payment_method',
        'fee_status',
        'settlement_status',
        'delivered_at',
        'settled_at',
        'notes',
    ];

    protected $casts = [
        'delivery_fee' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'purchase_amount' => 'decimal:2',
        'delivered_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function courier()
    {
        return $this->belongsTo(Courier::class, 'courier_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function merchantLocation()
    {
        return $this->belongsTo(MerchantLocation::class, 'merchant_location_id')->withDefault();
    }

    public function servicearea()
    {
        return $this->belongsTo(CourierServiceArea::class, 'service_area_id')->withDefault();
    }

    public function isCod(): bool
    {
        return $this->payment_method === 'cod';
    }

    public function isOnlinePayment(): bool
    {
        return $this->payment_method === 'online';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSettled(): bool
    {
        return $this->settlement_status === 'settled';
    }

    public function markAsDelivered(): void
    {
        $this->status = 'delivered';
        $this->delivered_at = now();
        $this->save();

        if ($this->isOnlinePayment()) {
            $this->courier->recordDeliveryFeeEarned($this->delivery_fee);
            $this->fee_status = 'paid';
            $this->save();
        } elseif ($this->isCod()) {
            $totalCollected = $this->purchase_amount + $this->delivery_fee;
            $this->courier->recordCodCollection($this->purchase_amount);
            $this->fee_status = 'collected';
            $this->save();
        }
    }

    public function markAsSettled(): void
    {
        $this->settlement_status = 'settled';
        $this->settled_at = now();
        $this->save();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeUnsettled($query)
    {
        return $query->where('settlement_status', 'pending');
    }

    public function scopeCod($query)
    {
        return $query->where('payment_method', 'cod');
    }

    public function scopeOnlinePayment($query)
    {
        return $query->where('payment_method', 'online');
    }
}
