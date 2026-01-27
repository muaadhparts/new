<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Models\Courier;

/**
 * Withdraw Model - Withdrawal requests
 *
 * Domain: Accounting
 * Table: withdraws
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $method
 * @property string|null $acc_email
 * @property string|null $iban
 * @property string|null $country
 * @property string|null $acc_name
 * @property string|null $address
 * @property string|null $swift
 * @property string|null $reference
 * @property float $amount
 * @property float $fee
 * @property string $status
 */
class Withdraw extends Model
{
    protected $table = 'withdraws';

    protected $fillable = [
        'user_id',
        'method',
        'acc_email',
        'iban',
        'country',
        'acc_name',
        'address',
        'swift',
        'reference',
        'amount',
        'fee',
        'status',
        'type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
    ];

    // === Status Constants (per schema enum) ===
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';

    // === Type Constants (per schema enum) ===
    const TYPE_USER = 'user';
    const TYPE_MERCHANT = 'merchant';
    const TYPE_COURIER = 'courier';

    // === Method Constants ===
    const METHOD_BANK_TRANSFER = 'bank_transfer';
    const METHOD_PAYPAL = 'paypal';
    const METHOD_CASH = 'cash';

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'user_id')->withDefault();
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

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
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

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Get net amount after fee
     */
    public function getNetAmount(): float
    {
        return $this->amount - ($this->fee ?? 0);
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_COMPLETED => __('Completed'),
            self::STATUS_REJECTED => __('Rejected'),
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'secondary',
        };
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_USER => __('User'),
            self::TYPE_MERCHANT => __('Merchant'),
            self::TYPE_COURIER => __('Courier'),
            default => $this->type,
        };
    }
}
