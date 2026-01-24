<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * BrandRegion Model - Regional data for brands
 *
 * Domain: Catalog
 * Table: brand_regions
 *
 * @property int $id
 * @property int $brand_id
 * @property string $code
 * @property string $label
 */
class BrandRegion extends Model
{
    protected $fillable = ['brand_id', 'code', 'label'];

    /**
     * The brand this region belongs to
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Catalogs in this region
     */
    public function catalogs(): HasMany
    {
        return $this->hasMany(Catalog::class, 'brand_region_id');
    }
}
