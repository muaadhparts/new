<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseTimeline extends Model
{
    protected $table = 'purchase_timelines';

    protected $fillable = ['purchase_id', 'name', 'text'];

    public function purchase()
    {
        return $this->belongsTo('App\Models\Purchase', 'purchase_id')->withDefault();
    }
}
