<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaticContent extends Model
{
    protected $table = 'static_content';

    protected $fillable = ['name', 'slug', 'details', 'meta_tag', 'meta_description', 'photo'];

    public $timestamps = false;
}
