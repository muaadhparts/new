<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VinDecodedCache extends Model
{
    use HasFactory;

    protected $table = 'vin_decoded_cache';

    protected $fillable = [
        'vin',
        'brand_id',
        'brand_region_id',
        'catalog_id',
        'catalogCode',
        'modelCode',
        'buildDate',
        'modelBeginDate',
        'modelEndDate',
        'shortName',
        'catalogType',
        'dataRegion',
        'catMarket',
        'nmc_vehicleType',
        'vin_model_id',
        'raw_json',
    ];

    protected $casts = [
        'buildDate'       => 'date',
        'modelBeginDate'  => 'date',
        'modelEndDate'    => 'date',
        'raw_json'        => 'array',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function brandRegion()
    {
        return $this->belongsTo(BrandRegion::class, 'brand_region_id');
    }

    public function catalog()
    {
        return $this->belongsTo(Catalog::class);
    }

    public function model()
    {
        return $this->belongsTo(VinModel::class, 'vin_model_id');
    }

    public function specifications()
    {
        return $this->hasMany(VinSpecMapped::class, 'vin_id');
    }
}
