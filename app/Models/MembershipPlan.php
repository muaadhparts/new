<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    protected $table = 'membership_plans';
    protected $fillable = ['title','price','days','allowed_items','details'];
    public $timestamps = false;
}
