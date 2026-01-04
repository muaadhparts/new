<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $table = 'announcements';

    protected $fillable = ['photo', 'link', 'type', 'title', 'text'];

    public $timestamps = false;
}
