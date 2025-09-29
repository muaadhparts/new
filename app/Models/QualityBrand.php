<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function merchantProducts()
    {
        return $this->hasMany(MerchantProduct::class, 'brand_quality_id');
    }

    // Displayable name (unifies language selection)
    public function getDisplayNameAttribute()
    {
        return $this->name_ar ?: $this->name_en;
    }

    // Logo URL from Storage
    public function getLogoUrlAttribute()
    {
        return $this->logo ? \Storage::url($this->logo) : null;
    }
}