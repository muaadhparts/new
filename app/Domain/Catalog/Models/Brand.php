<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

/**
 * Brand Model - Vehicle Brands (Toyota, Nissan, etc.)
 *
 * Domain: Catalog
 * Table: brands
 *
 * @property int $id
 * @property string $name
 * @property string|null $name_ar
 * @property string|null $slug
 * @property int $status
 * @property int|null $is_featured
 * @property string|null $photo
 */
class Brand extends Model
{
    protected $table = 'brands';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'name_ar',
        'slug',
        'status',
        'is_featured',
        'photo',
    ];

    protected $casts = [
        'status' => 'integer',
        'is_featured' => 'boolean',
    ];

    /* =========================================================================
     |  RELATIONSHIPS
     | ========================================================================= */

    /**
     * All catalogs belonging to this brand.
     */
    public function catalogs(): HasMany
    {
        return $this->hasMany(Catalog::class, 'brand_id');
    }

    /**
     * Active catalogs belonging to this brand.
     */
    public function activeCatalogs(): HasMany
    {
        return $this->catalogs()->where('status', 1);
    }

    /**
     * Brand regions for this brand.
     */
    public function regions(): HasMany
    {
        return $this->hasMany(BrandRegion::class, 'brand_id');
    }

    /**
     * NewCategories belonging to this brand.
     */
    public function newCategories(): HasMany
    {
        return $this->hasMany(NewCategory::class, 'brand_id');
    }

    /**
     * Vehicle fitments for this brand.
     */
    public function fitments(): HasMany
    {
        return $this->hasMany(CatalogItemFitment::class, 'brand_id');
    }

    /**
     * Catalog items that fit this brand's vehicles (via fitments).
     */
    public function catalogItems(): BelongsToMany
    {
        return $this->belongsToMany(
            CatalogItem::class,
            'catalog_item_fitments',
            'brand_id',
            'catalog_item_id'
        );
    }

    /* =========================================================================
     |  SCOPES
     | ========================================================================= */

    /**
     * Scope: Only active brands.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /**
     * Scope: Only featured brands.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', 1);
    }

    /**
     * Scope: Include count of active catalogs.
     */
    public function scopeWithCatalogsCount(Builder $query): Builder
    {
        return $query->withCount(['catalogs' => fn($q) => $q->where('status', 1)]);
    }

    /**
     * Scope: Include count of catalog items.
     */
    public function scopeWithItemsCount(Builder $query): Builder
    {
        return $query->withCount('catalogItems');
    }

    /* =========================================================================
     |  ACCESSORS
     | ========================================================================= */

    /**
     * Get localized brand name based on current locale.
     */
    public function getLocalizedNameAttribute(): string
    {
        $isAr = app()->getLocale() === 'ar';
        $nameAr = trim((string)($this->name_ar ?? ''));
        $name = trim((string)($this->name ?? ''));

        if ($isAr) {
            return $nameAr !== '' ? $nameAr : $name;
        }
        return $name !== '' ? $name : $nameAr;
    }

    /**
     * Get brand photo URL.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (empty($this->photo)) {
            return null;
        }

        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            return $this->photo;
        }

        $legacyPath = public_path('assets/images/brand/' . $this->photo);
        if (file_exists($legacyPath)) {
            return asset('assets/images/brand/' . $this->photo);
        }

        if (Storage::disk('public')->exists('brands/' . $this->photo)) {
            return Storage::url('brands/' . $this->photo);
        }

        return asset('assets/images/brand/' . $this->photo);
    }

    /**
     * Get active sub-catalogs (legacy compatibility).
     */
    public function getSubsAttribute()
    {
        if ($this->relationLoaded('catalogs')) {
            return $this->catalogs->where('status', 1)->values();
        }
        return $this->catalogs()->where('status', 1)->get();
    }
}
