<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Capability extends Model
{
    protected $table = 'capabilities';

    protected $fillable = ['user_id', 'name', 'details', 'photo'];

    public $timestamps = false;
}
