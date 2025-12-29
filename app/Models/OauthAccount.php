<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OauthAccount extends Model
{
    protected $table = 'oauth_accounts';

    protected $fillable = ['provider_id', 'provider'];

    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }
}
