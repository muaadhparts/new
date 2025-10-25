<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = ['country_code', 'country_name', 'country_name_ar', 'tax', 'status'];
    public $timestamps = false;

    public function states()
    {
        return $this->hasMany('App\Models\State');
    }
}
