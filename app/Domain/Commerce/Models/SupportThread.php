<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Identity\Models\Operator;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Support Thread Model
 *
 * Handles support tickets and disputes between users and admin.
 */
class SupportThread extends Model
{
    protected $table = 'support_threads';

    protected $fillable = [
        'subject',
        'user_id',
        'message',
        'type',
        'purchase_number',
    ];

    // ========================================
    // Relationships
    // ========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class)->withDefault();
    }

    public function supportMessages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'thread_id');
    }

    // ========================================
    // Scopes
    // ========================================

    public function scopeTickets($query)
    {
        return $query->where('type', 'Ticket');
    }

    public function scopeDisputes($query)
    {
        return $query->where('type', 'Dispute');
    }
}
