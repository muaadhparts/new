<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\TopUp;
use App\Models\WalletLog;
use App\Models\User;
use App\Models\MerchantPayment;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Validator;

class TopUpController extends Controller
{

    public function sendTopUp($number)
    {
        $topup = TopUp::where('deposit_number', $number)->first();
        $curr = Currency::where('name', '=', $topup->currency_code)->firstOrFail();
        $gateways = MerchantPayment::scopeHasGateway($curr->id);
        $paystack = MerchantPayment::whereKeyword('paystack')->first();
        $paystackData = $paystack->convertAutoData();

        if ($topup->status == 1) {
            return response()->json(['status' => false, 'data' => [], 'error' => "TopUp Already Added."]);
        }
        return view('user.top-up.payment', compact('topup', 'gateways', 'paystackData'));
    }

    public function topups()
    {
        try {
            $user = Auth::guard('api')->user();
            if ($user->topups->count() == 0) {
                return response()->json(['status' => true, 'data' => [], 'error' => []]);
            }
            foreach ($user->topups as $topup) {
                if ($topup->status != 1) {
                    $topup['payment_url'] = route('user.topup.send', $topup->deposit_number);
                    $topups_list[] = $topup;
                } else {
                    $topups_list[] = $topup;
                }
            }

            return response()->json(['status' => true, 'data' => $topups_list, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function transactions()
    {
        try {
            $user = Auth::guard('api')->user();
            return response()->json(['status' => true, 'data' => $user->transactions, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function transactionDetails(Request $request)
    {
        try {

            //--- Validation Section

            $rules = [
                'id' => 'required',
            ];
            $customs = [
                'id.required' => 'Transaction ID is required.',
            ];
            $validator = Validator::make($request->all(), $rules, $customs);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            //--- Validation Section Ends

            $id = $request->id;
            $data = WalletLog::find($id);
            if (!$data) {
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'Invalid ID.']]);
            }
            return response()->json(['status' => true, 'data' => $data, 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function store(Request $request)
    {

        try {

            //--- Validation Section

            $rules = [
                'amount' => 'required',
                'currency_code' => 'required',

            ];
            $customs = [
                'amount.required' => 'Payment Amount is required.',
                'currency_code.required' => 'Currency Field is required.',
            ];
            $validator = Validator::make($request->all(), $rules, $customs);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            //--- Validation Section Ends

            $input = $request->all();

            if (!Auth::guard('api')->check()) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => 'Unauthenticated.']]);
            }

            $curr = Currency::where('name', '=', $request->currency_code)->first();
            $user = Auth::guard('api')->user();
            $topup_number = Str::random(4) . time();

            $topup = new TopUp;
            $topup->user_id = $user->id;
            $topup->currency_value = $curr->value;
            $topup->currency = $curr->name;
            $topup->amount = $request->amount / $curr->value;
            $topup->currency_code = $curr->name;
            $topup->method = $request->method;
            $topup->txnid = $request->txnid;
            $topup->status = 0;
            $topup->deposit_number = $topup_number;
            $topup->save();

            return response()->json(['status' => true, 'data' => route('user.topup.send', $topup_number), 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }
}
