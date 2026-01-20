<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MerchantPhoto - Photos uploaded by merchants for their items
 *
 * Actual database columns:
 * - id
 * - merchant_item_id (FK to merchant_items.id)
 * - photo
 * - sort_order
 * - is_primary
 * - status
 * - created_at
 * - updated_at
 *
 * Relationship flow:
 * MerchantPhoto → MerchantItem → User (merchant)
 * MerchantPhoto → MerchantItem → CatalogItem
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
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * The merchant item this photo belongs to
     */
    public function merchantItem(): BelongsTo
    {
        return $this->belongsTo(MerchantItem::class, 'merchant_item_id');
    }

    /**
     * Get the merchant/user that owns this photo (via merchant item)
     */
    public function user(): BelongsTo
    {
        return $this->merchantItem?->user();
    }

    /**
     * Get the catalog item (via merchant item)
     */
    public function catalogItem()
    {
        return $this->merchantItem?->catalogItem();
    }

    /**
     * Scope: Filter by merchant user ID (through merchant_item)
     */
    public function scopeForMerchant($query, $userId)
    {
        return $query->whereHas('merchantItem', fn($q) => $q->where('user_id', $userId));
    }

    /**
     * Scope: Filter by catalog item ID (through merchant_item)
     */
    public function scopeForCatalogItem($query, $catalogItemId)
    {
        return $query->whereHas('merchantItem', fn($q) => $q->where('catalog_item_id', $catalogItemId));
    }

    /**
     * Scope: Filter by merchant item ID
     */
    public function scopeForMerchantItem($query, $merchantItemId)
    {
        return $query->where('merchant_item_id', $merchantItemId);
    }

    /**
     * Scope: Primary photos only
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope: Active photos only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope: Order by sort order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get photo URL
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (empty($this->photo)) {
            return null;
        }

        // Check if it's already a full URL
        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            return $this->photo;
        }

        // Return from assets folder
        return asset('assets/images/merchant-photos/' . $this->photo);
    }
}
