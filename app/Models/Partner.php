<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partner extends Model
{
    protected $fillable = ['link','photo'];

    public $timestamps = false;


    public function catalogs(): HasMany
    {
//        dd($this);
        return $this->hasMany(Catalog::class ,'brand_id','id');
    }

}