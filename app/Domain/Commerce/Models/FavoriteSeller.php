<?php

namespace App\Domain\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\CatalogItem;
use App\Models\MerchantItem;

/**
 * FavoriteSeller Model - User's favorite sellers/items
 *
 * Domain: Commerce
 * Table: favorite_sellers
 *
 * @property int $id
 * @property int $user_id
 * @property int $catalog_item_id
 * @property int|null $merchant_item_id
 */
class FavoriteSeller extends Model
{
    protected $table = 'favorite_sellers';

    public $timestamps = false;

    protected $fillable = ['user_id', 'catalog_item_id', 'merchant_item_id'];

    // =========================================================================
    // RELATIONS
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id')->withDefault();
    }

    public function merchantItem(): BelongsTo
    {
        return $this->belongsTo(MerchantItem::class, 'merchant_item_id')->withDefault();
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get the effective merchant item for this favorite item
     * If merchant_item_id is set, use it. Otherwise, find first active merchant item
     */
    public function getEffectiveMerchantItem()
    {
        if ($this->merchant_item_id) {
            return $this->merchantItem;
        }

        if ($this->relationLoaded('catalogItem') && $this->catalogItem && $this->catalogItem->relationLoaded('merchantItems')) {
            return $this->catalogItem->merchantItems
                ->filter(fn($mi) => $mi->status == 1)
                ->sortBy('price')
                ->first();
        }

        return MerchantItem::where('catalog_item_id', $this->catalog_item_id)
            ->where('status', 1)
            ->orderBy('price')
            ->first();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForCatalogItem($query, int $catalogItemId)
    {
        return $query->where('catalog_item_id', $catalogItemId);
    }
}
