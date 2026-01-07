<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seotool extends Model
{
    protected $fillable = [
        'google_analytics',
        'gtm_id',
        'search_console_verification',
        'bing_verification',
        'facebook_pixel',
        'meta_keys',
        'meta_description'
    ];
    public $timestamps = false;
}
