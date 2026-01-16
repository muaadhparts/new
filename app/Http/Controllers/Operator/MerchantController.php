<?php

namespace App\Http\Controllers\Operator;

use App\Classes\MuaadhMailer;
use App\Models\MerchantCommission;
use App\Models\MerchantItem;
use App\Models\Muaadhsetting;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Models\UserMembershipPlan;
use App\Models\Withdraw;
use Auth;
use Carbon\Carbon;
use Datatables;
use Illuminate\Http\Request;
use Validator;

class MerchantController extends OperatorBaseController
{
    //*** JSON Request
    public function datatables()
    {
        $datas = User::where('is_merchant', '=', 2)->orWhere('is_merchant', '=', 1)->latest('id')->get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->addColumn('status', function (User $data) {
                $class = $data->is_merchant == 2 ? 'drop-success' : 'drop-danger';
                $s = $data->is_merchant == 2 ? 'selected' : '';
                $ns = $data->is_merchant == 1 ? 'selected' : '';
                return '<div class="action-list"><select class="process select merchant-droplinks ' . $class . '">' .
                '<option value="' . route('operator-merchant-st', ['id1' => $data->id, 'id2' => 2]) . '" ' . $s . '>' . __("Activated") . '</option>' .
                '<option value="' . route('operator-merchant-st', ['id1' => $data->id, 'id2' => 1]) . '" ' . $ns . '>' . __("Deactivated") . '</option></select></div>';
            })
            ->editColumn('operator_commission', function (User $data) {
                $collect = $data->operator_commission > 0 ? '<a href="' . route('operator-merchant-commission-collect', $data->id) . '" class="btn btn-primary btn-sm">Collect</a>' : '';
                $url = '<div class="action-list"><p class="mx-3 d-inline-block">' . \PriceHelper::showAdminCurrencyPrice($data->operator_commission) . '</p>
                ' . $collect . '</div>';
                return $url;
            })
            ->addColumn('action', function (User $data) {
                return '<div class="godropdown"><button class="go-dropdown-toggle"> ' . __("Actions") . '<i class="fas fa-chevron-down"></i></button><div class="action-list"><a href="' . route('operator-merchant-secret', $data->id) . '" > <i class="fas fa-user"></i> ' . __("Secret Login") . '</a><a href="' . route('operator-merchant-show', $data->id) . '" > <i class="fas fa-eye"></i> ' . __("Details") . '</a><a data-href="' . route('operator-merchant-edit', $data->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-edit"></i> ' . __("Edit") . '</a><a href="javascript:;" class="send" data-email="' . $data->email . '" data-bs-toggle="modal" data-bs-target="#merchantform"><i class="fas fa-envelope"></i> ' . __("Send Email") . '</a><a href="javascript:;" data-href="' . route('operator-merchant-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i> ' . __("Delete") . '</a></div></div>';
            })
            ->rawColumns(['status', 'action', 'operator_commission'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    public function index()
    {
        return view('operator.merchant.index');
    }

    public function withdraws()
    {
        return view('operator.merchant.withdraws');
    }

    //*** GET Request
    public function status($id1, $id2)
    {
        $user = User::findOrFail($id1);
        $user->is_merchant = $id2;
        $user->update();
        //--- Redirect Section
        $msg[0] = __('Status Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends

    }

    //*** GET Request
    public function edit($id)
    {
        $data = User::findOrFail($id);
        return view('operator.merchant.edit', compact('data'));
    }

    //*** GET Request
    public function requestTrustBadge($id)
    {
        $data = User::findOrFail($id);
        return view('operator.merchant.request-trust-badge', compact('data'));
    }

    //*** POST Request
    public function requestTrustBadgeSubmit(Request $request, $id)
    {
        $settings = Muaadhsetting::find(1);
        $user = User::findOrFail($id);
        $user->trustBadges()->create(['admin_warning' => 1, 'warning_reason' => $request->details]);

        if ($settings->is_smtp == 1) {
            $data = [
                'to' => $user->email,
                'type' => "trust_badge_request",
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
            mail($user->email, 'Request for verification.', 'You are requested verify your account. Please send us photo of your passport.Thank You.', $headers);
        }

        $msg = 'Verification Request Sent Successfully.';
        return response()->json($msg);
    }

    //*** POST Request
    public function update(Request $request, $id)
    {
        //--- Validation Section
        $rules = [
            'shop_name' => 'unique:users,shop_name,' . $id,
        ];
        $customs = [
            'shop_name.unique' => 'Shop Name "' . $request->shop_name . '" has already been taken. Please choose another name.',
        ];

        $validator = Validator::make($request->all(), $rules, $customs);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        $user = User::findOrFail($id);
        $data = $request->all();
        $user->update($data);
        $msg = 'Merchant Information Updated Successfully.';
        return response()->json($msg);
    }

    //*** GET Request
    public function show($id)
    {
        $data = User::findOrFail($id);
        return view('operator.merchant.show', compact('data'));
    }

    //*** JSON Request - Merchant Items DataTables
    public function merchantItemsDatatables($id)
    {
        $datas = MerchantItem::with(['catalogItem.brand', 'qualityBrand'])
            ->where('user_id', $id)
            ->latest('id');

        $commission = MerchantCommission::getOrCreateForMerchant($id);

        return Datatables::of($datas)
            ->addColumn('mp_id', function (MerchantItem $data) {
                $dt = $data->catalogItem;
                $adminMerchantUrl = $dt && $dt->slug
                    ? route('front.catalog-item', ['slug' => $dt->slug, 'merchant_id' => $data->user_id, 'merchant_item_id' => $data->id])
                    : '#';
                return '<a href="' . $adminMerchantUrl . '" target="_blank">' . sprintf("%'.06d", $data->id) . '</a>';
            })
            ->addColumn('name', function (MerchantItem $data) {
                $dt = $data->catalogItem;
                return $dt ? getLocalizedCatalogItemName($dt, 50) : __('N/A');
            })
            ->addColumn('brand', function (MerchantItem $data) {
                $dt = $data->catalogItem;
                return $dt && $dt->brand ? getLocalizedBrandName($dt->brand) : __('N/A');
            })
            ->addColumn('quality_brand', function (MerchantItem $data) {
                return $data->qualityBrand ? getLocalizedQualityName($data->qualityBrand) : __('N/A');
            })
            ->addColumn('condition', function (MerchantItem $data) {
                $condition = $data->item_condition == 1 ? __('Used') : __('New');
                $class = $data->item_condition == 1 ? 'badge-warning' : 'badge-success';
                return '<span class="badge ' . $class . '">' . $condition . '</span>';
            })
            ->addColumn('stock', function (MerchantItem $data) {
                $stck = $data->stock;
                if ($stck === null || $stck === '') {
                    return __('Unlimited');
                } elseif ((int)$stck === 0) {
                    return '<span class="text-danger">' . __('Out Of Stock') . '</span>';
                }
                return $stck;
            })
            ->addColumn('price', function (MerchantItem $data) use ($commission) {
                $price = (float) $data->price;
                $finalPrice = $commission->getPriceWithCommission($price);
                return \PriceHelper::showAdminCurrencyPrice($finalPrice);
            })
            ->addColumn('status', function (MerchantItem $data) {
                $class = $data->status == 1 ? 'drop-success' : 'drop-danger';
                return '<div class="action-list">
                    <select class="process select droplinks ' . $class . '">
                        <option data-val="1" value="' . route('operator-merchant-item-status', ['id' => $data->id, 'status' => 1]) . '" ' . ($data->status == 1 ? 'selected' : '') . '>' . __("Activated") . '</option>
                        <option data-val="0" value="' . route('operator-merchant-item-status', ['id' => $data->id, 'status' => 0]) . '" ' . ($data->status == 0 ? 'selected' : '') . '>' . __("Deactivated") . '</option>
                    </select>
                </div>';
            })
            ->addColumn('action', function (MerchantItem $data) {
                $dt = $data->catalogItem;
                return '<a href="' . route('operator-catalog-item-edit', $dt->id ?? 0) . '" class="view-details">
                    <i class="fas fa-eye"></i>' . __("Details") . '
                </a>';
            })
            ->rawColumns(['mp_id', 'condition', 'stock', 'status', 'action'])
            ->toJson();
    }

    //*** GET Request
    public function secret($id)
    {
        Auth::guard('web')->logout();
        $data = User::findOrFail($id);
        Auth::guard('web')->login($data);
        return redirect()->route('merchant.dashboard');
    }

    //*** GET Request
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->is_merchant = 0;
        $user->is_merchant = 0;
        $user->shop_name = null;
        $user->shop_details = null;
        $user->owner_name = null;
        $user->shop_number = null;
        $user->shop_address = null;
        $user->reg_number = null;
        $user->shop_message = null;
        $user->update();
        if ($user->userCatalogEvents->count() > 0) {
            foreach ($user->userCatalogEvents as $gal) {
                $gal->delete();
            }
        }
        //--- Redirect Section
        $msg = 'Merchant Deleted Successfully.';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** JSON Request
    public function withdrawdatatables()
    {
        $datas = Withdraw::where('type', '=', 'merchant')->latest('id')->get();
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)
            ->addColumn('name', function (Withdraw $data) {
                $name = $data->user->name;
                return '<a href="' . route('operator-merchant-show', $data->user->id) . '" target="_blank">' . $name . '</a>';
            })
            ->addColumn('email', function (Withdraw $data) {
                $email = $data->user->email;
                return $email;
            })
            ->addColumn('phone', function (Withdraw $data) {
                $phone = $data->user->phone;
                return $phone;
            })
            ->editColumn('status', function (Withdraw $data) {
                $status = ucfirst($data->status);
                return $status;
            })
            ->editColumn('amount', function (Withdraw $data) {
                $sign = $this->curr;
                $amount = $data->amount * $sign->value;
                return \PriceHelper::showAdminCurrencyPrice($amount);
            })
            ->addColumn('action', function (Withdraw $data) {
                $action = '<div class="action-list"><a data-href="' . route('operator-merchant-withdraw-show', $data->id) . '" class="view details-width" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-eye"></i> Details</a>';
                if ($data->status == "pending") {
                    $action .= '<a data-href="' . route('operator-merchant-withdraw-accept', $data->id) . '" data-bs-toggle="modal" data-bs-target="#status-modal1"> <i class="fas fa-check"></i> Accept</a><a data-href="' . route('operator-merchant-withdraw-reject', $data->id) . '" data-bs-toggle="modal" data-bs-target="#status-modal"> <i class="fas fa-trash-alt"></i> Reject</a>';
                }
                $action .= '</div>';
                return $action;
            })
            ->rawColumns(['name', 'action'])
            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
    public function withdrawdetails($id)
    {
        $sign = $this->curr;
        $withdraw = Withdraw::findOrFail($id);
        return view('operator.merchant.withdraw-details', compact('withdraw', 'sign'));
    }

    //*** GET Request
    public function accept($id)
    {
        $withdraw = Withdraw::findOrFail($id);
        $data['status'] = "completed";
        $withdraw->update($data);
        //--- Redirect Section
        $msg = 'Withdraw Accepted Successfully.';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function reject($id)
    {
        $withdraw = Withdraw::findOrFail($id);
        $data['status'] = "rejected";
        $withdraw->update($data);
        //--- Redirect Section
        $msg = 'Withdraw Rejected Successfully.';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    //*** GET Request
    public function addMembershipPlan($id)
    {
        $data = User::findOrFail($id);
        return view('operator.merchant.add-membership-plan', compact('data'));
    }

    //*** POST Request
    public function addMembershipPlanStore(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $package = $user->membershipPlans()->where('status', 1)->orderBy('id', 'desc')->first();
        $plan = MembershipPlan::findOrFail($request->plan_id);
        $settings = Muaadhsetting::findOrFail(1);
        $today = Carbon::now()->format('Y-m-d');
        $user->is_merchant = 2;
        if (!empty($package)) {
            if ($package->membership_plan_id == $request->plan_id) {
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
        $user->mail_sent = 1;
        $user->update();
        $userPlan = new UserMembershipPlan;
        $userPlan->user_id = $user->id;
        $userPlan->membership_plan_id = $plan->id;
        $userPlan->name = $plan->name;
        $userPlan->currency_sign = $this->curr->sign;
        $userPlan->currency_code = $this->curr->name;
        $userPlan->currency_value = $this->curr->value;
        $userPlan->price = $plan->price * $this->curr->value;
        $userPlan->price = $userPlan->price / $this->curr->value;
        $userPlan->days = $plan->days;
        $userPlan->allowed_items = $plan->allowed_items;
        $userPlan->details = $plan->details;
        $userPlan->status = 1;
        $userPlan->save();
        if ($settings->is_smtp == 1) {
            $data = [
                'to' => $user->email,
                'type' => "merchant_trusted",
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

        $msg = 'Membership Plan Added Successfully.';
        return response()->json($msg);
    }

    public function commissionCollect($id)
    {
        $user = User::findOrFail($id);
        if (!$user) {
            return redirect()->back()->with('unsuccess', 'Merchant not found!');
        } else {
            $user->operator_commission = 0;
            $user->update();
            return redirect()->back()->with('success', 'Commission collected successfully!');
        }
    }
}
