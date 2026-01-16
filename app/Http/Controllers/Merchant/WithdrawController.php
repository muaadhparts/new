<?php

namespace App\Http\Controllers\Merchant;

use App\Models\Withdraw;
use Illuminate\Http\Request;

class WithdrawController extends MerchantBaseController
{

    public function index()
    {
        $withdraws = Withdraw::where('user_id', '=', $this->user->id)->where('type', '=', 'merchant')->latest('id')->get();
        $sign = $this->curr;
        return view('merchant.withdraw.index', compact('withdraws', 'sign'));
    }


    public function create()
    {
        $sign = $this->curr;
        return view('merchant.withdraw.create', compact('sign'));
    }


    public function store(Request $request)
    {
        // Feature disabled - wallet/balance system removed
        return redirect()->back()->with('unsuccess', __('Withdraw feature is currently unavailable.'));
    }
}
