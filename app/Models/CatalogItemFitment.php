<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CatalogItemFitment - Links catalog items to vehicle brands/catalogs
 *
 * This table represents which vehicles a part fits.
 * One catalog_item can have multiple fitments (fits Toyota AND Lexus)
 *
 * @property int $id
 * @property int|null $catalog_item_id
 * @property int $catalog_id
 * @property int $brand_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class CatalogItemFitment extends Model
{
    protected $table = 'catalog_item_fitments';

    protected $fillable = [
        'catalog_item_id',
        'catalog_id',
        'brand_id',
    ];

    /**
     * The catalog item this fitment belongs to
     */
    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'catalog_item_id');
    }

    /**
     * The vehicle brand (Toyota, Nissan, etc.)
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * The catalog this fitment belongs to
     */
    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class, 'catalog_id');
    }

    /**
     * Get localized brand name
     */
    public function getBrandNameAttribute(): ?string
    {
        return $this->brand?->localized_name;
    }

    /**
     * Get brand logo URL
     */
    public function getBrandLogoAttribute(): ?string
    {
        return $this->brand?->photo_url;
    }
}
