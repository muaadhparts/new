<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkuAlternativeItem extends Model
{
    protected $table = 'sku_alternatives';
    protected $fillable = ['sku', 'group_id'];

    public function products()
    {
        return $this->hasMany(Product::class, 'sku', 'sku');
    }
}
