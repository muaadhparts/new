<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'type', 'price', 'times', 'start_date','end_date','coupon_type','category','sub_category','child_category','user_id'];
    public $timestamps = false;
}
