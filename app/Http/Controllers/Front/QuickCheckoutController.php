<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QuickCheckoutController extends FrontBaseController
{
    public function quick()
    {
        return redirect()->route('front.cart')->with('info', __('Please select a merchant to proceed with checkout.'));
    }
}