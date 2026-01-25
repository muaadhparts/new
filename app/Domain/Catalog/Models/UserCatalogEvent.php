<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * User Catalog Event Model
 *
 * Tracks catalog events specific to a user (purchase notifications, etc.)
 */
class UserCatalogEvent extends Model
{
    protected $table = 'user_catalog_events';

    protected $fillable = [
        'user_id',
        'purchase_number',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public static function countPurchase(int $userId): int
    {
        return static::where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
    }
}
