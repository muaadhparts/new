<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopUp extends Model
{
    protected $table = 'top_ups';

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withDefault();
    }
}
