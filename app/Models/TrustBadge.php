<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrustBadge extends Model
{
    protected $table = 'trust_badges';

    protected $fillable = ['user_id', 'attachments', 'text', 'admin_warning', 'warning_reason', 'status'];

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withDefault();
    }
}
