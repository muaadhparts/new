<?php

namespace App\Http\Controllers\Admin;


use App\{
    Models\User,
    Models\MembershipPlan,
    Models\UserMembershipPlan
};

use Illuminate\Http\Request;
use Carbon\Carbon;
use Datatables;

class MerchantMembershipPlanController extends AdminBaseController
{
    //*** GET Request
    public function datatables($status)
    {
         $datas = UserMembershipPlan::whereStatus($status)->latest('id')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->addColumn('name', function(UserMembershipPlan $data) {
                                $name = isset($data->user->owner_name) ? $data->user->owner_name : __('Removed');
                                return  $name;
                            })

                            ->editColumn('txnid', function(UserMembershipPlan $data) {
                                $txnid = $data->txnid == null ? __('Free') : $data->txnid;
                                return $txnid;
                            })
                            ->editColumn('created_at', function(UserMembershipPlan $data) {
                                $date = $data->created_at->diffForHumans();
                                return $date;
                            })
                            ->addColumn('action', function(UserMembershipPlan $data) {
                                $status = '';
                                if($data->status == 0){
                                    $class = $data->status == 1 ? 'drop-success' : 'drop-warning';
                                    $s = $data->status == 1 ? 'selected' : '';
                                    $ns = $data->status == 0 ? 'selected' : '';
                                    $status =  '<select class="process select merchant-droplinks '.$class.'">
                                                    <option data-val="1" value="'. route('admin-user-membership-plan-status',['id1' => $data->id, 'id2' => 1]).'" '.$s.'>
                                                    '.__("Completed").
                                                    '</option>
                                                    <option data-val="0" value="'. route('admin-user-membership-plan-status',['id1' => $data->id, 'id2' => 0]).'" '.$ns.'>
                                                    '.__("Pending").'
                                                    </option>
                                                </select>';
                                }

                                return '<div class="action-list">'.$status.'<a data-href="' . route('admin-merchant-membership-plan',$data->id) . '" class="view details-width" data-bs-toggle="modal" data-bs-target="#modal1"> <i class="fas fa-eye"></i>'.__('Details').'</a></div>';
                            })
                            ->rawColumns(['action'])
                            ->toJson(); //--- Returning Json Data To Client Side
    }

	//*** GET Request
    public function index($slug)
    {
        if($slug == 'completed'){
            return view('admin.merchant.membership-plans');
        }else if($slug == 'pending'){
            return view('admin.merchant.pending-membership-plans');
        }

    }



	//*** GET Request
    public function show($id)
    {
        $membershipPlan = UserMembershipPlan::findOrFail($id);
        return view('admin.merchant.membership-plan-details',compact('membershipPlan'));
    }

	//*** GET Request
    public function status($id1,$id2)
    {
        $userPlan = UserMembershipPlan::findOrFail($id1);
        $userPlan->status = $id2;
        $userPlan->update();

        $user = User::findOrFail($userPlan->user_id);
        $package = $user->membershipPlans()->where('status',1)->orderBy('id','desc')->first();
        $plan = MembershipPlan::findOrFail($userPlan->membership_plan_id);
        $today = Carbon::now()->format('Y-m-d');
        $user->is_merchant = 2;

        if(!empty($package))
        {
            if($package->membership_plan_id == $userPlan->id)
            {
                $newday = strtotime($today);
                $lastday = strtotime($user->date);
                $secs = $lastday-$newday;
                $days = $secs / 86400;
                $total = $days+$plan->days;
                $user->date = date('Y-m-d', strtotime($today.' + '.$total.' days'));
            }
            else
            {
                $user->date = date('Y-m-d', strtotime($today.' + '.$plan->days.' days'));
            }
        }
        else
        {
            $user->date = date('Y-m-d', strtotime($today.' + '.$plan->days.' days'));
        }
        $user->mail_sent = 1;
        $user->update();

        //--- Redirect Section
        $msg[0] = __('Status Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends

    }

}
