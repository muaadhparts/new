<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MerchantSettlement Model
 *
 * Represents a settlement cycle for a merchant.
 * All financial data is derived from MerchantPurchase records.
 *
 * ARCHITECTURAL PRINCIPLE (2026-01-09):
 * - settlement_type determines direction of payment:
 *   - platform_pays_merchant: Platform collected more, owes merchant
 *   - merchant_pays_platform: Merchant collected more, owes platform
 *
 * Status Workflow:
 * draft -> pending -> approved -> paid
 *                  -> cancelled
 */
class MerchantSettlement extends Model
{
    protected $table = 'merchant_settlements';

    protected $fillable = [
        'user_id',
        'settlement_number',
        'period_start',
        'period_end',
        'total_sales',
        'total_commission',
        'total_tax',
        'total_shipping',
        'total_packing',
        'total_deductions',
        'net_payable',
        'platform_owes_merchant',
        'merchant_owes_platform',
        'orders_count',
        'items_count',
        'status',
        'settlement_type',
        'payment_method',
        'payment_reference',
        'payment_date',
        'created_by',
        'approved_by',
        'paid_by',
        'approved_at',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'total_sales' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_shipping' => 'decimal:2',
        'total_packing' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_payable' => 'decimal:2',
        'platform_owes_merchant' => 'decimal:2',
        'merchant_owes_platform' => 'decimal:2',
        'payment_date' => 'datetime',
        'approved_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    // Settlement type constants
    const TYPE_PLATFORM_PAYS_MERCHANT = 'platform_pays_merchant';
    const TYPE_MERCHANT_PAYS_PLATFORM = 'merchant_pays_platform';

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MerchantSettlementItem::class);
    }

    public function merchantPurchases(): HasMany
    {
        return $this->hasMany(MerchantPurchase::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    // =========================================================================
    // SETTLEMENT NUMBER GENERATION
    // =========================================================================

    public static function generateSettlementNumber(): string
    {
        $date = now()->format('Ymd');
        $lastSettlement = self::where('settlement_number', 'like', "MS-{$date}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastSettlement) {
            $lastNumber = (int) substr($lastSettlement->settlement_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('MS-%s-%04d', $date, $newNumber);
    }

    // =========================================================================
    // STATUS WORKFLOW
    // =========================================================================

    public function submit(?int $submittedBy = null): bool
    {
        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        $this->status = self::STATUS_PENDING;
        $this->save();

        return true;
    }

    public function approve(?int $approvedBy = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_APPROVED;
        $this->approved_by = $approvedBy;
        $this->approved_at = now();
        $this->save();

        return true;
    }

    public function markAsPaid(string $paymentMethod, ?string $reference = null, ?int $paidBy = null): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        $this->status = self::STATUS_PAID;
        $this->payment_method = $paymentMethod;
        $this->payment_reference = $reference;
        $this->payment_date = now();
        $this->paid_by = $paidBy;
        $this->paid_at = now();
        $this->save();

        // Mark all associated MerchantPurchases as settled
        MerchantPurchase::where('merchant_settlement_id', $this->id)
            ->update([
                'settlement_status' => 'settled',
                'settled_at' => now(),
            ]);

        // Log platform revenue
        PlatformRevenueLog::create([
            'date' => now()->toDateString(),
            'source' => 'commission',
            'reference_type' => 'MerchantSettlement',
            'reference_id' => $this->id,
            'amount' => $this->total_commission,
            'description' => "Commission from settlement {$this->settlement_number}",
        ]);

        return true;
    }

    public function cancel(?int $cancelledBy = null, ?string $reason = null): bool
    {
        if (!in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING])) {
            return false;
        }

        // Unlink MerchantPurchases
        MerchantPurchase::where('merchant_settlement_id', $this->id)
            ->update([
                'settlement_status' => 'unsettled',
                'merchant_settlement_id' => null,
            ]);

        $this->status = self::STATUS_CANCELLED;
        $this->notes = $reason ? ($this->notes . "\nCancelled: " . $reason) : $this->notes;
        $this->save();

        return true;
    }

    // =========================================================================
    // STATUS CHECKS
    // =========================================================================

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeEdited(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeByMerchant($query, int $merchantId)
    {
        return $query->where('user_id', $merchantId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->where('period_start', '>=', $startDate)
                     ->where('period_end', '<=', $endDate);
    }

    // =========================================================================
    // LABELS
    // =========================================================================

    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_DRAFT => __('Draft'),
            self::STATUS_PENDING => __('Pending Approval'),
            self::STATUS_APPROVED => __('Approved'),
            self::STATUS_PAID => __('Paid'),
            self::STATUS_CANCELLED => __('Cancelled'),
        ];

        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusBadgeClass(): string
    {
        $classes = [
            self::STATUS_DRAFT => 'bg-secondary',
            self::STATUS_PENDING => 'bg-warning',
            self::STATUS_APPROVED => 'bg-info',
            self::STATUS_PAID => 'bg-success',
            self::STATUS_CANCELLED => 'bg-danger',
        ];

        return $classes[$this->status] ?? 'bg-secondary';
    }

    // =========================================================================
    // SETTLEMENT TYPE METHODS
    // =========================================================================

    /**
     * Check if platform pays merchant
     */
    public function isPlatformPaysMerchant(): bool
    {
        return $this->settlement_type === self::TYPE_PLATFORM_PAYS_MERCHANT;
    }

    /**
     * Check if merchant pays platform
     */
    public function isMerchantPaysPlatform(): bool
    {
        return $this->settlement_type === self::TYPE_MERCHANT_PAYS_PLATFORM;
    }

    /**
     * Get settlement type label
     */
    public function getSettlementTypeLabel(): string
    {
        $labels = [
            self::TYPE_PLATFORM_PAYS_MERCHANT => __('Platform Pays Merchant'),
            self::TYPE_MERCHANT_PAYS_PLATFORM => __('Merchant Pays Platform'),
        ];

        return $labels[$this->settlement_type] ?? __('Unknown');
    }

    /**
     * Get settlement direction icon
     */
    public function getSettlementDirectionIcon(): string
    {
        return $this->isPlatformPaysMerchant()
            ? 'fa-arrow-right text-success' // Money goes to merchant
            : 'fa-arrow-left text-warning'; // Money comes from merchant
    }

    /**
     * Get balance summary for display
     */
    public function getBalanceSummary(): array
    {
        return [
            'platform_owes_merchant' => (float) $this->platform_owes_merchant,
            'merchant_owes_platform' => (float) $this->merchant_owes_platform,
            'net_payable' => (float) $this->net_payable,
            'settlement_type' => $this->settlement_type,
            'direction' => $this->isPlatformPaysMerchant() ? 'platform_to_merchant' : 'merchant_to_platform',
            'direction_label' => $this->getSettlementTypeLabel(),
        ];
    }
}
