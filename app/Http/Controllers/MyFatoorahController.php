<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * MyFatoorah Controller Stub
 *
 * Required by myfatoorah/laravel-package.
 * All payment logic handled by Merchant\Payment\MyFatoorahPaymentController.
 */
class MyFatoorahController extends Controller
{
    /**
     * Redirect to cart - old checkout not supported
     */
    public function index(Request $request)
    {
        return redirect()->route('merchant-cart.index')
            ->with('unsuccess', __('Please use the merchant checkout'));
    }

    /**
     * Callback - delegate to new controller
     */
    public function callback(Request $request)
    {
        return app(\App\Http\Controllers\Merchant\Payment\MyFatoorahPaymentController::class)
            ->handleCallback($request);
    }

    /**
     * Checkout view - redirect to cart
     */
    public function checkout(Request $request)
    {
        return redirect()->route('merchant-cart.index')
            ->with('unsuccess', __('Please use the merchant checkout'));
    }

    /**
     * Webhook handler - delegate to new controller
     */
    public function webhook(Request $request)
    {
        return app(\App\Http\Controllers\Merchant\Payment\MyFatoorahPaymentController::class)
            ->notify($request);
    }
}
