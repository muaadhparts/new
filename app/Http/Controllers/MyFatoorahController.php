<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Merchant\Payment\MyFatoorahPaymentController;
use Illuminate\Http\Request;

/**
 * MyFatoorah Controller - Stub
 *
 * This is a stub controller required by the myfatoorah/laravel-package.
 * All actual payment logic is handled by MyFatoorahPaymentController.
 *
 * @deprecated Use App\Http\Controllers\Merchant\Payment\MyFatoorahPaymentController instead
 */
class MyFatoorahController extends Controller
{
    protected MyFatoorahPaymentController $paymentController;

    public function __construct(MyFatoorahPaymentController $paymentController)
    {
        $this->paymentController = $paymentController;
    }

    /**
     * Redirect to new checkout flow
     */
    public function index(Request $request, int $merchantId = 0)
    {
        if (!$merchantId) {
            $merchantId = session('checkout_merchant_id', 0);
        }

        if (!$merchantId) {
            return redirect()->route('front.cart')
                ->with('unsuccess', __('Please start checkout from the cart'));
        }

        return $this->paymentController->processPayment($request, $merchantId);
    }

    /**
     * Handle MyFatoorah callback - delegate to new controller
     */
    public function notify(Request $request)
    {
        return $this->paymentController->notify($request);
    }

    /**
     * Handle callback - delegate to new controller
     */
    public function callback(Request $request)
    {
        return $this->paymentController->handleCallback($request);
    }
}
