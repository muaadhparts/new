<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 * @property int $featured
 * @property string|null $link
 * @property string|null $photo
 */
class Brand extends Model
{
    protected $fillable = ['name', 'name_ar', 'slug', 'status', 'featured', 'link', 'photo'];

    public $timestamps = false;

    protected $appends = ['localized_name', 'photo_url'];

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * Catalogs belonging to this brand
     */
    public function catalogs(): HasMany
    {
        return $this->hasMany(Catalog::class, 'brand_id', 'id');
    }

    /**
     * NewCategories belonging to this brand
     */
    public function newCategories(): HasMany
    {
        return $this->hasMany(NewCategory::class, 'brand_id');
    }

    /**
     * Brand regions
     */
    public function regions(): HasMany
    {
        return $this->hasMany(BrandRegion::class, 'brand_id');
    }

    /**
     * Vehicle fitments - which catalog items fit this brand's vehicles
     */
    public function fitments(): HasMany
    {
        return $this->hasMany(CatalogItemFitment::class, 'brand_id');
    }

    /**
     * Get catalog items that fit this brand's vehicles (via fitments)
     */
    public function catalogItems()
    {
        return $this->belongsToMany(CatalogItem::class, 'catalog_item_fitments', 'brand_id', 'catalog_item_id');
    }

    // =========================================================
    // ACCESSORS
    // =========================================================

    /**
     * Alias: subs -> catalogs (compatibility with Category model)
     */
    public function getSubsAttribute()
    {
        if ($this->relationLoaded('catalogs')) {
            return $this->catalogs->where('status', 1)->values();
        }
        return $this->catalogs()->where('status', 1)->get();
    }

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

        // Check if it's already a full URL
        if (filter_var($this->photo, FILTER_VALIDATE_URL)) {
            return $this->photo;
        }

        // Check in assets/images/brand/ (legacy location)
        $legacyPath = public_path('assets/images/brand/' . $this->photo);
        if (file_exists($legacyPath)) {
            return asset('assets/images/brand/' . $this->photo);
        }

        // Check in storage
        if (Storage::disk('public')->exists('brands/' . $this->photo)) {
            return Storage::url('brands/' . $this->photo);
        }

        // Fallback to legacy path
        return asset('assets/images/brand/' . $this->photo);
    }
}
