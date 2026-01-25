<?php

namespace App\Domain\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Referral Commission Model
 *
 * Tracks referral commissions earned by users.
 */
class ReferralCommission extends Model
{
    protected $table = 'referral_commissions';

    protected $fillable = [
        'user_id',
        'referred_user_id',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
