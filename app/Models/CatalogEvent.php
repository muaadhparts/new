<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatalogEvent extends Model
{
    protected $table = 'catalog_events';

    protected $fillable = ['purchase_id', 'user_id', 'merchant_id', 'catalog_item_id', 'conversation_id'];

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

    /**
     * @deprecated Use merchant() instead
     */
    public function vendor()
    {
        return $this->merchant();
    }

    public function catalogItem()
    {
        return $this->belongsTo('App\Models\CatalogItem', 'catalog_item_id')->withDefault();
    }

    public function conversation()
    {
        return $this->belongsTo('App\Models\Conversation')->withDefault();
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

    public static function countConversation()
    {
        return CatalogEvent::where('conversation_id', '!=', null)->where('is_read', '=', 0)->latest('id')->get()->count();
    }
}
