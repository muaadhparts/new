<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMembershipPlan extends Model
{
    protected $table = 'user_membership_plans';
    protected $fillable = ['user_id', 'membership_plan_id', 'title', 'currency_sign', 'currency_code','currency_value', 'price', 'days', 'allowed_items', 'details', 'method', 'txnid', 'charge_id', 'flutter_id', 'created_at', 'updated_at', 'status','payment_number'];

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withDefault();
    }

    public function membershipPlan()
    {
        return $this->belongsTo('App\Models\MembershipPlan', 'membership_plan_id')->withDefault();
    }
}
