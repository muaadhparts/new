<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * MerchantPurchase Model
 *
 * ARCHITECTURAL PRINCIPLE (2026-01-09):
 * - Sales are ALWAYS registered to merchant in this table with Gross, Commission, Net
 * - user_id = 0 → Platform service → Money goes to platform
 * - user_id ≠ 0 → Merchant service → Money goes directly to merchant
 *
 * Money Flow:
 * - Platform payment gateway (payment_owner_id = 0):
 *   → Money goes to platform → platform_owes_merchant = net_amount
 *
 * - Merchant payment gateway (payment_owner_id ≠ 0):
 *   → Money goes to merchant → merchant_owes_platform = commission + platform_services
 *
 * Platform Services = any service where owner_id = 0:
 *   - platform_shipping_fee (if shipping_owner_id = 0)
 *   - platform_packing_fee (if packing_owner_id = 0)
 */
class MerchantPurchase extends Model
{
    protected $table = 'merchant_purchases';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'purchase_id',
        'user_id',
        'cart',
        'qty',
        'price',
        'purchase_number',
        'status',
        'commission_amount',
        'tax_amount',
        'shipping_cost',
        'packing_cost',
        'courier_fee',
        'platform_shipping_fee',
        'platform_packing_fee',
        'net_amount',
        'merchant_owes_platform',
        'platform_owes_merchant',
        'payment_type',
        'shipping_type',
        'money_received_by',
        'payment_owner_id',
        'shipping_owner_id',
        'packing_owner_id',
        'payment_gateway_id',
        'shipping_id',
        'courier_id',
        'merchant_location_id',
        'settlement_status',
        'settled_at',
        'merchant_settlement_id',
    ];

    protected $casts = [
        'cart' => 'array',
        'commission_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'packing_cost' => 'decimal:2',
        'courier_fee' => 'decimal:2',
        'platform_shipping_fee' => 'decimal:2',
        'platform_packing_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'merchant_owes_platform' => 'decimal:2',
        'platform_owes_merchant' => 'decimal:2',
        'price' => 'decimal:2',
        'settled_at' => 'datetime',
        'payment_owner_id' => 'integer',
        'shipping_owner_id' => 'integer',
        'packing_owner_id' => 'integer',
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

    public function settlement()
    {
        return $this->belongsTo(MerchantSettlement::class, 'merchant_settlement_id');
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
        return $query->where('payment_owner_id', '>', 0);
    }

    public function scopePlatformPayments($query)
    {
        return $query->where('payment_owner_id', 0);
    }

    public function scopeCourierDeliveries($query)
    {
        return $query->where('shipping_type', 'courier');
    }

    // ============================================================
    // OWNER CHECKING METHODS
    // STRICT RULE: owner_id = 0 → Platform, owner_id > 0 → Merchant
    // NOTE: NULL is NOT allowed - always use 0 for platform
    // ============================================================

    /**
     * Check if payment is handled by platform (payment_owner_id = 0)
     */
    public function isPlatformPayment(): bool
    {
        return $this->payment_owner_id === 0;
    }

    /**
     * Check if payment is handled by merchant (payment_owner_id > 0)
     */
    public function isMerchantPayment(): bool
    {
        return $this->payment_owner_id > 0;
    }

    /**
     * Check if shipping is provided by platform (shipping_owner_id = 0)
     */
    public function isPlatformShipping(): bool
    {
        return $this->shipping_owner_id === 0;
    }

    /**
     * Check if shipping is provided by merchant (shipping_owner_id > 0)
     */
    public function isMerchantShipping(): bool
    {
        return $this->shipping_owner_id > 0;
    }

    /**
     * Check if packing is provided by platform (packing_owner_id = 0)
     */
    public function isPlatformPacking(): bool
    {
        return $this->packing_owner_id === 0;
    }

    /**
     * Check if packing is provided by merchant (packing_owner_id > 0)
     */
    public function isMerchantPacking(): bool
    {
        return $this->packing_owner_id > 0;
    }

    // ============================================================
    // MONEY FLOW METHODS
    // ============================================================

    /**
     * Check if money was received by platform
     * Platform receives money when using platform payment gateway
     */
    public function moneyReceivedByPlatform(): bool
    {
        return $this->isPlatformPayment();
    }

    /**
     * Check if money was received directly by merchant
     * Merchant receives money when using their own payment gateway
     */
    public function moneyReceivedByMerchant(): bool
    {
        return $this->isMerchantPayment();
    }

    /**
     * Calculate total platform services used by merchant
     * This is the amount merchant owes platform for using platform services
     */
    public function calculatePlatformServicesTotal(): float
    {
        $total = 0;

        // Platform shipping fee (if shipping_owner_id = 0)
        if ($this->isPlatformShipping()) {
            $total += (float) $this->platform_shipping_fee;
        }

        // Platform packing fee (if packing_owner_id = 0)
        if ($this->isPlatformPacking()) {
            $total += (float) $this->platform_packing_fee;
        }

        return $total;
    }

    /**
     * Calculate what merchant owes platform
     * Used when: Merchant receives payment directly (merchant payment gateway)
     * Formula: Commission + Tax + Platform Services (shipping/packing)
     */
    public function calculateMerchantOwes(): float
    {
        if (!$this->moneyReceivedByMerchant()) {
            return 0;
        }

        return (float) $this->commission_amount
            + (float) $this->tax_amount
            + $this->calculatePlatformServicesTotal();
    }

    /**
     * Calculate what platform owes merchant
     * Used when: Platform receives payment (platform payment gateway)
     * Formula: Gross - Commission - Tax - Platform Services = Net Amount
     */
    public function calculatePlatformOwes(): float
    {
        if (!$this->moneyReceivedByPlatform()) {
            return 0;
        }

        return (float) $this->net_amount;
    }

    /**
     * Recalculate and update financial balances
     * Call this after setting owner IDs to update owes amounts
     */
    public function recalculateFinancialBalance(): self
    {
        // Net amount = Gross - Commission - Tax
        $this->net_amount = (float) $this->price
            - (float) $this->commission_amount
            - (float) $this->tax_amount;

        if ($this->moneyReceivedByMerchant()) {
            // Merchant received money directly
            // Merchant owes platform: commission + tax + platform services
            $this->merchant_owes_platform = $this->calculateMerchantOwes();
            $this->platform_owes_merchant = 0;
        } else {
            // Platform received money
            // Platform owes merchant: net amount
            $this->platform_owes_merchant = $this->calculatePlatformOwes();
            $this->merchant_owes_platform = 0;
        }

        return $this;
    }

    // ============================================================
    // SCOPES FOR SETTLEMENT QUERIES
    // ============================================================

    /**
     * Scope: Where platform owes merchant (pending payout to merchant)
     */
    public function scopeWherePlatformOwesMerchant($query)
    {
        return $query->where('platform_owes_merchant', '>', 0);
    }

    /**
     * Scope: Where merchant owes platform (pending collection from merchant)
     */
    public function scopeWhereMerchantOwesPlatform($query)
    {
        return $query->where('merchant_owes_platform', '>', 0);
    }

    /**
     * Scope: Unsettled purchases (not yet settled)
     */
    public function scopeUnsettled($query)
    {
        return $query->whereNull('settlement_status')
            ->orWhere('settlement_status', '!=', 'settled');
    }

    /**
     * Scope: Settled purchases
     */
    public function scopeSettled($query)
    {
        return $query->where('settlement_status', 'settled');
    }

    // ============================================================
    // HELPER METHODS
    // ============================================================

    /**
     * Get human-readable payment owner label
     */
    public function getPaymentOwnerLabel(): string
    {
        if ($this->isPlatformPayment()) {
            return __('Platform');
        }

        if ($this->payment_owner_id === $this->user_id) {
            return __('Merchant');
        }

        return User::find($this->payment_owner_id)?->name ?? __('Unknown');
    }

    /**
     * Get human-readable shipping owner label
     */
    public function getShippingOwnerLabel(): string
    {
        if ($this->isPlatformShipping()) {
            return __('Platform');
        }

        if ($this->shipping_owner_id === $this->user_id) {
            return __('Merchant');
        }

        return User::find($this->shipping_owner_id)?->name ?? __('Unknown');
    }

    /**
     * Get money flow summary for display
     */
    public function getMoneyFlowSummary(): array
    {
        return [
            'gross_amount' => (float) $this->price,
            'commission' => (float) $this->commission_amount,
            'tax' => (float) $this->tax_amount,
            'platform_services' => $this->calculatePlatformServicesTotal(),
            'net_to_merchant' => (float) $this->net_amount,
            'money_received_by' => $this->moneyReceivedByPlatform() ? 'platform' : 'merchant',
            'merchant_owes_platform' => (float) $this->merchant_owes_platform,
            'platform_owes_merchant' => (float) $this->platform_owes_merchant,
        ];
    }
}
