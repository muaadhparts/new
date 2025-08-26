<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogModel extends Model
{
    protected $fillable = [

        'catlog_id',
        'brand_id',
        'name',
        'public_id',
        'vin',
        'model_code',
        'beginDate',
        'endDate',
        'year',
        'majorAttributes',
        'vehicleDescription',
        'meta',
    ];

    protected $casts = [
        'majorAttributes' => 'array',
        'vehicleDescription' => 'array',
        'meta' => 'array',
    ];


    public function catalog(): BelongsTo
    {
        return $this->belongsTo(Catalog::class,'catlog_id');
    }


    public function levels()
    {
        return $this->belongsToMany(Level::class, 'catalog_model_level')
            ->withPivot('catalog_model_id', 'level_id')
            ->withTimestamps();
    }
    public function level()
    {
        return $this->belongsToMany(Level::class, 'catalog_model_level','level_id')
            ->withPivot('catalog_model_id', 'level_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}
