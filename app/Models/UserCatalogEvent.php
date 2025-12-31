<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCatalogEvent extends Model
{
    protected $table = 'user_catalog_events';

    protected $fillable = ['user_id', 'purchase_number', 'is_read'];

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withDefault();
    }

    public static function countPurchase($id)
    {
        return UserCatalogEvent::where('user_id', '=', $id)->where('is_read', '=', 0)->get()->count();
    }
}
