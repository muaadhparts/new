<?php

namespace App\Http\Controllers\User;

use App\Models\Reward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RewardController extends UserBaseController
{
    public function rewards()
    {
        $curr = monetaryUnit()->getDefault();
        $user = Auth::user();

        // Get the point value (use platform default, or 1.00 as fallback)
        $pointValue = Reward::getMerchantPointValue(0);

        // Calculate monetary value of user's points
        $pointsValue = $user->reward * $pointValue;

        return view('user.reward.index', compact('user', 'curr', 'pointValue', 'pointsValue'));
    }
}
