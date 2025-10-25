<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    public $timestamps = false;
    protected $fillable = ['state', 'state_ar', 'country_id', 'status', 'tax', 'owner_id'];

    public function country()
    {
        return $this->belongsTo('App\Models\Country')->withDefault();
    }

    public function cities()
    {
        return $this->hasMany('App\Models\City');
    }
}
