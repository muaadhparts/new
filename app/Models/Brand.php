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

}