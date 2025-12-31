<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantPurchase extends Model
{
    protected $table = 'merchant_purchases';

    public $timestamps = false;

    protected $fillable = [
        'purchase_id',
        'user_id',
        'qty',
        'price',
        'purchase_number',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withDefault();
    }

    public function purchase()
    {
        return $this->belongsTo('App\Models\Purchase', 'purchase_id')->withDefault();
    }
}
