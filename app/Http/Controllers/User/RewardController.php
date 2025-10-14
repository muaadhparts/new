<?php

namespace App\Http\Controllers\User;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RewardController extends UserBaseController
{
    public function rewards()
    {
        $curr = Currency::where('is_default','=',1)->first();
        $user = Auth::user();
        $datas = Transaction::where('reward_point',"!=",0)->where('user_id',$user->id)->orderby('id','desc')->paginate(12);
        return view('user.reward.index',compact('user','datas','curr'));
    }

    public function convert()
    {
        $curr = Currency::where('is_default','=',1)->first();
        $user = Auth::user();
        return view('user.reward.convert',compact('user','curr'));
    }


    public function convertSubmit(Request $request)
    {
        $user = Auth::user();
        $gs = Generalsetting::find(1);

        $rules =
        [
            'reward_point' => 'required|integer|max:'.$user->reward.'|min:'.$gs->reward_point
        ];

       $request->validate($rules);

   
        $dolar = ($request->reward_point / $gs->reward_point)  * $gs->reward_dolar;

        $user->reward = $user->reward - $request->reward_point;
        $user->balance = $user->balance + $dolar;
        $user->update();
        $trans =  new Transaction();
        $trans->user_id = $user->id;
        $trans->amount = $dolar;
        $trans->txn_number = Str::random(3).substr(time(), 6,8).Str::random(3);
        $trans->reward_point = $request->reward_point;
        $trans->currency_sign = "$";
        $trans->currency_code = "USD";
        $trans->currency_value = 1;
        $trans->reward_dolar = $dolar;
        $trans->type = 'plus';
        $trans->details = 'Reward Point Convert';
        $trans->save();

        return back()->with('success',__('Your Wallet Balance Added ' . ' : $'. $dolar));


    }
}
