<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbuseFlag extends Model
{
    protected $table = 'abuse_flags';

    protected $fillable = ['catalog_item_id', 'merchant_item_id', 'user_id', 'title', 'note'];

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withDefault(function ($data) {
            foreach ($data->getFillable() as $dt) {
                $data[$dt] = __('Deleted');
            }
        });
    }

    public function catalogItem()
    {
        return $this->belongsTo('App\Models\CatalogItem', 'catalog_item_id')->withDefault(function ($data) {
            foreach ($data->getFillable() as $dt) {
                $data[$dt] = __('Deleted');
            }
        });
    }

    public function merchantItem()
    {
        return $this->belongsTo('App\Models\MerchantItem', 'merchant_item_id')->withDefault();
    }
}
