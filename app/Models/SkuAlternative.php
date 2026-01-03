<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkuAlternative extends Model
{
    protected $table = 'sku_alternatives';
    protected $fillable = ['part_number', 'group_id'];

    public function items(): HasMany
    {
        return $this->hasMany(SkuAlternativeItem::class, 'a_id');
    }
}
