<?php

namespace App\Domain\Merchant\Models;

use Illuminate\Database\Eloquent\Model;
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
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
        'status' => 'boolean',
    ];

    // =========================================================
    // RELATIONS
    // =========================================================

    public function merchantItem(): BelongsTo
    {
        return $this->belongsTo(MerchantItem::class, 'merchant_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->merchantItem?->user();
    }

    public function catalogItem()
    {
        return $this->merchantItem?->catalogItem();
    }

    // =========================================================
    // SCOPES
    // =========================================================

    public function scopeForMerchant($query, $userId)
    {
        return $query->whereHas('merchantItem', fn($q) => $q->where('user_id', $userId));
    }

    public function scopeForCatalogItem($query, $catalogItemId)
    {
        return $query->whereHas('merchantItem', fn($q) => $q->where('catalog_item_id', $catalogItemId));
    }

    public function scopeForMerchantItem($query, $merchantItemId)
    {
        return $query->where('merchant_item_id', $merchantItemId);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // =========================================================
    // ACCESSORS
    // =========================================================

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
}
