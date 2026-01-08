<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    protected $fillable = ['purchase_amount','reward'];
    public $timestamps = false;

}
