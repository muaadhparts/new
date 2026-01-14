<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * AccountingLedger - سجل الحركات المالية
 *
 * يسجل كل حركة مالية بين الأطراف:
 * - debt: دين جديد عند الشيك-آوت
 * - collection: تحصيل COD
 * - settlement: تسوية دين
 * - fee: رسوم (عمولة، توصيل)
 * - refund: استرداد
 * - reversal: إلغاء
 */
class AccountingLedger extends Model
{
    protected $table = 'accounting_ledger';

    protected $fillable = [
        'transaction_ref',
        'purchase_id',
        'merchant_purchase_id',
        'from_party_id',
        'to_party_id',
        'amount',
        'currency',
        'transaction_type',
        'entry_type',
        'direction',
        'debt_status',
        'original_entry_id',
        'description',
        'description_ar',
        'metadata',
        'status',
        'transaction_date',
        'settled_at',
        'created_by',
        'settled_by',
        'settlement_batch_id',
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

    // === Entry Type Constants (نوع القيد المحدد) ===
    const ENTRY_SALE_REVENUE = 'SALE_REVENUE';                // إيراد مبيعات
    const ENTRY_COMMISSION_EARNED = 'COMMISSION_EARNED';      // عمولة مكتسبة
    const ENTRY_TAX_COLLECTED = 'TAX_COLLECTED';              // ضريبة محصلة
    const ENTRY_SHIPPING_FEE_PLATFORM = 'SHIPPING_FEE_PLATFORM';  // رسم شحن (منصة)
    const ENTRY_SHIPPING_FEE_MERCHANT = 'SHIPPING_FEE_MERCHANT';  // رسم شحن (تاجر)
    const ENTRY_COURIER_FEE = 'COURIER_FEE';                  // رسم توصيل
    const ENTRY_COD_PENDING = 'COD_PENDING';                  // COD معلق
    const ENTRY_COD_COLLECTED = 'COD_COLLECTED';              // COD محصل
    const ENTRY_SETTLEMENT_PAYMENT = 'SETTLEMENT_PAYMENT';    // دفعة تسوية
    const ENTRY_REFUND = 'REFUND';                            // استرداد
    const ENTRY_CANCELLATION_REVERSAL = 'CANCELLATION_REVERSAL';  // عكس إلغاء
    const ENTRY_ADJUSTMENT = 'ADJUSTMENT';                    // تعديل يدوي
    const ENTRY_PLATFORM_FEE = 'PLATFORM_FEE';                // رسوم منصة إضافية

    // === Direction Constants (اتجاه القيد) ===
    const DIRECTION_DEBIT = 'DEBIT';    // مدين
    const DIRECTION_CREDIT = 'CREDIT';  // دائن

    // === Debt Status Constants (حالة الدين) ===
    const DEBT_PENDING = 'PENDING';     // معلق
    const DEBT_SETTLED = 'SETTLED';     // مسدد
    const DEBT_CANCELLED = 'CANCELLED'; // ملغي
    const DEBT_REVERSED = 'REVERSED';   // معكوس

    // === Status Constants ===
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // ═══════════════════════════════════════════════════════════════
    // BOOT
    // ═══════════════════════════════════════════════════════════════

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

    /**
     * توليد رقم مرجعي فريد
     */
    public static function generateTransactionRef(): string
    {
        return 'TXN-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

    // ═══════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════

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

    // ═══════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════

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

    // === Entry Type Scopes ===
    public function scopeOfEntryType($query, string $entryType)
    {
        return $query->where('entry_type', $entryType);
    }

    public function scopeSaleRevenue($query)
    {
        return $query->where('entry_type', self::ENTRY_SALE_REVENUE);
    }

    public function scopeCommissionEarned($query)
    {
        return $query->where('entry_type', self::ENTRY_COMMISSION_EARNED);
    }

    public function scopeTaxCollected($query)
    {
        return $query->where('entry_type', self::ENTRY_TAX_COLLECTED);
    }

    // === Debt Status Scopes ===
    public function scopeDebtPending($query)
    {
        return $query->where('debt_status', self::DEBT_PENDING);
    }

    public function scopeDebtSettled($query)
    {
        return $query->where('debt_status', self::DEBT_SETTLED);
    }

    // === Original Entry Relationship ===
    public function originalEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_entry_id');
    }

    public function reversalEntries()
    {
        return $this->hasMany(self::class, 'original_entry_id');
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

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

    /**
     * تحويل الحالة لمكتملة
     */
    public function markAsCompleted(?int $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'settled_at' => now(),
            'settled_by' => $userId,
        ]);
    }

    /**
     * إلغاء المعاملة
     */
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

    /**
     * الحصول على اسم نوع المعاملة بالعربية
     */
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

    /**
     * الحصول على لون الحالة
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }

    /**
     * الحصول على اسم الحالة بالعربية
     */
    public function getStatusNameAr(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'قيد الانتظار',
            self::STATUS_COMPLETED => 'مكتمل',
            self::STATUS_CANCELLED => 'ملغي',
            default => 'غير محدد',
        };
    }

    /**
     * تنسيق المبلغ
     */
    public function getFormattedAmount(): string
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }

    /**
     * الحصول على اسم نوع القيد بالعربية
     */
    public function getEntryTypeNameAr(): string
    {
        return match ($this->entry_type) {
            self::ENTRY_SALE_REVENUE => 'إيراد مبيعات',
            self::ENTRY_COMMISSION_EARNED => 'عمولة مكتسبة',
            self::ENTRY_TAX_COLLECTED => 'ضريبة محصلة',
            self::ENTRY_SHIPPING_FEE_PLATFORM => 'رسم شحن (منصة)',
            self::ENTRY_SHIPPING_FEE_MERCHANT => 'رسم شحن (تاجر)',
            self::ENTRY_COURIER_FEE => 'رسم توصيل',
            self::ENTRY_COD_PENDING => 'دفع عند الاستلام (معلق)',
            self::ENTRY_COD_COLLECTED => 'دفع عند الاستلام (محصل)',
            self::ENTRY_SETTLEMENT_PAYMENT => 'دفعة تسوية',
            self::ENTRY_REFUND => 'استرداد',
            self::ENTRY_CANCELLATION_REVERSAL => 'عكس إلغاء',
            self::ENTRY_ADJUSTMENT => 'تعديل يدوي',
            self::ENTRY_PLATFORM_FEE => 'رسوم منصة',
            default => 'غير محدد',
        };
    }

    /**
     * الحصول على اسم حالة الدين بالعربية
     */
    public function getDebtStatusNameAr(): string
    {
        return match ($this->debt_status) {
            self::DEBT_PENDING => 'معلق',
            self::DEBT_SETTLED => 'مسدد',
            self::DEBT_CANCELLED => 'ملغي',
            self::DEBT_REVERSED => 'معكوس',
            default => 'غير محدد',
        };
    }

    /**
     * الحصول على لون حالة الدين
     */
    public function getDebtStatusColor(): string
    {
        return match ($this->debt_status) {
            self::DEBT_PENDING => 'warning',
            self::DEBT_SETTLED => 'success',
            self::DEBT_CANCELLED => 'secondary',
            self::DEBT_REVERSED => 'danger',
            default => 'secondary',
        };
    }

    /**
     * الحصول على اتجاه القيد بالعربية
     */
    public function getDirectionNameAr(): string
    {
        return $this->direction === self::DIRECTION_DEBIT ? 'مدين' : 'دائن';
    }

    /**
     * هل القيد دائن؟
     */
    public function isCredit(): bool
    {
        return $this->direction === self::DIRECTION_CREDIT;
    }

    /**
     * هل القيد مدين؟
     */
    public function isDebit(): bool
    {
        return $this->direction === self::DIRECTION_DEBIT;
    }

    /**
     * هل الدين معلق؟
     */
    public function isDebtPending(): bool
    {
        return $this->debt_status === self::DEBT_PENDING;
    }

    /**
     * هل الدين مسدد؟
     */
    public function isDebtSettled(): bool
    {
        return $this->debt_status === self::DEBT_SETTLED;
    }

    /**
     * تسديد الدين
     */
    public function settleDebt(?int $userId = null): void
    {
        $this->update([
            'debt_status' => self::DEBT_SETTLED,
            'settled_at' => now(),
            'settled_by' => $userId,
        ]);
    }

    /**
     * عكس القيد (للإلغاء)
     */
    public function reverseDebt(): void
    {
        $this->update([
            'debt_status' => self::DEBT_REVERSED,
        ]);
    }
}
