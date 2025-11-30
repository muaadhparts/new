<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $fillable = ['name', 'link', 'photo'];

    public $timestamps = false;


    public function catalogs(): HasMany
    {
//        dd($this);
        return $this->hasMany(Catalog::class ,'brand_id','id');
    }



    public function regions()
    {
        return $this->hasMany(BrandRegion::class, 'brand_id');
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

}