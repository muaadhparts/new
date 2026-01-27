<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CatalogItemFitment - Links catalog items to vehicle brands/catalogs
 *
 * Domain: Catalog
 * Table: catalog_item_fitments
 *
 * This table represents which vehicles a part fits.
 * One catalog_item can have multiple fitments (fits Toyota AND Lexus)
 *
 * @property int $id
 * @property int|null $catalog_item_id
 * @property int $catalog_id
 * @property int $brand_id
 */
class CatalogItemFitment extends Model
{
    protected $table = 'catalog_item_fitments';

    protected $fillable = [
        'catalog_item_id',
        'catalog_id',
        'brand_id',
    ];

    protected $casts = [
        'catalog_item_id' => 'integer',
        'catalog_id' => 'integer',
        'brand_id' => 'integer',
    ];

    /* =========================================================================
     |  RELATIONSHIPS
     | ========================================================================= */

    /**
     * The catalog item this fitment belongs to.
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    /**
     * The vehicle brand (Toyota, Nissan, etc.).
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * The catalog this fitment belongs to.
     */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    /* =========================================================================
     |  SCOPES
     | ========================================================================= */

    /**
     * Scope: Filter by catalog item ID.
     */
    public function scopeForCatalogItem(Builder $query, int $catalogItemId): Builder
    {
        return $query->where('catalog_item_id', $catalogItemId);
    }

    /**
     * Scope: Filter by brand ID.
     */
    public function scopeForBrand(Builder $query, int $brandId): Builder
    {
        return $query->where('brand_id', $brandId);
    }

    /**
     * Scope: Filter by catalog ID.
     */
    public function scopeForCatalog(Builder $query, int $catalogId): Builder
    {
        return $query->where('catalog_id', $catalogId);
    }

    /* =========================================================================
     |  ACCESSORS
     | ========================================================================= */

    /**
     * Get localized brand name.
     */
    public function getBrandNameAttribute(): string
    {
        if ($this->relationLoaded('brand')) {
            return $this->brand?->localized_name ?? '';
        }
        return '';
    }

    /**
     * Get brand logo URL.
     */
    public function getBrandLogoAttribute(): ?string
    {
        if ($this->relationLoaded('brand')) {
            return $this->brand?->photo_url;
        }
        return null;
    }

    /**
     * Get localized catalog name.
     */
    public function getCatalogNameAttribute(): string
    {
        if ($this->relationLoaded('catalog')) {
            return $this->catalog?->localized_name ?? '';
        }
        return '';
    }

    /**
     * Get catalog year range.
     */
    public function getYearRangeAttribute(): string
    {
        if ($this->relationLoaded('catalog') && $this->catalog) {
            return $this->catalog->year_range;
        }
        return '';
    }
}
