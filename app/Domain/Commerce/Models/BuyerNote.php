<?php

namespace App\Domain\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Domain\Identity\Models\User;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Commerce\Models\NoteResponse;

/**
 * BuyerNote Model - Buyer notes/questions on items
 *
 * Domain: Commerce
 * Table: buyer_notes
 *
 * @property int $id
 * @property int $catalog_item_id
 * @property int|null $merchant_item_id
 * @property int $user_id
 * @property string|null $text
 */
class BuyerNote extends Model
{
    protected $table = 'buyer_notes';

    protected $fillable = ['catalog_item_id', 'merchant_item_id', 'user_id', 'text'];

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

    public function noteResponses(): HasMany
    {
        return $this->hasMany(NoteResponse::class);
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

    public function scopeForMerchantItem($query, int $merchantItemId)
    {
        return $query->where('merchant_item_id', $merchantItemId);
    }
}
