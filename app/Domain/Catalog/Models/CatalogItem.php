<?php

namespace App\Domain\Catalog\Models;

use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Models\MerchantPhoto;
use App\Domain\Commerce\Models\FavoriteSeller;
use App\Domain\Commerce\Models\BuyerNote;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CatalogItem Model - Main product catalog
 *
 * Domain: Catalog
 * Table: catalog_items
 *
 * CatalogItem stores only catalogue-level attributes.
 * Merchant-specific data (price, stock, policy, status, etc.) is in MerchantItem.
 *
 * @property int $id
 * @property string $part_number
 * @property string|null $label_en
 * @property string|null $label_ar
 * @property string|null $attributes
 * @property string|null $name
 * @property string|null $slug
 * @property string|null $photo
 * @property string|null $thumbnail
 * @property float|null $weight
 * @property int $views
 */
class CatalogItem extends Model
{
    use HasFactory;

    protected $table = 'catalog_items';

    protected $fillable = [
        'part_number',
        'label_en',
        'label_ar',
        'attributes',
        'name',
        'slug',
        'photo',
        'thumbnail',
        'weight',
        'views',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'views' => 'integer',
        'attributes' => 'array',
    ];

    /* =========================================================================
     |  ACCESSORS (Display only - no business logic)
     | ========================================================================= */

    /**
     * Get localized name based on current locale.
     */
    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        
        if ($locale === 'ar') {
            return $this->label_ar ?: $this->label_en ?: $this->name;
        }
        
        return $this->label_en ?: $this->name;
    }

    /**
     * Get full photo URL.
     */
    public function getPhotoUrlAttribute(): string
    {
        if (empty($this->photo)) {
            return asset('assets/images/noimage.png');
        }
        
        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            return $this->photo;
        }
        
        return \Illuminate\Support\Facades\Storage::disk('s3')->url($this->photo);
    }

    /**
     * Get thumbnail URL.
     */
    public function getThumbnailUrlAttribute(): string
    {
        // For now, same as photo_url
        return $this->photo_url;
    }

    /* =========================================================================
     |  PRICE CONVERSION HELPERS - DEPRECATED
     | =========================================================================
     | These methods are deprecated. Use PriceFormatterService instead.
     | @deprecated Use \App\Domain\Commerce\Services\PriceFormatterService
     | @see \App\Domain\Commerce\Services\PriceFormatterService
     */

    /**
     * Filter products collection for API responses.
     * Processes nested arrays/collections and returns flattened result.
     */
    public static function filterProducts($products)
    {
        if (empty($products)) {
            return [];
        }

        // If it's already a collection, return as array
        if ($products instanceof \Illuminate\Support\Collection) {
            return $products->toArray();
        }

        // If it's an array, return as is
        if (is_array($products)) {
            return $products;
        }

        // Otherwise, wrap in array
        return [$products];
    }

    /* =========================================================================
     |  RELATIONSHIPS
     | ========================================================================= */

    /**
     * Vehicle fitments - which brands/vehicles this part fits.
     */
    public function fitments(): HasMany
    {
        return $this->hasMany(CatalogItemFitment::class, 'catalog_item_id');
    }

    /**
     * Merchant listings for this catalog item (each row is one seller).
     */
    public function merchantItems(): HasMany
    {
        return $this->hasMany(MerchantItem::class, 'catalog_item_id');
    }

    /**
     * Active merchant listings only (status=1, approved merchant).
     */
    public function activeMerchantItems(): HasMany
    {
        return $this->hasMany(MerchantItem::class, 'catalog_item_id')
            ->where('status', 1)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2));
    }

    /**
     * Get all brands this catalog item fits (via fitments).
     */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'catalog_item_fitments', 'catalog_item_id', 'brand_id');
    }

    /**
     * Get all catalogs this catalog item fits (via fitments).
     */
    public function catalogs(): BelongsToMany
    {
        return $this->belongsToMany(Catalog::class, 'catalog_item_fitments', 'catalog_item_id', 'catalog_id');
    }

    /**
     * Reviews for this catalog item.
     */
    public function catalogReviews(): HasMany
    {
        return $this->hasMany(CatalogReview::class, 'catalog_item_id');
    }

    /**
     * Get all merchant photos for this catalog item (via merchant_items).
     */
    public function merchantPhotos(): HasManyThrough
    {
        return $this->hasManyThrough(
            MerchantPhoto::class,
            MerchantItem::class,
            'catalog_item_id',
            'merchant_item_id',
            'id',
            'id'
        )->where('merchant_photos.status', 1)
         ->orderBy('merchant_photos.sort_order');
    }

    /**
     * Get merchant photos for a specific merchant with limit.
     */
    public function merchantPhotosForMerchant(int $merchantUserId, int $limit = 4)
    {
        return $this->hasManyThrough(
            MerchantPhoto::class,
            MerchantItem::class,
            'catalog_item_id',
            'merchant_item_id',
            'id',
            'id'
        )->where('merchant_photos.status', 1)
         ->where('merchant_items.user_id', $merchantUserId)
         ->orderBy('merchant_photos.sort_order')
         ->limit($limit);
    }

    /**
     * Favorites relation.
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(FavoriteSeller::class, 'catalog_item_id');
    }

    /**
     * Single favorite (legacy).
     */
    public function favorite(): BelongsTo
    {
        return $this->belongsTo(FavoriteSeller::class)->withDefault();
    }

    /**
     * Buyer notes for this catalog item.
     */
    public function buyerNotes(): HasMany
    {
        return $this->hasMany(BuyerNote::class, 'catalog_item_id');
    }

    /**
     * Abuse flags for this catalog item.
     */
    public function abuseFlags(): HasMany
    {
        return $this->hasMany(AbuseFlag::class, 'catalog_item_id');
    }
}
