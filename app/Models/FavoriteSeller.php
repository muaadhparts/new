<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteSeller extends Model
{
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function merchant()
    {
        return $this->belongsTo('App\Models\User','user_id');
    }

    /**
     * @deprecated Use merchant() instead
     */
    public function vendor()
    {
        return $this->merchant();
    }
}
