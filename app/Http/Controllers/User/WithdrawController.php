<?php

namespace App\Http\Controllers\User;

use App\Models\Currency;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Http\Request;

class WithdrawController extends UserBaseController
{

    public function index()
    {
        $withdraws = Withdraw::where('user_id', '=', $this->user->id)->where('type', '=', 'user')->latest('id')->paginate(12);
        $sign = Currency::where('is_default', '=', 1)->first();
        return view('user.withdraw.index', compact('withdraws', 'sign'));
    }

    public function create()
    {
        $sign = Currency::where('is_default', '=', 1)->first();
        return view('user.withdraw.withdraw', compact('sign'));
    }

    public function store(Request $request)
    {

        $from = User::findOrFail($this->user->id);

        $withdrawcharge = $this->gs;
        $charge = $withdrawcharge->withdraw_fee;
        
        if ($request->amount > 0) {

            $amount = $request->amount;

            if ($from->affilate_income >= $amount) {
                $fee = (($withdrawcharge->withdraw_charge / 100) * $amount) + $charge;
                $finalamount = $amount - $fee;
                if($finalamount <= 0){
                    return back()->with('unsuccess', __('Invalid Amount.'));
                }

                if ($from->affilate_income >= $finalamount) {
                    $finalamount = number_format((float) $finalamount, 2, '.', '');
                    $from->affilate_income = $from->affilate_income - $finalamount;
                    $from->update();

                    $newwithdraw = new Withdraw();
                    $newwithdraw['user_id'] = $this->user->id;
                    $newwithdraw['method'] = $request->methods;
                    $newwithdraw['acc_email'] = $request->acc_email;
                    $newwithdraw['iban'] = $request->iban;
                    $newwithdraw['country'] = $request->acc_country;
                    $newwithdraw['acc_name'] = $request->acc_name;
                    $newwithdraw['address'] = $request->address;
                    $newwithdraw['swift'] = $request->swift;
                    $newwithdraw['reference'] = $request->reference;
                    $newwithdraw['amount'] = $finalamount;
                    $newwithdraw['fee'] = $fee;
                    $newwithdraw['type'] = 'user';
                    $newwithdraw->save();

                    return back()->with('success', __('Withdraw Request Sent Successfully.'));
                } else {
                    return back()->with('unsuccess', __('Insufficient Balance.'));

                }
            } else {
                return back()->with('unsuccess', __('Insufficient Balance.'));

            }
        } else {
            return back()->with('unsuccess', __('Invalid Amount.'));
        }

    }
}
