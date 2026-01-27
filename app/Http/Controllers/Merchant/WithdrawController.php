<?php

namespace App\Http\Controllers\Merchant;

use App\Domain\Accounting\Models\Withdraw;
use Illuminate\Http\Request;

class WithdrawController extends MerchantBaseController
{

    public function index()
    {
        $withdraws = Withdraw::where('user_id', '=', $this->user->id)->where('type', '=', 'merchant')->latest('id')->get();
        $sign = $this->curr;

        // PRE-COMPUTED: Display values (DATA_FLOW_POLICY - no date()/calculations in view)
        $withdraws->transform(function ($withdraw) use ($sign) {
            $withdraw->date_formatted = $withdraw->created_at?->format('d-M-Y') ?? 'N/A';
            $withdraw->amount_formatted = $sign->sign . round($withdraw->amount * $sign->value, 2);
            return $withdraw;
        });

        return view('merchant.withdraw.index', [
            'withdraws' => $withdraws,
        ]);
    }


    public function create()
    {
        return view('merchant.withdraw.create', ['sign' => $this->curr]);
    }


    public function store(Request $request)
    {
        // Feature disabled - wallet/balance system removed
        return redirect()->back()->with('unsuccess', __('Withdraw feature is currently unavailable.'));
    }
}
