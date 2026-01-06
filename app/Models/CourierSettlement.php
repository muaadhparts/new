<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierSettlement extends Model
{
    protected $table = 'courier_settlements';

    protected $fillable = [
        'courier_id',
        'amount',
        'type',
        'status',
        'payment_method',
        'reference_number',
        'notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    const TYPE_PAY_TO_COURIER = 'pay_to_courier';
    const TYPE_RECEIVE_FROM_COURIER = 'receive_from_courier';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function transactions()
    {
        return $this->hasMany(CourierTransaction::class, 'settlement_id');
    }

    public function process(?int $processedBy = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $courier = $this->courier;

        if ($this->type === self::TYPE_PAY_TO_COURIER) {
            $courier->recordSettlementPaid($this->amount);
            $transactionType = CourierTransaction::TYPE_SETTLEMENT_PAID;
        } else {
            $courier->recordSettlementReceived($this->amount);
            $transactionType = CourierTransaction::TYPE_SETTLEMENT_RECEIVED;
        }

        CourierTransaction::recordTransaction(
            $this->courier_id,
            $transactionType,
            $this->amount,
            null,
            $this->id,
            'Settlement #' . $this->id,
            $processedBy
        );

        $this->status = self::STATUS_COMPLETED;
        $this->processed_by = $processedBy;
        $this->processed_at = now();
        $this->save();

        return true;
    }

    public function cancel(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_CANCELLED;
        $this->save();

        return true;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isPaymentToCourier(): bool
    {
        return $this->type === self::TYPE_PAY_TO_COURIER;
    }

    public function isReceiveFromCourier(): bool
    {
        return $this->type === self::TYPE_RECEIVE_FROM_COURIER;
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeByCourier($query, $courierId)
    {
        return $query->where('courier_id', $courierId);
    }

    public function getTypeLabel(): string
    {
        return $this->type === self::TYPE_PAY_TO_COURIER
            ? __('Payment to Courier')
            : __('Receive from Courier');
    }

    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_COMPLETED => __('Completed'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];

        return $labels[$this->status] ?? $this->status;
    }
}
