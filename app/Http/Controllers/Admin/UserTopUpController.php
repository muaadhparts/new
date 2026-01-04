<?php

namespace App\Http\Controllers\Admin;

use App\{
    Models\TopUp,
    Models\WalletLog
};
use Illuminate\{
    Http\Request,
    Support\Str
};
use Validator;
use Datatables;

class UserTopUpController extends AdminBaseController
{
    //*** JSON Request
    public function datatables($status)
    {
            $datas = TopUp::whereStatus($status)->Latest('id')->get();

             //--- Integrating This Collection Into Datatables
             return Datatables::of($datas)
                                ->addColumn('name', function(TopUp $data) {
                                    $name = '<a href="'.route('admin-user-show',$data->user_id).'" target="_blank">'.$data->user->name.'</a>';
                                    return $name;
                                })
                                ->editColumn('amount', function(TopUp $data) {
                                    $price = $data->amount * $data->currency_value;
                                    return \PriceHelper::showAdminCurrencyPrice($price);
                                })
                                ->addColumn('action', function(TopUp $data) {
                                    if($data->status == 1){
                                        return '<span class="badge badge-success top-up-completed">'.__("Completed").'</span';
                                    }else{
                                        $class = $data->status == 1 ? 'drop-success' : 'drop-warning';
                                        $s = $data->status == 1 ? 'selected' : '';
                                        $ns = $data->status == 0 ? 'selected' : '';
                                        return '<div class="action-list"><select class="process select merchant-droplinks '.$class.'"><option data-val="1" value="'. route('admin-user-top-up-status',['id1' => $data->id, 'id2' => 1]).'" '.$s.'>'.__("Completed").'</option><option data-val="0" value="'. route('admin-user-top-up-status',['id1' => $data->id, 'id2' => 0]).'" '.$ns.'>'.__("Pending").'</option></select></div>';
                                    }

                                })
                                ->rawColumns(['name','action'])
                                ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
    public function topUps($slug)
    {
        if($slug == 'all'){
            return view('admin.top-up.index');
        }else if($slug == 'pending'){
            return view('admin.top-up.pending');
        }

    }


	//*** GET Request
    public function status($id1,$id2)
    {
        $topUp = TopUp::findOrFail($id1);
        $topUp->status = $id2;
        $topUp->update();

        $user = $topUp->user;
        $user->balance = $user->balance + $topUp->amount;
        $user->save();

        // store in wallet_logs table
        if ($topUp->status == 1) {
            $walletLog = new WalletLog;
            $walletLog->txn_number = Str::random(3).substr(time(), 6,8).Str::random(3);
            $walletLog->user_id = $topUp->user_id;
            $walletLog->amount = $topUp->amount;
            $walletLog->user_id = $topUp->user_id;
            $walletLog->currency_sign = $topUp->currency;
            $walletLog->currency_code = $topUp->currency_code;
            $walletLog->currency_value= $topUp->currency_value;
            $walletLog->method = $topUp->method;
            $walletLog->txnid = $topUp->txnid;
            $walletLog->details = 'Balance Top Up';
            $walletLog->type = 'plus';
            $walletLog->save();
        }

        //--- Redirect Section
        $msg[0] = __('Status Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends

    }

}
