<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandRegion extends Model
{
    protected $fillable = ['brand_id', 'code', 'label'];

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'brand_id');
    }
}
