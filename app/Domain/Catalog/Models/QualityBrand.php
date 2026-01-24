<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use App\Models\MerchantItem;

/**
 * QualityBrand Model - Aftermarket parts brands
 *
 * Domain: Catalog
 * Table: quality_brands
 *
 * @property int $id
 * @property string|null $code
 * @property string|null $name_en
 * @property string|null $name_ar
 * @property string|null $logo
 * @property string|null $country
 * @property string|null $website
 * @property string|null $description
 * @property int $is_active
 */
class QualityBrand extends Model
{
    use HasFactory;

    protected $table = 'quality_brands';

    protected $fillable = [
        'code', 'name_en', 'name_ar', 'logo', 'country', 'website', 'description', 'is_active',
    ];

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * Merchant items for this quality brand
     */
    public function merchantItems(): HasMany
    {
        return $this->hasMany(MerchantItem::class, 'quality_brand_id');
    }

    // =========================================================
    // SCOPES
    // =========================================================

    /**
     * Filter active brands only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    // =========================================================
    // ACCESSORS
    // =========================================================

    /**
     * Get localized quality brand name based on current locale.
     */
    public function getLocalizedNameAttribute(): string
    {
        $isAr = app()->getLocale() === 'ar';
        $nameAr = trim((string)($this->name_ar ?? ''));
        $nameEn = trim((string)($this->name_en ?? ''));

        if ($isAr) {
            return $nameAr !== '' ? $nameAr : $nameEn;
        }
        return $nameEn !== '' ? $nameEn : $nameAr;
    }

    /**
     * Displayable name (unifies language selection) - legacy alias
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->localized_name;
    }

    /**
     * Logo URL from Storage
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::url($this->logo) : null;
    }
}
