<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletLog extends Model
{
    protected $table = 'wallet_logs';

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withDefault();
    }
}
