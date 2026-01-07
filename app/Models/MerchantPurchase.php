<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantPurchase extends Model
{
    protected $table = 'merchant_purchases';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'purchase_id',
        'user_id',
        'qty',
        'price',
        'purchase_number',
        'status',
        'commission_amount',
        'tax_amount',
        'shipping_cost',
        'packing_cost',
        'courier_fee',
        'net_amount',
        'payment_type',
        'shipping_type',
        'money_received_by',
        'payment_gateway_id',
        'shipping_id',
        'courier_id',
        'merchant_location_id',
    ];

    protected $casts = [
        'commission_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'packing_cost' => 'decimal:2',
        'courier_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function merchant()
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id')->withDefault();
    }

    public function paymentGateway()
    {
        return $this->belongsTo(MerchantPayment::class, 'payment_gateway_id')->withDefault();
    }

    public function shipping()
    {
        return $this->belongsTo(Shipping::class, 'shipping_id')->withDefault();
    }

    public function courier()
    {
        return $this->belongsTo(Courier::class, 'courier_id')->withDefault();
    }

    public function merchantLocation()
    {
        return $this->belongsTo(MerchantLocation::class, 'merchant_location_id')->withDefault();
    }

    public function isMerchantPayment(): bool
    {
        return $this->payment_type === 'merchant';
    }

    public function isPlatformPayment(): bool
    {
        return $this->payment_type === 'platform';
    }

    public function isCourierDelivery(): bool
    {
        return $this->shipping_type === 'courier';
    }

    public function isShippingDelivery(): bool
    {
        return in_array($this->shipping_type, ['platform', 'merchant']);
    }

    public function calculateNetAmount(): float
    {
        return $this->price - $this->commission_amount - $this->tax_amount;
    }

    public function scopeByMerchant($query, $merchantId)
    {
        return $query->where('user_id', $merchantId);
    }

    public function scopeMerchantPayments($query)
    {
        return $query->where('payment_type', 'merchant');
    }

    public function scopePlatformPayments($query)
    {
        return $query->where('payment_type', 'platform');
    }

    public function scopeCourierDeliveries($query)
    {
        return $query->where('shipping_type', 'courier');
    }
}
