<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use App\Domain\Merchant\Models\MerchantItem;

/**
 * QualityBrand Model - Aftermarket parts brands (Bosch, Denso, etc.)
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
        'code',
        'name_en',
        'name_ar',
        'logo',
        'country',
        'website',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* =========================================================================
     |  RELATIONSHIPS
     | ========================================================================= */

    /**
     * All merchant items for this quality brand.
     */
    public function merchantItems(): HasMany
    {
        return $this->hasMany(MerchantItem::class, 'quality_brand_id');
    }

    /**
     * Active merchant items for this quality brand.
     */
    public function activeMerchantItems(): HasMany
    {
        return $this->merchantItems()->where('status', 1);
    }

    /* =========================================================================
     |  SCOPES
     | ========================================================================= */

    /**
     * Scope: Only active quality brands.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: Filter by code.
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    /**
     * Scope: Include count of active merchant items.
     */
    public function scopeWithItemsCount(Builder $query): Builder
    {
        return $query->withCount(['merchantItems' => fn($q) => $q->where('status', 1)]);
    }

    /* =========================================================================
     |  ACCESSORS
     | ========================================================================= */

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
     * Get display name (alias for localized_name).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->localized_name;
    }

    /**
     * Get logo URL from Storage.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo)) {
            return null;
        }

        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        return Storage::disk('do')->url($this->logo);
    }
}
