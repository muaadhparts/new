<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\MerchantItem;

class QualityBrand extends Model
{
    // Correct table
    protected $table = 'brand_qualities';

    protected $fillable = [
        'code','name_en','name_ar','logo','country','website','description','is_active',
    ];

    // dd('QualityBrand loaded'); // Quick check if needed (keep it suspended)

    // Activation scope - you used ->active() in templates
    public function scopeActive($q)
    {
        return $q->where('is_active', 1);
    }

    /**
     * Merchant items for this quality brand
     */
    public function merchantItems()
    {
        return $this->hasMany(MerchantItem::class, 'brand_quality_id');
    }


    /**
     * Get localized quality brand name based on current locale.
     * Arabic: name_ar (fallback to name_en)
     * English: name_en (fallback to name_ar)
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

    // Displayable name (unifies language selection) - legacy alias
    public function getDisplayNameAttribute()
    {
        return $this->localized_name;
    }

    // Logo URL from Storage
    public function getLogoUrlAttribute()
    {
        return $this->logo ? \Storage::url($this->logo) : null;
    }
}