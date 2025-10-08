<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandRegion extends Model
{
    protected $fillable = ['brand_id', 'code', 'label'];

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * 🔗 الكتالوجات المرتبطة بهذه المنطقة
     */
    public function catalogs()
    {
        return $this->hasMany(Catalog::class, 'brand_region_id');
    }
}
