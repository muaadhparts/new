<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;

class CommsBlueprint extends Model
{
    protected $table = 'comms_blueprints';

    public $timestamps = false;

    protected $fillable = [
        'email_type',
        'email_subject',
        'email_body',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];
}
