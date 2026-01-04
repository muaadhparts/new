<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metric extends Model
{
    protected $table = 'metrics';

    protected $fillable = ['referral', 'total_count', 'todays_count', 'today', 'type'];

    public $timestamps = false;
}
