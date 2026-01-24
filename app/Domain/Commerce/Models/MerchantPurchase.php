<?php

namespace App\Domain\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Shipping;
use App\Models\Courier;
use App\Models\MerchantBranch;
use App\Models\MerchantPayment;
use App\Models\SettlementBatch;

/**
 * MerchantPurchase Model - Per-merchant order record
 *
 * Domain: Commerce
 * Table: merchant_purchases
 *
 * WARNING: cod_amount CALCULATION RULES
 * cod_amount = Accounting bucket for COD (items_price + delivery_fee)
 * MUST be calculated via PaymentAccountingService::calculateMerchantPurchaseCodAmount()
 *
 * @property int $id
 * @property int $purchase_id
 * @property int $user_id
 * @property array $cart
 * @property int $qty
 * @property float $price
 * @property string|null $purchase_number
 * @property string $status
 * @property float $commission_amount
 * @property float $tax_amount
 * @property float $net_amount
 */
class MerchantPurchase extends Model
{
    protected $table = 'merchant_purchases';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'purchase_id', 'user_id', 'cart', 'qty', 'price', 'purchase_number', 'status',
        'commission_amount', 'tax_amount', 'shipping_cost', 'courier_fee',
        'platform_shipping_fee', 'net_amount',
        // Debt Ledger
        'merchant_owes_platform', 'platform_owes_merchant', 'courier_owes_platform',
        'shipping_company_owes_merchant', 'shipping_company_owes_platform',
        // COD & Money Tracking
        'cod_amount', 'money_holder', 'delivery_method', 'delivery_provider',
        'collection_status', 'collected_at', 'collected_by',
        // Ownership & Gateway
        'payment_method', 'payment_type', 'shipping_type', 'money_received_by',
        'payment_owner_id', 'shipping_owner_id', 'payment_gateway_id', 'shipping_id',
        'courier_id', 'merchant_branch_id', 'settlement_status', 'settled_at',
        'merchant_settlement_id',
    ];

    protected $casts = [
        'cart' => 'array',
        'commission_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'courier_fee' => 'decimal:2',
        'platform_shipping_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'price' => 'decimal:2',
        'merchant_owes_platform' => 'decimal:2',
        'platform_owes_merchant' => 'decimal:2',
        'courier_owes_platform' => 'decimal:2',
        'shipping_company_owes_merchant' => 'decimal:2',
        'shipping_company_owes_platform' => 'decimal:2',
        'cod_amount' => 'decimal:2',
        'settled_at' => 'datetime',
        'collected_at' => 'datetime',
        'payment_owner_id' => 'integer',
        'shipping_owner_id' => 'integer',
    ];

    // === Constants ===
    const MONEY_HOLDER_PLATFORM = 'platform';
    const MONEY_HOLDER_MERCHANT = 'merchant';
    const MONEY_HOLDER_COURIER = 'courier';
    const MONEY_HOLDER_SHIPPING = 'shipping_company';
    const MONEY_HOLDER_PENDING = 'pending';

    const DELIVERY_LOCAL_COURIER = 'local_courier';
    const DELIVERY_SHIPPING_COMPANY = 'shipping_company';
    const DELIVERY_PICKUP = 'pickup';
    const DELIVERY_DIGITAL = 'digital';
    const DELIVERY_NONE = 'none';

    const COLLECTION_NOT_APPLICABLE = 'not_applicable';
    const COLLECTION_PENDING = 'pending';
    const COLLECTION_COLLECTED = 'collected';
    const COLLECTION_FAILED = 'failed';

    // =========================================================================
    // RELATIONS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withDefault();
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id')->withDefault();
    }

    public function paymentGateway(): BelongsTo
    {
        return $this->belongsTo(MerchantPayment::class, 'payment_gateway_id')->withDefault();
    }

    public function shipping(): BelongsTo
    {
        return $this->belongsTo(Shipping::class, 'shipping_id')->withDefault();
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'courier_id')->withDefault();
    }

    public function merchantBranch(): BelongsTo
    {
        return $this->belongsTo(MerchantBranch::class, 'merchant_branch_id')->withDefault();
    }

    public function settlementBatch(): BelongsTo
    {
        return $this->belongsTo(SettlementBatch::class, 'merchant_settlement_id');
    }

    // =========================================================================
    // STATUS CHECK METHODS
    // =========================================================================

    public function isCourierDelivery(): bool
    {
        return $this->shipping_type === 'courier';
    }

    public function isShippingDelivery(): bool
    {
        return in_array($this->shipping_type, ['platform', 'merchant']);
    }

    public function isPlatformPayment(): bool
    {
        return $this->payment_owner_id === 0;
    }

    public function isMerchantPayment(): bool
    {
        return $this->payment_owner_id > 0;
    }

    public function isPlatformShipping(): bool
    {
        return $this->shipping_owner_id === 0;
    }

    public function isMerchantShipping(): bool
    {
        return $this->shipping_owner_id > 0;
    }

    public function moneyReceivedByPlatform(): bool
    {
        return $this->isPlatformPayment();
    }

    public function moneyReceivedByMerchant(): bool
    {
        return $this->isMerchantPayment();
    }

    // =========================================================================
    // CALCULATION METHODS
    // =========================================================================

    public function calculateNetAmount(): float
    {
        return $this->price - $this->commission_amount - $this->tax_amount;
    }

    public function calculatePlatformServicesTotal(): float
    {
        if ($this->isPlatformShipping()) {
            return (float) $this->platform_shipping_fee;
        }

        return 0;
    }

    public function calculateMerchantOwes(): float
    {
        if (!$this->moneyReceivedByMerchant()) {
            return 0;
        }

        return (float) $this->commission_amount
            + (float) $this->tax_amount
            + $this->calculatePlatformServicesTotal();
    }

    public function calculatePlatformOwes(): float
    {
        if (!$this->moneyReceivedByPlatform()) {
            return 0;
        }

        return (float) $this->net_amount;
    }

    /**
     * @deprecated Use PaymentAccountingService::calculateDebtLedger() instead
     */
    public function recalculateFinancialBalance(): self
    {
        throw new \RuntimeException(
            'Direct debt modification is forbidden. ' .
            'Use PaymentAccountingService::calculateDebtLedger() for new records ' .
            'or settlement methods for existing debts.'
        );
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

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

    public function scopeWherePlatformOwesMerchant($query)
    {
        return $query->where('platform_owes_merchant', '>', 0);
    }

    public function scopeWhereMerchantOwesPlatform($query)
    {
        return $query->where('merchant_owes_platform', '>', 0);
    }

    public function scopeUnsettled($query)
    {
        return $query->whereNull('settlement_status')
            ->orWhere('settlement_status', '!=', 'settled');
    }

    public function scopeSettled($query)
    {
        return $query->where('settlement_status', 'settled');
    }

    // =========================================================================
    // CART DATA ACCESS METHODS
    // =========================================================================

    public function getCartItems(): array
    {
        $cart = $this->cart;

        if (is_string($cart)) {
            $cart = json_decode($cart, true);
        }

        return $cart['items'] ?? $cart ?? [];
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

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
