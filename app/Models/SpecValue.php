<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecValue extends Model
{
    protected $table = 'spec_values';

    protected $fillable = ['attribute_id', 'name'];

    public function attribute()
    {
        return $this->belongsTo('App\Models\Attribute')->withDefault();
    }
}
