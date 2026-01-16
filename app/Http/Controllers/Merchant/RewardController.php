<?php

namespace App\Http\Controllers\Merchant;

use App\Models\Reward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RewardController extends MerchantBaseController
{
    /**
     * Display merchant's reward tiers
     */
    public function index()
    {
        $merchantId = Auth::user()->id;
        $datas = Reward::merchantOnly($merchantId)->get();

        // Get default currency sign
        $sign = monetaryUnit()->getDefault();

        return view('merchant.reward.index', compact('datas', 'sign'));
    }

    /**
     * Update merchant's reward tiers
     */
    public function update(Request $request)
    {
        $merchantId = Auth::user()->id;

        // Delete existing rewards for this merchant
        Reward::where('user_id', $merchantId)->delete();

        // Get the point value (shared across all tiers for this merchant)
        $pointValue = (float) $request->get('point_value', 1.00);

        if ($request->purchase_amount && $request->reward) {
            foreach ($request->purchase_amount as $key => $amount) {
                if ($amount > 0 && $request->reward[$key] > 0) {
                    $data = new Reward();
                    $data->user_id = $merchantId;
                    $data->purchase_amount = $amount;
                    $data->reward = $request->reward[$key];
                    $data->point_value = $pointValue;
                    $data->save();
                }
            }
        }

        $mgs = __('Data Update Successfully');
        return response()->json($mgs);
    }
}
