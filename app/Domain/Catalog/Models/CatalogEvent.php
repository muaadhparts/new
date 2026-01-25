<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Commerce\Models\Purchase;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CatalogEvent Model
 *
 * Tracks various events in the catalog system (registrations, purchases, etc.)
 */
class CatalogEvent extends Model
{
    protected $table = 'catalog_events';

    protected $fillable = [
        'purchase_id',
        'user_id',
        'merchant_id',
        'catalog_item_id',
        'chat_thread_id',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    // ========================================
    // Relationships
    // ========================================

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id')->withDefault();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id')->withDefault();
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id')->withDefault();
    }

    // ========================================
    // Static Counters
    // ========================================

    public static function countRegistration(): int
    {
        return static::whereNotNull('user_id')
            ->where('is_read', 0)
            ->count();
    }

    public static function countPurchase(): int
    {
        return static::whereNotNull('purchase_id')
            ->where('is_read', 0)
            ->count();
    }

    public static function countCatalogItem(): int
    {
        return static::whereNotNull('catalog_item_id')
            ->where('is_read', 0)
            ->count();
    }

    public static function countChatThread(): int
    {
        return static::whereNotNull('chat_thread_id')
            ->where('is_read', 0)
            ->count();
    }
}
