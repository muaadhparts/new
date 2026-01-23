<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrontendSetting extends Model
{
    protected $table = 'frontend_settings';

    protected $fillable = [
        'contact_email',
        'street',
        'phone',
        'fax',
        'email',
        'home',
        'blog',
        'faq',
        'contact',
        'category',
        'newsletter',
    ];

    public $timestamps = false;

}
