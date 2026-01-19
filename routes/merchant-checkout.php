<?php

/**
 * Branch Checkout Routes (API-First Architecture)
 *
 * All routes require authentication and follow RESTful conventions.
 * Controllers return JSON responses for API calls, or views for browser requests.
 *
 * NOTE: Checkout is now branch-scoped (branchId parameter),
 *       but payment/shipping methods remain merchant-scoped (from branch->user)
 */

use App\Http\Controllers\Merchant\CheckoutMerchantController;
use App\Http\Controllers\Merchant\Payment\CodPaymentController;
use App\Http\Controllers\Merchant\Payment\StripePaymentController;
use App\Http\Controllers\Merchant\Payment\PaypalPaymentController;
use App\Http\Controllers\Merchant\Payment\ManualPaymentController;
use App\Http\Controllers\Merchant\Payment\RazorpayPaymentController;
use App\Http\Controllers\Merchant\Payment\MyFatoorahPaymentController;
use App\Http\Controllers\Merchant\Payment\InstamojoPaymentController;
use App\Http\Controllers\Merchant\Payment\PaytmPaymentController;
use App\Http\Controllers\Merchant\Payment\MolliePaymentController;
use App\Http\Controllers\Merchant\Payment\PaystackPaymentController;
use App\Http\Controllers\Merchant\Payment\FlutterwavePaymentController;
use App\Http\Controllers\Merchant\Payment\MercadopagoPaymentController;
use App\Http\Controllers\Merchant\Payment\VoguepayPaymentController;
use App\Http\Controllers\Merchant\Payment\SslCommerzPaymentController;
use App\Http\Controllers\Merchant\Payment\AuthorizeNetPaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Branch Checkout Routes (NEW - Primary)
|--------------------------------------------------------------------------
|
| New API-First checkout flow for branch-specific purchases.
| All routes are prefixed with /branch/{branchId}/checkout
|
*/

Route::prefix('branch/{branchId}/checkout')
    ->middleware(['web', 'preserve.session'])
    ->group(function () {

        // ====================================================================
        // CHECKOUT FLOW ROUTES
        // ====================================================================

        // Step 1: Address
        Route::get('/address', [CheckoutMerchantController::class, 'showAddress'])
            ->name('branch.checkout.address');

        Route::post('/address', [CheckoutMerchantController::class, 'processAddress'])
            ->name('branch.checkout.address.submit');

        // Step 2: Shipping
        Route::get('/shipping', [CheckoutMerchantController::class, 'showShipping'])
            ->name('branch.checkout.shipping');

        Route::post('/shipping', [CheckoutMerchantController::class, 'processShipping'])
            ->name('branch.checkout.shipping.submit');

        // Step 3: Payment
        Route::get('/payment', [CheckoutMerchantController::class, 'showPayment'])
            ->name('branch.checkout.payment');

        // Step 4: Return/Confirmation
        Route::get('/return/{status?}', [CheckoutMerchantController::class, 'showReturn'])
            ->name('branch.checkout.return');

        // ====================================================================
        // UTILITY ROUTES
        // ====================================================================

        // Calculate tax for location (AJAX)
        Route::post('/calculate-tax', [CheckoutMerchantController::class, 'calculateTax'])
            ->name('branch.checkout.calculate-tax');

        // Get delivery options via AJAX
        Route::post('/delivery-options', [CheckoutMerchantController::class, 'getDeliveryOptions'])
            ->name('branch.checkout.delivery-options');

        // Calculate totals preview (AJAX)
        Route::post('/preview-totals', [CheckoutMerchantController::class, 'previewTotals'])
            ->name('branch.checkout.preview-totals');

        // Discount code management
        Route::get('/discount/check', [CheckoutMerchantController::class, 'checkDiscountCode'])
            ->name('branch.checkout.discount.check');

        Route::post('/discount/apply', [CheckoutMerchantController::class, 'applyDiscountCode'])
            ->name('branch.checkout.discount.apply');

        Route::post('/discount/remove', [CheckoutMerchantController::class, 'removeDiscountCode'])
            ->name('branch.checkout.discount.remove');

        // ====================================================================
        // PAYMENT GATEWAY ROUTES - Process Payment
        // ====================================================================

        // Cash on Delivery
        Route::post('/payment/cod', [CodPaymentController::class, 'processPayment'])
            ->name('branch.payment.cod.process');

        // Manual/Bank Transfer
        Route::post('/payment/manual', [ManualPaymentController::class, 'processPayment'])
            ->name('branch.payment.manual.process');

        // Stripe
        Route::post('/payment/stripe', [StripePaymentController::class, 'processPayment'])
            ->name('branch.payment.stripe.process');

        // PayPal
        Route::post('/payment/paypal', [PaypalPaymentController::class, 'processPayment'])
            ->name('branch.payment.paypal.process');

        // Razorpay (INR Only)
        Route::post('/payment/razorpay', [RazorpayPaymentController::class, 'processPayment'])
            ->name('branch.payment.razorpay.process');

        // MyFatoorah (Middle East)
        Route::post('/payment/myfatoorah', [MyFatoorahPaymentController::class, 'processPayment'])
            ->name('branch.payment.myfatoorah.process');

        // Instamojo (INR Only)
        Route::post('/payment/instamojo', [InstamojoPaymentController::class, 'processPayment'])
            ->name('branch.payment.instamojo.process');

        // Paytm (INR Only)
        Route::post('/payment/paytm', [PaytmPaymentController::class, 'processPayment'])
            ->name('branch.payment.paytm.process');

        // Mollie (Europe)
        Route::post('/payment/mollie', [MolliePaymentController::class, 'processPayment'])
            ->name('branch.payment.mollie.process');

        // Paystack (Africa - Inline Payment)
        Route::post('/payment/paystack', [PaystackPaymentController::class, 'processPayment'])
            ->name('branch.payment.paystack.process');

        Route::get('/payment/paystack/config', [PaystackPaymentController::class, 'getConfig'])
            ->name('branch.payment.paystack.config');

        // Flutterwave (Africa)
        Route::post('/payment/flutterwave', [FlutterwavePaymentController::class, 'processPayment'])
            ->name('branch.payment.flutterwave.process');

        // MercadoPago (Latin America)
        Route::post('/payment/mercadopago', [MercadopagoPaymentController::class, 'processPayment'])
            ->name('branch.payment.mercadopago.process');

        Route::get('/payment/mercadopago/config', [MercadopagoPaymentController::class, 'getConfig'])
            ->name('branch.payment.mercadopago.config');

        // VoguePay (Nigeria - Inline Payment)
        Route::post('/payment/voguepay', [VoguepayPaymentController::class, 'processPayment'])
            ->name('branch.payment.voguepay.process');

        Route::get('/payment/voguepay/config', [VoguepayPaymentController::class, 'getConfig'])
            ->name('branch.payment.voguepay.config');

        // SSL Commerz (Bangladesh)
        Route::post('/payment/sslcommerz', [SslCommerzPaymentController::class, 'processPayment'])
            ->name('branch.payment.sslcommerz.process');

        // Authorize.net (USA)
        Route::post('/payment/authorize', [AuthorizeNetPaymentController::class, 'processPayment'])
            ->name('branch.payment.authorize.process');
    });

/*
|--------------------------------------------------------------------------
| Legacy Merchant Checkout Routes (Redirects to Branch)
|--------------------------------------------------------------------------
|
| Keep old merchant routes for backwards compatibility.
| These redirect to new branch routes when accessed.
|
*/

Route::prefix('merchant/{merchantId}/checkout')
    ->middleware(['web', 'preserve.session'])
    ->group(function () {

        // Redirect to branch checkout if accessed
        Route::get('/address', function (int $merchantId) {
            return redirect()->route('merchant-cart.index')
                ->with('info', __('Please select a branch to checkout'));
        })->name('merchant.checkout.address');

        Route::get('/shipping', function (int $merchantId) {
            return redirect()->route('merchant-cart.index');
        })->name('merchant.checkout.shipping');

        Route::get('/payment', function (int $merchantId) {
            return redirect()->route('merchant-cart.index');
        })->name('merchant.checkout.payment');

        Route::get('/return/{status?}', function (int $merchantId) {
            return redirect()->route('merchant-cart.index');
        })->name('merchant.checkout.return');
    });

/*
|--------------------------------------------------------------------------
| Payment Callback Routes (No Branch ID in URL)
|--------------------------------------------------------------------------
|
| These routes handle payment gateway callbacks that don't include
| branch_id in the URL. The branch_id is passed via query parameter
| or stored in session.
|
*/

Route::prefix('branch/payment')
    ->middleware(['web', 'preserve.session'])
    ->group(function () {

        // Stripe Callback
        Route::get('/stripe/callback', [StripePaymentController::class, 'handleCallback'])
            ->name('branch.payment.stripe.callback');

        // PayPal Callback
        Route::get('/paypal/callback', [PaypalPaymentController::class, 'handleCallback'])
            ->name('branch.payment.paypal.callback');

        // Razorpay Callback
        Route::post('/razorpay/callback', [RazorpayPaymentController::class, 'handleCallback'])
            ->name('branch.payment.razorpay.callback');

        // MyFatoorah Callback
        Route::get('/myfatoorah/callback', [MyFatoorahPaymentController::class, 'handleCallback'])
            ->name('branch.payment.myfatoorah.callback');

        // Instamojo Callback
        Route::get('/instamojo/callback', [InstamojoPaymentController::class, 'handleCallback'])
            ->name('branch.payment.instamojo.callback');

        // Paytm Callback
        Route::post('/paytm/callback', [PaytmPaymentController::class, 'handleCallback'])
            ->name('branch.payment.paytm.callback');

        // Mollie Callback
        Route::get('/mollie/callback', [MolliePaymentController::class, 'handleCallback'])
            ->name('branch.payment.mollie.callback');

        // Flutterwave Callback
        Route::get('/flutterwave/callback', [FlutterwavePaymentController::class, 'handleCallback'])
            ->name('branch.payment.flutterwave.callback');

        // SSL Commerz Callback
        Route::post('/sslcommerz/callback', [SslCommerzPaymentController::class, 'handleCallback'])
            ->name('branch.payment.sslcommerz.callback');
    });

/*
|--------------------------------------------------------------------------
| Legacy Payment Callback Routes (Keep for existing payments)
|--------------------------------------------------------------------------
*/

Route::prefix('merchant/payment')
    ->middleware(['web', 'preserve.session'])
    ->group(function () {

        // Stripe Callback
        Route::get('/stripe/callback', [StripePaymentController::class, 'handleCallback'])
            ->name('merchant.payment.stripe.callback');

        // PayPal Callback
        Route::get('/paypal/callback', [PaypalPaymentController::class, 'handleCallback'])
            ->name('merchant.payment.paypal.callback');

        // Razorpay Callback
        Route::post('/razorpay/callback', [RazorpayPaymentController::class, 'handleCallback'])
            ->name('merchant.payment.razorpay.callback');

        // MyFatoorah Callback
        Route::get('/myfatoorah/callback', [MyFatoorahPaymentController::class, 'handleCallback'])
            ->name('merchant.payment.myfatoorah.callback');

        // Instamojo Callback
        Route::get('/instamojo/callback', [InstamojoPaymentController::class, 'handleCallback'])
            ->name('merchant.payment.instamojo.callback');

        // Paytm Callback
        Route::post('/paytm/callback', [PaytmPaymentController::class, 'handleCallback'])
            ->name('merchant.payment.paytm.callback');

        // Mollie Callback
        Route::get('/mollie/callback', [MolliePaymentController::class, 'handleCallback'])
            ->name('merchant.payment.mollie.callback');

        // Flutterwave Callback
        Route::get('/flutterwave/callback', [FlutterwavePaymentController::class, 'handleCallback'])
            ->name('merchant.payment.flutterwave.callback');

        // SSL Commerz Callback
        Route::post('/sslcommerz/callback', [SslCommerzPaymentController::class, 'handleCallback'])
            ->name('merchant.payment.sslcommerz.callback');
    });
