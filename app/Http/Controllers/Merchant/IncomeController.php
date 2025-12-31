<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MerchantPurchase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncomeController extends Controller
{
    public function index(Request $request)
    {
        if($request->start_date && $request->end_date){
            $start_date = Carbon::parse($request->start_date);
            $end_date = Carbon::parse($request->end_date);
            $datas = MerchantPurchase::with('purchase')->whereDate('created_at','>=',$start_date)->whereDate('created_at','<=',$end_date);
        }else{
            $datas = MerchantPurchase::with('purchase')->where('user_id',Auth::user()->id);
        }

        return view('merchant.earning',[
            'datas' => $datas->count() > 0 ? $datas->get() : [],
            'total' => $datas->count() > 0 ? $datas->get()[0]->purchase->currency_sign . $datas->sum('price') : 0,
            'start_date' => isset($start_date) ? $start_date : '',
            'end_date' => isset($end_date) ? $end_date : '',
        ]);
    }


}
