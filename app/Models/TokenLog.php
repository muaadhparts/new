<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenLog extends Model
{
    protected $table = 'token_logs';

    protected $fillable = [
        'type',
        'status',
        'message',
        'executed_at',
    ];
}
