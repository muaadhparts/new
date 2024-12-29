<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Token extends Model
{

    protected $fillable = [
        'accessToken' ,
        'refreshToken',
        'expires_at' ,

        ];

    public function isExpired()
    {
        return $this->expires_at < Carbon::now();
    }

}
