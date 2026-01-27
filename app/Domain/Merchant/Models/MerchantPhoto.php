<?php

namespace App\Domain\Merchant\Models;

use App\Domain\Catalog\Models\CatalogItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * MerchantPhoto Model - Photos uploaded by merchants for their items
 *
 * Domain: Merchant
 * Table: merchant_photos
 *
 * @property int $id
 * @property int $merchant_item_id
 * @property string|null $photo
 * @property int $sort_order
 * @property bool $is_primary
 * @property bool $status
 */
class MerchantPhoto extends Model
{
    protected $table = 'merchant_photos';

    protected $fillable = [
        'merchant_item_id',
        'photo',
        'sort_order',
        'is_primary',
        'status',
    ];

    protected $casts = [
        'merchant_item_id' => 'integer',
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * Accessors to include in array/JSON serialization
     */
    protected $appends = [
        'photo_url',
    ];

    /* =========================================================================
     |  RELATIONSHIPS
     | ========================================================================= */

    /**
     * The merchant item this photo belongs to.
     */
    public function merchantItem(): BelongsTo
    {
        return $this->belongsTo(MerchantItem::class, 'merchant_item_id');
    }

    /**
     * Get the catalog item through the merchant item.
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id')
            ->via('merchantItem');
    }

    /* =========================================================================
     |  SCOPES
     | ========================================================================= */

    /**
     * Scope: Only active photos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /**
     * Scope: Only primary photos.
     */
    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope: Order by sort_order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Scope: Filter by merchant item ID.
     */
    public function scopeForMerchantItem(Builder $query, int $merchantItemId): Builder
    {
        return $query->where('merchant_item_id', $merchantItemId);
    }

    /**
     * Scope: Filter by merchant user ID.
     */
    public function scopeForMerchant(Builder $query, int $merchantId): Builder
    {
        return $query->whereHas('merchantItem', fn($q) => $q->where('user_id', $merchantId));
    }

    /**
     * Scope: Filter by catalog item ID.
     */
    public function scopeForCatalogItem(Builder $query, int $catalogItemId): Builder
    {
        return $query->whereHas('merchantItem', fn($q) => $q->where('catalog_item_id', $catalogItemId));
    }

    /* =========================================================================
     |  ACCESSORS
     | ========================================================================= */

    /**
     * Get the photo URL.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (empty($this->photo)) {
            return null;
        }

        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            return $this->photo;
        }

        if (str_contains($this->photo, '/')) {
            return Storage::disk('do')->url($this->photo);
        }

        return asset('assets/images/merchant-photos/' . $this->photo);
    }

    /**
     * Get the thumbnail URL (alias for photo_url).
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->photo_url;
    }
}
