<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Brand extends Model
{
    protected $fillable = ['name', 'name_ar', 'slug', 'status', 'featured', 'link', 'photo'];

    public $timestamps = false;

    /**
     * Appended attributes
     */
    protected $appends = ['localized_name', 'photo_url'];

    // =========================================================
    // COMPATIBILITY - للتوافق مع Category model القديم
    // =========================================================

    /**
     * Alias: subs → catalogs (للتوافق مع $category->subs)
     */
    public function getSubsAttribute()
    {
        return $this->catalogs()->where('status', 1)->get();
    }

    /**
     * الكتالوجات المرتبطة بالبراند
     */
    public function catalogs(): HasMany
    {
        return $this->hasMany(Catalog::class, 'brand_id', 'id');
    }

    /**
     * TreeCategories المرتبطة بالبراند
     */
    public function treeCategories(): HasMany
    {
        return $this->hasMany(TreeCategory::class, 'brand_id');
    }



    public function regions()
    {
        return $this->hasMany(BrandRegion::class, 'brand_id');
    }

    /**
     * Get catalog items for this brand.
     * @deprecated Use catalogItems() instead
     */
    public function products(): HasMany
    {
        return $this->hasMany(CatalogItem::class, 'brand_id');
    }

    /**
     * Get catalog items for this brand.
     */
    public function catalogItems(): HasMany
    {
        return $this->hasMany(CatalogItem::class, 'brand_id');
    }


    /**
     * Get localized brand name based on current locale.
     * Arabic: name_ar (fallback to name)
     * English: name (fallback to name_ar)
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
     * Checks multiple possible storage locations.
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

        // Fallback to legacy path even if not exists
        return asset('assets/images/brand/' . $this->photo);
    }

}