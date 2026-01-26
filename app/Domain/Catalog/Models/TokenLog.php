<?php

namespace App\Domain\Catalog\Models;

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

    protected $casts = [
        'executed_at' => 'datetime',
    ];
}
