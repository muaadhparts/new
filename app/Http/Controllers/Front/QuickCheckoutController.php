<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QuickCheckoutController extends FrontBaseController
{
    public function quick()
    {
        return redirect()->route('front.checkout');
    }
}