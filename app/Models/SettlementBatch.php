<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * SettlementBatch - دفعات التسوية
 *
 * يجمع عدة معاملات تسوية في دفعة واحدة.
 * مثال: دفع المنصة للتاجر مرة واحدة أسبوعياً.
 */
class SettlementBatch extends Model
{
    protected $table = 'settlement_batches';

    protected $fillable = [
        'batch_ref',
        'from_party_id',
        'to_party_id',
        'total_amount',
        'currency',
        'payment_method',
        'payment_reference',
        'notes',
        'status',
        'settlement_date',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'settlement_date' => 'date',
    ];

    // === Status Constants ===
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // === Payment Method Constants ===
    const PAYMENT_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_CASH = 'cash';
    const PAYMENT_WALLET = 'wallet';
    const PAYMENT_CHEQUE = 'cheque';

    // ═══════════════════════════════════════════════════════════════
    // BOOT
    // ═══════════════════════════════════════════════════════════════

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($batch) {
            if (empty($batch->batch_ref)) {
                $batch->batch_ref = self::generateBatchRef();
            }
        });
    }

    /**
     * توليد رقم مرجعي فريد
     */
    public static function generateBatchRef(): string
    {
        return 'SET-' . date('Ymd') . '-' . strtoupper(Str::random(5));
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

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(AccountingLedger::class, 'settlement_batch_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ═══════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForParty($query, int $partyId)
    {
        return $query->where(function ($q) use ($partyId) {
            $q->where('from_party_id', $partyId)
              ->orWhere('to_party_id', $partyId);
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // WORKFLOW METHODS
    // ═══════════════════════════════════════════════════════════════

    /**
     * إرسال للموافقة
     */
    public function submit(): void
    {
        if ($this->status !== self::STATUS_DRAFT) {
            throw new \InvalidArgumentException('Only draft batches can be submitted');
        }

        $this->update(['status' => self::STATUS_PENDING]);
    }

    /**
     * الموافقة والتنفيذ
     */
    public function approve(int $approvedBy): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \InvalidArgumentException('Only pending batches can be approved');
        }

        $this->update([
            'status' => self::STATUS_COMPLETED,
            'approved_by' => $approvedBy,
            'settlement_date' => now(),
        ]);

        // تحديث جميع الـ ledger entries المرتبطة
        $this->ledgerEntries()->update([
            'status' => AccountingLedger::STATUS_COMPLETED,
            'settled_at' => now(),
            'settled_by' => $approvedBy,
        ]);
    }

    /**
     * إلغاء الدفعة
     */
    public function cancel(?string $reason = null): void
    {
        if ($this->status === self::STATUS_COMPLETED) {
            throw new \InvalidArgumentException('Completed batches cannot be cancelled');
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $this->notes . "\n[CANCELLED]: " . $reason,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
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

    /**
     * الحصول على اسم الحالة بالعربية
     */
    public function getStatusNameAr(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'مسودة',
            self::STATUS_PENDING => 'في انتظار الموافقة',
            self::STATUS_COMPLETED => 'مكتمل',
            self::STATUS_CANCELLED => 'ملغي',
            default => 'غير محدد',
        };
    }

    /**
     * الحصول على لون الحالة
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_PENDING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }

    /**
     * تنسيق المبلغ
     */
    public function getFormattedAmount(): string
    {
        return $this->currency . ' ' . number_format($this->total_amount, 2);
    }

    /**
     * الحصول على اسم طريقة الدفع بالعربية
     */
    public function getPaymentMethodNameAr(): string
    {
        return match ($this->payment_method) {
            self::PAYMENT_BANK_TRANSFER => 'تحويل بنكي',
            self::PAYMENT_CASH => 'نقدي',
            self::PAYMENT_WALLET => 'محفظة',
            self::PAYMENT_CHEQUE => 'شيك',
            default => $this->payment_method ?? 'غير محدد',
        };
    }
}
