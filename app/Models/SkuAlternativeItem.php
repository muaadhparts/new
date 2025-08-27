<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkuAlternativeItem extends Model
{
    protected $table = 'sku_alternative_items';
    protected $fillable = ['a_id', 'sku'];

    // عطّلها إذا الجدول ما فيه created_at/updated_at
    public $timestamps = false;

    public function group(): BelongsTo
    {
        // dd('SkuAlternativeItem@group', $this->a_id); // لفحص سريع عند الحاجة
        return $this->belongsTo(SkuAlternative::class, 'a_id');
    }

    public function product(): BelongsTo
    {
        // dd('SkuAlternativeItem@product', $this->sku); // لفحص سريع عند الحاجة
        return $this->belongsTo(Product::class, 'sku', 'sku');
    }
}
