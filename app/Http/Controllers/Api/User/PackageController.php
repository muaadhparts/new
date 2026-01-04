<?php

namespace App\Http\Controllers\Api\User;

use App\Classes\MuaadhMailer;
use App\Http\Controllers\Controller;
use App\Models\Muaadhsetting;
use App\Models\MembershipPlan;
use App\Models\UserMembershipPlan;
use Auth;use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Validator;

class PackageController extends Controller
{
    public function packages()
    {

        try {

            $user = Auth::guard('api')->user();
            $membershipPlans = MembershipPlan::all();

            $package = $user->membershipPlans()->where('status', 1)->first();
            if ($package) {
                if (Carbon::now()->format('Y-m-d') > $user->date) {
                    $package->end_date = date('d/m/Y', strtotime($user->date));
                } else {
                    $package->end_date = date('d/m/Y', strtotime($user->date));
                }
            }

            return response()->json(['status' => true, 'data' => ['membershipPlans' => $membershipPlans, 'current_package' => $package], 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }

    public function packageDetails(Request $request)
    {

        try {
            //--- Validation Section

            $rules = [
                'id' => 'required',
            ];
            $customs = [
                'id.required' => 'Package ID is required.',
            ];
            $validator = Validator::make(Input::all(), $rules, $customs);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            //--- Validation Section Ends

            $gs = Muaadhsetting::findOrfail(1);
            if ($gs->reg_merchant != 1) {
                return response()->json(['status' => false, 'data' => [], 'error' => []]);
            }

            $user = Auth::guard('api')->user();
            $package = $user->membershipPlans()->where('status', 1)->orderBy('id', 'desc')->first();
            $id = $request->id;
            $data = MembershipPlan::find($id);
            if (!$data) {
                return response()->json(['status' => true, 'data' => [], 'error' => ['message' => 'Invalid ID.']]);
            }
            return response()->json(['status' => true, 'data' => ['membershipPlan' => $data, 'package' => $package], 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }

    }

    public function store(Request $request)
    {

        try {
            $rules = [
                'method' => 'required',
                'txnid' => 'required',
                'membership_plan_id' => 'required',
            ];
            $customs = [
                'method.required' => 'Payment Method is required.',
                'txnid.required' => 'Payment Transaction ID is required.',
                'membership_plan_id.required' => 'Subscription ID is required',
            ];
            $validator = Validator::make($request->all(), $rules, $customs);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
            }

            //--- Validation Section Ends

            if (!Auth::guard('api')->check()) {
                return response()->json(['status' => false, 'data' => [], 'error' => ["message" => 'Unauthenticated.']]);
            }

            $user = Auth::guard('api')->user();
            $package = $user->membershipPlans()->where('status', 1)->orderBy('id', 'desc')->first();
            $plan = MembershipPlan::findOrFail($request->membership_plan_id);
            $settings = Muaadhsetting::findOrFail(1);
            $today = Carbon::now()->format('Y-m-d');
            $input = $request->all();

            if (!empty($package)) {
                if ($package->membership_plan_id == $request->membership_plan_id) {
                    $newday = strtotime($today);
                    $lastday = strtotime($user->date);
                    $secs = $lastday - $newday;
                    $days = $secs / 86400;
                    $total = $days + $plan->days;
                    $user->date = date('Y-m-d', strtotime($today . ' + ' . $total . ' days'));
                } else {
                    $user->date = date('Y-m-d', strtotime($today . ' + ' . $plan->days . ' days'));
                }
            } else {
                $user->date = date('Y-m-d', strtotime($today . ' + ' . $plan->days . ' days'));
            }

            if ($user->is_merchant == 0) {

                //--- Validation Section

                $rules = [
                    'shop_name' => 'required|unique:users',
                    'owner_name' => 'required',
                    'shop_number' => 'required',
                    'shop_address' => 'required',
                ];
                $customs = [
                    'shop_name.required' => 'Shop name is required.',
                    'shop_name.unique' => 'This shop name has already been taken.',
                    'owner_name.required' => 'Owner name is required.',
                    'shop_number.required' => 'Shop number is required.',
                    'shop_address.required' => 'Shop address is required.',
                ];
                $validator = Validator::make(Input::all(), $rules, $customs);

                if ($validator->fails()) {
                    return response()->json(['status' => false, 'data' => [], 'error' => $validator->errors()]);
                }

                //--- Validation Section Ends

            }
            $user->is_merchant = 2;
            $user->mail_sent = 1;
            $user->update($input);

            $userPlan = new UserMembershipPlan;
            $userPlan->user_id = $user->id;
            $userPlan->membership_plan_id = $plan->id;
            $userPlan->title = $plan->title;
            $userPlan->currency = $plan->currency;
            $userPlan->currency_code = $plan->currency_code;
            $userPlan->price = $plan->price;
            $userPlan->days = $plan->days;
            $userPlan->allowed_items = $plan->allowed_items;
            $userPlan->details = $plan->details;
            $userPlan->method = $request->method;
            $userPlan->txnid = $request->txnid;
            $userPlan->status = 1;
            $userPlan->save();

            if ($settings->is_smtp == 1) {
                $data = [
                    'to' => $user->email,
                    'type' => "merchant_accept",
                    'cname' => $user->name,
                    'oamount' => "",
                    'aname' => "",
                    'aemail' => "",
                    'onumber' => "",
                ];
                $mailer = new MuaadhMailer();
                $mailer->sendAutoMail($data);
            } else {
                $headers = "From: " . $settings->from_name . "<" . $settings->from_email . ">";
                mail($user->email, 'Your Merchant Account Activated', 'Your Merchant Account Activated Successfully. Please Login to your account and build your own shop.', $headers);
            }

            return response()->json(['status' => true, 'data' => ['message' => 'Merchant Account Activated Successfully.'], 'error' => []]);
        } catch (\Exception $e) {
            return response()->json(['status' => true, 'data' => [], 'error' => ['message' => $e->getMessage()]]);
        }
    }
}
