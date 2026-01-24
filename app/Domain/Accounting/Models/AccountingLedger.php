<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\Purchase;
use App\Models\MerchantPurchase;
use App\Models\User;

/**
 * AccountingLedger Model - Financial transaction log
 *
 * Domain: Accounting
 * Table: accounting_ledger
 *
 * Records every financial transaction between parties:
 * - debt: New debt at checkout
 * - collection: COD collection
 * - settlement: Debt settlement
 * - fee: Fees (commission, delivery)
 * - refund: Refund
 * - reversal: Cancellation
 */
class AccountingLedger extends Model
{
    protected $table = 'accounting_ledger';

    protected $fillable = [
        'transaction_ref', 'purchase_id', 'merchant_purchase_id',
        'from_party_id', 'to_party_id', 'amount', 'monetary_unit_code',
        'transaction_type', 'entry_type', 'direction', 'debt_status',
        'original_entry_id', 'description', 'description_ar', 'metadata',
        'status', 'transaction_date', 'settled_at', 'created_by',
        'settled_by', 'settlement_batch_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'transaction_date' => 'datetime',
        'settled_at' => 'datetime',
    ];

    // === Transaction Type Constants ===
    const TYPE_DEBT = 'debt';
    const TYPE_COLLECTION = 'collection';
    const TYPE_SETTLEMENT = 'settlement';
    const TYPE_FEE = 'fee';
    const TYPE_REFUND = 'refund';
    const TYPE_REVERSAL = 'reversal';
    const TYPE_ADJUSTMENT = 'adjustment';

    // === Entry Type Constants ===
    const ENTRY_SALE_REVENUE = 'SALE_REVENUE';
    const ENTRY_COMMISSION_EARNED = 'COMMISSION_EARNED';
    const ENTRY_TAX_COLLECTED = 'TAX_COLLECTED';
    const ENTRY_SHIPPING_FEE_PLATFORM = 'SHIPPING_FEE_PLATFORM';
    const ENTRY_SHIPPING_FEE_MERCHANT = 'SHIPPING_FEE_MERCHANT';
    const ENTRY_COURIER_FEE = 'COURIER_FEE';
    const ENTRY_COD_PENDING = 'COD_PENDING';
    const ENTRY_COD_COLLECTED = 'COD_COLLECTED';
    const ENTRY_SETTLEMENT_PAYMENT = 'SETTLEMENT_PAYMENT';
    const ENTRY_REFUND = 'REFUND';
    const ENTRY_CANCELLATION_REVERSAL = 'CANCELLATION_REVERSAL';
    const ENTRY_ADJUSTMENT = 'ADJUSTMENT';
    const ENTRY_PLATFORM_FEE = 'PLATFORM_FEE';

    // === Direction Constants ===
    const DIRECTION_DEBIT = 'DEBIT';
    const DIRECTION_CREDIT = 'CREDIT';

    // === Debt Status Constants ===
    const DEBT_PENDING = 'PENDING';
    const DEBT_SETTLED = 'SETTLED';
    const DEBT_CANCELLED = 'CANCELLED';
    const DEBT_REVERSED = 'REVERSED';

    // === Status Constants ===
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // =========================================================================
    // BOOT
    // =========================================================================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ledger) {
            if (empty($ledger->transaction_ref)) {
                $ledger->transaction_ref = self::generateTransactionRef();
            }
            if (empty($ledger->transaction_date)) {
                $ledger->transaction_date = now();
            }
        });
    }

    public static function generateTransactionRef(): string
    {
        return 'TXN-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function fromParty(): BelongsTo
    {
        return $this->belongsTo(AccountParty::class, 'from_party_id');
    }

    public function toParty(): BelongsTo
    {
        return $this->belongsTo(AccountParty::class, 'to_party_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function merchantPurchase(): BelongsTo
    {
        return $this->belongsTo(MerchantPurchase::class);
    }

    public function settlementBatch(): BelongsTo
    {
        return $this->belongsTo(SettlementBatch::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function settledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    public function originalEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_entry_id');
    }

    public function reversalEntries()
    {
        return $this->hasMany(self::class, 'original_entry_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeDebts($query)
    {
        return $query->where('transaction_type', self::TYPE_DEBT);
    }

    public function scopeSettlements($query)
    {
        return $query->where('transaction_type', self::TYPE_SETTLEMENT);
    }

    public function scopeForParty($query, int $partyId)
    {
        return $query->where(function ($q) use ($partyId) {
            $q->where('from_party_id', $partyId)
              ->orWhere('to_party_id', $partyId);
        });
    }

    public function scopeBetweenParties($query, int $partyId1, int $partyId2)
    {
        return $query->where(function ($q) use ($partyId1, $partyId2) {
            $q->where(function ($sub) use ($partyId1, $partyId2) {
                $sub->where('from_party_id', $partyId1)
                    ->where('to_party_id', $partyId2);
            })->orWhere(function ($sub) use ($partyId1, $partyId2) {
                $sub->where('from_party_id', $partyId2)
                    ->where('to_party_id', $partyId1);
            });
        });
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeOfEntryType($query, string $entryType)
    {
        return $query->where('entry_type', $entryType);
    }

    public function scopeDebtPending($query)
    {
        return $query->where('debt_status', self::DEBT_PENDING);
    }

    public function scopeDebtSettled($query)
    {
        return $query->where('debt_status', self::DEBT_SETTLED);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

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

    public function isDebt(): bool
    {
        return $this->transaction_type === self::TYPE_DEBT;
    }

    public function isSettlement(): bool
    {
        return $this->transaction_type === self::TYPE_SETTLEMENT;
    }

    public function isCredit(): bool
    {
        return $this->direction === self::DIRECTION_CREDIT;
    }

    public function isDebit(): bool
    {
        return $this->direction === self::DIRECTION_DEBIT;
    }

    public function isDebtPending(): bool
    {
        return $this->debt_status === self::DEBT_PENDING;
    }

    public function isDebtSettled(): bool
    {
        return $this->debt_status === self::DEBT_SETTLED;
    }

    public function markAsCompleted(?int $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'settled_at' => now(),
            'settled_by' => $userId,
        ]);
    }

    public function cancel(?int $userId = null, ?string $reason = null): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['cancellation_reason'] = $reason;
        $metadata['cancelled_at'] = now()->toIso8601String();

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'metadata' => $metadata,
        ]);
    }

    public function settleDebt(?int $userId = null): void
    {
        $this->update([
            'debt_status' => self::DEBT_SETTLED,
            'settled_at' => now(),
            'settled_by' => $userId,
        ]);
    }

    public function reverseDebt(): void
    {
        $this->update([
            'debt_status' => self::DEBT_REVERSED,
        ]);
    }

    public function getTypeNameAr(): string
    {
        return match ($this->transaction_type) {
            self::TYPE_DEBT => 'دين',
            self::TYPE_COLLECTION => 'تحصيل',
            self::TYPE_SETTLEMENT => 'تسوية',
            self::TYPE_FEE => 'رسوم',
            self::TYPE_REFUND => 'استرداد',
            self::TYPE_REVERSAL => 'إلغاء',
            self::TYPE_ADJUSTMENT => 'تعديل',
            default => 'غير محدد',
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }

    public function getFormattedAmount(): string
    {
        return $this->monetary_unit_code . ' ' . number_format($this->amount, 2);
    }
}
