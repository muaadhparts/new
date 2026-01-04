<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogEvent extends Model
{
    protected $table = 'catalog_events';

    protected $fillable = ['purchase_id', 'user_id', 'merchant_id', 'catalog_item_id', 'chat_thread_id'];

    public function purchase()
    {
        return $this->belongsTo('App\Models\Purchase', 'purchase_id')->withDefault();
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withDefault();
    }

    public function merchant()
    {
        return $this->belongsTo('App\Models\User', 'merchant_id')->withDefault();
    }

    public function catalogItem()
    {
        return $this->belongsTo('App\Models\CatalogItem', 'catalog_item_id')->withDefault();
    }

    public function chatThread()
    {
        return $this->belongsTo('App\Models\ChatThread', 'chat_thread_id')->withDefault();
    }

    public static function countRegistration()
    {
        return CatalogEvent::where('user_id', '!=', null)->where('is_read', '=', 0)->latest('id')->get()->count();
    }

    public static function countPurchase()
    {
        return CatalogEvent::where('purchase_id', '!=', null)->where('is_read', '=', 0)->latest('id')->get()->count();
    }

    public static function countCatalogItem()
    {
        return CatalogEvent::where('catalog_item_id', '!=', null)->where('is_read', '=', 0)->latest('id')->get()->count();
    }

    public static function countChatThread()
    {
        return CatalogEvent::where('chat_thread_id', '!=', null)->where('is_read', '=', 0)->latest('id')->get()->count();
    }
}
