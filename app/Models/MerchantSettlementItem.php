<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MerchantSettlementItem Model
 *
 * Represents a line item in a merchant settlement.
 * Links to MerchantPurchase as the source of truth.
 */
class MerchantSettlementItem extends Model
{
    protected $table = 'merchant_settlement_items';

    protected $fillable = [
        'merchant_settlement_id',
        'merchant_purchase_id',
        'sale_amount',
        'commission_amount',
        'tax_amount',
        'shipping_amount',
        'net_amount',
    ];

    protected $casts = [
        'sale_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(MerchantSettlement::class, 'merchant_settlement_id');
    }

    public function merchantPurchase(): BelongsTo
    {
        return $this->belongsTo(MerchantPurchase::class);
    }

    // =========================================================================
    // FACTORY
    // =========================================================================

    /**
     * Create a settlement item from a MerchantPurchase
     */
    public static function createFromMerchantPurchase(
        int $settlementId,
        MerchantPurchase $merchantPurchase
    ): self {
        return self::create([
            'merchant_settlement_id' => $settlementId,
            'merchant_purchase_id' => $merchantPurchase->id,
            'sale_amount' => $merchantPurchase->price,
            'commission_amount' => $merchantPurchase->commission_amount,
            'tax_amount' => $merchantPurchase->tax_amount,
            'shipping_amount' => $merchantPurchase->shipping_cost + $merchantPurchase->courier_fee,
            'net_amount' => $merchantPurchase->net_amount,
        ]);
    }
}
