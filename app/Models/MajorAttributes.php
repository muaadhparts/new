<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MajorAttributes extends Model
{
    // Security: Define fillable fields instead of empty guarded
    protected $fillable = [
        'catalog_id',
        'name',
        'items',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class);
    }
}
