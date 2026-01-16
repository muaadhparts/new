<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AccountBalance - أرصدة الحسابات المحسوبة
 *
 * يخزن الأرصدة المحسوبة بين الأطراف للتقارير السريعة.
 * يتم تحديثها تلقائياً عند إضافة معاملات جديدة.
 */
class AccountBalance extends Model
{
    protected $table = 'account_balances';

    protected $fillable = [
        'party_id',
        'counterparty_id',
        'balance_type',
        'total_amount',
        'pending_amount',
        'settled_amount',
        'monetary_unit_code',
        'transaction_count',
        'last_transaction_at',
        'last_calculated_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'pending_amount' => 'decimal:2',
        'settled_amount' => 'decimal:2',
        'transaction_count' => 'integer',
        'last_transaction_at' => 'datetime',
        'last_calculated_at' => 'datetime',
    ];

    // === Balance Type Constants ===
    const TYPE_RECEIVABLE = 'receivable';  // مستحق للطرف
    const TYPE_PAYABLE = 'payable';        // مستحق على الطرف

    // ═══════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════

    public function party(): BelongsTo
    {
        return $this->belongsTo(AccountParty::class, 'party_id');
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(AccountParty::class, 'counterparty_id');
    }

    // ═══════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════

    public function scopeReceivables($query)
    {
        return $query->where('balance_type', self::TYPE_RECEIVABLE);
    }

    public function scopePayables($query)
    {
        return $query->where('balance_type', self::TYPE_PAYABLE);
    }

    public function scopeForParty($query, int $partyId)
    {
        return $query->where('party_id', $partyId);
    }

    public function scopeWithCounterparty($query, int $counterpartyId)
    {
        return $query->where('counterparty_id', $counterpartyId);
    }

    public function scopeWithBalance($query)
    {
        return $query->where('total_amount', '>', 0);
    }

    // ═══════════════════════════════════════════════════════════════
    // FACTORY METHODS
    // ═══════════════════════════════════════════════════════════════

    /**
     * الحصول على/إنشاء رصيد بين طرفين
     */
    public static function getOrCreate(int $partyId, int $counterpartyId, string $balanceType): self
    {
        return static::firstOrCreate(
            [
                'party_id' => $partyId,
                'counterparty_id' => $counterpartyId,
                'balance_type' => $balanceType,
            ],
            [
                'total_amount' => 0,
                'pending_amount' => 0,
                'settled_amount' => 0,
                'monetary_unit_code' => \App\Services\MonetaryUnitService::BASE_MONETARY_UNIT,
                'transaction_count' => 0,
            ]
        );
    }

    /**
     * تحديث الرصيد بعد معاملة جديدة
     */
    public function recordTransaction(float $amount, bool $isSettlement = false): void
    {
        if ($isSettlement) {
            $this->pending_amount -= $amount;
            $this->settled_amount += $amount;
        } else {
            $this->pending_amount += $amount;
            $this->total_amount += $amount;
        }

        $this->transaction_count++;
        $this->last_transaction_at = now();
        $this->last_calculated_at = now();
        $this->save();
    }

    /**
     * إعادة حساب الرصيد من الـ Ledger
     */
    public function recalculateFromLedger(): void
    {
        $party = $this->party;
        $counterparty = $this->counterparty;

        // حساب الديون المستحقة
        $pendingDebts = AccountingLedger::where('from_party_id', $counterparty->id)
            ->where('to_party_id', $party->id)
            ->where('transaction_type', AccountingLedger::TYPE_DEBT)
            ->where('status', AccountingLedger::STATUS_PENDING)
            ->sum('amount');

        // حساب التسويات
        $settlements = AccountingLedger::where('from_party_id', $counterparty->id)
            ->where('to_party_id', $party->id)
            ->where('transaction_type', AccountingLedger::TYPE_SETTLEMENT)
            ->where('status', AccountingLedger::STATUS_COMPLETED)
            ->sum('amount');

        // حساب إجمالي الديون
        $totalDebts = AccountingLedger::where('from_party_id', $counterparty->id)
            ->where('to_party_id', $party->id)
            ->where('transaction_type', AccountingLedger::TYPE_DEBT)
            ->whereIn('status', [AccountingLedger::STATUS_PENDING, AccountingLedger::STATUS_COMPLETED])
            ->sum('amount');

        $transactionCount = AccountingLedger::betweenParties($party->id, $counterparty->id)->count();

        $this->update([
            'total_amount' => $totalDebts,
            'pending_amount' => $pendingDebts,
            'settled_amount' => $settlements,
            'transaction_count' => $transactionCount,
            'last_calculated_at' => now(),
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

    public function isReceivable(): bool
    {
        return $this->balance_type === self::TYPE_RECEIVABLE;
    }

    public function isPayable(): bool
    {
        return $this->balance_type === self::TYPE_PAYABLE;
    }

    public function hasBalance(): bool
    {
        return $this->pending_amount > 0;
    }

    /**
     * تنسيق المبلغ الإجمالي
     */
    public function getFormattedTotal(): string
    {
        return $this->currency . ' ' . number_format($this->total_amount, 2);
    }

    /**
     * تنسيق المبلغ المعلق
     */
    public function getFormattedPending(): string
    {
        return $this->currency . ' ' . number_format($this->pending_amount, 2);
    }

    /**
     * تنسيق المبلغ المسوى
     */
    public function getFormattedSettled(): string
    {
        return $this->currency . ' ' . number_format($this->settled_amount, 2);
    }

    /**
     * الحصول على نوع الرصيد بالعربية
     */
    public function getTypeNameAr(): string
    {
        return match ($this->balance_type) {
            self::TYPE_RECEIVABLE => 'مستحق له',
            self::TYPE_PAYABLE => 'مستحق عليه',
            default => 'غير محدد',
        };
    }
}
