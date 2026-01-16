<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\MonetaryUnit;
use App\Models\Muaadhsetting;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Http\Request;
use Datatables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RewardController extends Controller
{
    /**
     * Display all rewards with merchant filter
     */
    public function index(Request $request)
    {
        $merchantId = $request->get('merchant_id', 0);

        // Get list of merchants for filter dropdown
        $merchants = User::where('is_merchant', 1)->where('status', 1)->get(['id', 'shop_name', 'name']);

        if ($merchantId > 0) {
            $datas = Reward::merchantOnly($merchantId)->get();
        } else {
            // Platform-only by default
            $datas = Reward::platformOnly()->get();
        }

        $selectedMerchant = $merchantId > 0 ? User::find($merchantId) : null;
        $sign = monetaryUnit()->getDefault();

        return view('operator.reward.index', compact('datas', 'merchants', 'merchantId', 'selectedMerchant', 'sign'));
    }

    /**
     * Update rewards for a specific merchant or platform
     */
    public function update(Request $request)
    {
        $merchantId = (int) $request->get('merchant_id', 0);

        // Delete existing rewards for this merchant/platform
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

    /**
     * Update global reward settings (enable/disable, conversion rates)
     */
    public function infoUpdate(Request $request)
    {
        $rules = ['reward_point' => 'required|integer|min:1', 'reward_dolar' => 'required|numeric|min:0'];
        $customs = ['reward_dolar.required' => __('Reward value field is required.'), 'reward_point.required' => __('Reward point field is required.')];
        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }

        $data = Muaadhsetting::findOrFail(1);
        $data->reward_dolar = $request->reward_dolar;
        $data->reward_point = $request->reward_point;
        $data->update();
        cache()->forget('muaadhsettings');
        $mgs = __('Data Update Successfully');
        return response()->json($mgs);
    }

    /**
     * Get rewards for a specific merchant (AJAX)
     */
    public function getMerchantRewards(Request $request)
    {
        $merchantId = (int) $request->get('merchant_id', 0);

        if ($merchantId > 0) {
            $rewards = Reward::merchantOnly($merchantId)->get();
        } else {
            $rewards = Reward::platformOnly()->get();
        }

        return response()->json([
            'status' => true,
            'data' => $rewards,
            'point_value' => $rewards->first()?->point_value ?? 1.00
        ]);
    }
}
