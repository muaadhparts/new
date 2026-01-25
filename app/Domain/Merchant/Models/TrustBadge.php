<?php

namespace App\Domain\Merchant\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trust Badge Model
 *
 * Merchant verification and trust badges.
 */
class TrustBadge extends Model
{
    protected $table = 'trust_badges';

    protected $fillable = [
        'user_id',
        'attachments',
        'text',
        'admin_warning',
        'warning_reason',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }
}
