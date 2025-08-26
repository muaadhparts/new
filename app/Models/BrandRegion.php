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
}
