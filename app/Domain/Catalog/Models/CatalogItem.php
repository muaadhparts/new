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
