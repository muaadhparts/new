<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierTransaction extends Model
{
    protected $table = 'courier_transactions';

    protected $fillable = [
        'courier_id',
        'delivery_courier_id',
        'settlement_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    const TYPE_FEE_EARNED = 'fee_earned';
    const TYPE_COD_COLLECTED = 'cod_collected';
    const TYPE_SETTLEMENT_PAID = 'settlement_paid';
    const TYPE_SETTLEMENT_RECEIVED = 'settlement_received';
    const TYPE_ADJUSTMENT = 'adjustment';

    public function courier()
    {
        return $this->belongsTo(Courier::class);
    }

    public function deliveryCourier()
    {
        return $this->belongsTo(DeliveryCourier::class);
    }

    public function settlement()
    {
        return $this->belongsTo(CourierSettlement::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function recordTransaction(
        int $courierId,
        string $type,
        float $amount,
        ?int $deliveryCourierId = null,
        ?int $settlementId = null,
        ?string $notes = null,
        ?int $createdBy = null
    ): self {
        $courier = Courier::findOrFail($courierId);
        $balanceBefore = $courier->balance;

        $transaction = self::create([
            'courier_id' => $courierId,
            'delivery_courier_id' => $deliveryCourierId,
            'settlement_id' => $settlementId,
            'type' => $type,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $courier->balance,
            'notes' => $notes,
            'created_by' => $createdBy,
        ]);

        return $transaction;
    }

    public function isFeeEarned(): bool
    {
        return $this->type === self::TYPE_FEE_EARNED;
    }

    public function isCodCollected(): bool
    {
        return $this->type === self::TYPE_COD_COLLECTED;
    }

    public function isSettlement(): bool
    {
        return in_array($this->type, [self::TYPE_SETTLEMENT_PAID, self::TYPE_SETTLEMENT_RECEIVED]);
    }

    public function scopeByCourier($query, $courierId)
    {
        return $query->where('courier_id', $courierId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getTypeLabel(): string
    {
        $labels = [
            self::TYPE_FEE_EARNED => __('Delivery Fee Earned'),
            self::TYPE_COD_COLLECTED => __('COD Collected'),
            self::TYPE_SETTLEMENT_PAID => __('Settlement Paid'),
            self::TYPE_SETTLEMENT_RECEIVED => __('Settlement Received'),
            self::TYPE_ADJUSTMENT => __('Adjustment'),
        ];

        return $labels[$this->type] ?? $this->type;
    }
}
