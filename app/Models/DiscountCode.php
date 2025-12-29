<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    protected $table = 'discount_codes';

    protected $fillable = ['code', 'type', 'price', 'times', 'start_date', 'end_date', 'apply_to', 'category', 'sub_category', 'child_category', 'user_id'];

    public $timestamps = false;
}
