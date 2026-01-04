<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyerNote extends Model
{
    protected $table = 'buyer_notes';

    protected $fillable = ['catalog_item_id', 'merchant_item_id', 'user_id', 'text'];

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withDefault();
    }

    public function catalogItem()
    {
        return $this->belongsTo('App\Models\CatalogItem', 'catalog_item_id')->withDefault();
    }

    public function merchantItem()
    {
        return $this->belongsTo('App\Models\MerchantItem', 'merchant_item_id')->withDefault();
    }

    public function noteResponses()
    {
        return $this->hasMany('App\Models\NoteResponse');
    }
}
