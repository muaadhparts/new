<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Spec extends Model
{
    protected $table = 'specs';

    protected $fillable = ['specable_id', 'specable_type', 'name', 'input_name', 'price_status', 'details_status'];

    public function specable()
    {
        return $this->morphTo();
    }

    public function specValues()
    {
        return $this->hasMany('App\Models\SpecValue');
    }
}
