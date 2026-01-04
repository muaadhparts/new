<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantPayment extends Model
{
    protected $table = 'merchant_payments';

    protected $fillable = ['title', 'details', 'subtitle', 'name', 'type', 'information', 'currency_id'];

    public $timestamps = false;

    public function currency()
    {
        return $this->belongsTo('App\Models\Currency')->withDefault();
    }

    public static function scopeHasGateway($curr)
    {
        return MerchantPayment::where('currency_id', 'like', "%\"{$curr}\"%")->orwhere('currency_id', '*')->get();
    }

    public function convertAutoData()
    {
        return $this->information ? json_decode($this->information, true) : [];
    }

    public function getAutoDataText()
    {
        $text = $this->convertAutoData();
        return end($text);
    }

    public function showKeyword()
    {
        $data = $this->keyword == null ? 'other' : $this->keyword;
        return $data;
    }

    /**
     * Get checkout link for merchant-specific payment route
     *
     * POLICY: ALL payment routes require merchant_id in the route.
     * This method returns merchant-specific routes when merchant_id is provided.
     *
     * @param int|null $merchantId The merchant ID for merchant-specific routes
     * @return string The route URL
     */
    public function showCheckoutLink($merchantId = null)
    {
        $data = $this->keyword == null ? 'other' : $this->keyword;

        // ====================================================================
        // MERCHANT-SPECIFIC ROUTES (New Policy)
        // ====================================================================
        if ($merchantId) {
            $routeMap = [
                'myfatoorah'    => 'front.checkout.merchant.myfatoorah.submit',
                'paypal'        => 'front.checkout.merchant.paypal.submit',
                'stripe'        => 'front.checkout.merchant.stripe.submit',
                'instamojo'     => 'front.checkout.merchant.instamojo.submit',
                'paystack'      => 'front.checkout.merchant.paystack.submit',
                'paytm'         => 'front.checkout.merchant.paytm.submit',
                'mollie'        => 'front.checkout.merchant.mollie.submit',
                'razorpay'      => 'front.checkout.merchant.razorpay.submit',
                'authorize.net' => 'front.checkout.merchant.authorize.submit',
                'mercadopago'   => 'front.checkout.merchant.mercadopago.submit',
                'flutterwave'   => 'front.checkout.merchant.flutterwave.submit',
                'sslcommerz'    => 'front.checkout.merchant.ssl.submit',
                'voguepay'      => 'front.checkout.merchant.voguepay.submit',
                'cod'           => 'front.checkout.merchant.cod.submit',
                'wallet'        => 'front.checkout.merchant.wallet.submit',
            ];

            $routeName = $routeMap[$data] ?? 'front.checkout.merchant.manual.submit';
            return route($routeName, ['merchantId' => $merchantId]);
        }

        // ====================================================================
        // LEGACY ROUTES (Will redirect to cart with error)
        // These are kept for backward compatibility but will fail
        // ====================================================================
        $link = '';
        if ($data == 'myfatoorah') {
            $link = route('front.myfatoorah.submit');
        } else if ($data == 'paypal') {
            $link = route('front.paypal.submit');
        } else if ($data == 'stripe') {
            $link = route('front.stripe.submit');
        } else if ($data == 'instamojo') {
            $link = route('front.instamojo.submit');
        } else if ($data == 'paystack') {
            $link = route('front.paystack.submit');
        } else if ($data == 'paytm') {
            $link = route('front.paytm.submit');
        } else if ($data == 'mollie') {
            $link = route('front.molly.submit');
        } else if ($data == 'razorpay') {
            $link = route('front.razorpay.submit');
        } else if ($data == 'authorize.net') {
            $link = route('front.authorize.submit');
        } else if ($data == 'mercadopago') {
            $link = route('front.mercadopago.submit');
        } else if ($data == 'flutterwave') {
            $link = route('front.flutter.submit');
        } else if ($data == '2checkout') {
            $link = route('front.twocheckout.submit');
        } else if ($data == 'sslcommerz') {
            $link = route('front.ssl.submit');
        } else if ($data == 'voguepay') {
            $link = route('front.voguepay.submit');
        } else if ($data == 'cod') {
            $link = route('front.cod.submit');
        } else {
            $link = route('front.manual.submit');
        }

        return $link;
    }

    public function showSubscriptionLink()
    {
        $link = '';
        $data = $this->keyword;
        if ($data == 'paypal') {
            $link = route('user.paypal.submit');
        } else if ($data == 'stripe') {
            $link = route('user.stripe.submit');
        } else if ($data == 'instamojo') {
            $link = route('user.instamojo.submit');
        } else if ($data == 'paystack') {
            $link = route('user.paystack.submit');
        } else if ($data == 'paytm') {
            $link = route('user.paytm.submit');
        } else if ($data == 'mollie') {
            $link = route('user.molly.submit');
        } else if ($data == 'razorpay') {
            $link = route('user.razorpay.submit');
        } else if ($data == 'authorize.net') {
            $link = route('user.authorize.submit');
        } else if ($data == 'mercadopago') {
            $link = route('user.mercadopago.submit');
        } else if ($data == 'flutterwave') {
            $link = route('user.flutter.submit');
        } else if ($data == '2checkout') {
            $link = route('user.twocheckout.submit');
        } else if ($data == 'sslcommerz') {
            $link = route('user.ssl.submit');
        } else if ($data == 'voguepay') {
            $link = route('user.voguepay.submit');
        } else if ($data == null) {
            $link = route('user.manual.submit');
        }
        return $link;
    }

    public function showTopUpLink()
    {
        $link = '';
        $data = $this->keyword;
        if ($data == 'paypal') {
            $link = route('topup.paypal.submit');
        } else if ($data == 'stripe') {
            $link = route('topup.stripe.submit');
        } else if ($data == 'instamojo') {
            $link = route('topup.instamojo.submit');
        } else if ($data == 'paystack') {
            $link = route('topup.paystack.submit');
        } else if ($data == 'paytm') {
            $link = route('topup.paytm.submit');
        } else if ($data == 'mollie') {
            $link = route('topup.molly.submit');
        } else if ($data == 'razorpay') {
            $link = route('topup.razorpay.submit');
        } else if ($data == 'authorize.net') {
            $link = route('topup.authorize.submit');
        } else if ($data == 'mercadopago') {
            $link = route('topup.mercadopago.submit');
        } else if ($data == 'flutterwave') {
            $link = route('topup.flutter.submit');
        } else if ($data == '2checkout') {
            $link = route('topup.twocheckout.submit');
        } else if ($data == 'sslcommerz') {
            $link = route('topup.ssl.submit');
        } else if ($data == 'voguepay') {
            $link = route('topup.voguepay.submit');
        } else if ($data == null) {
            $link = route('topup.manual.submit');
        }
        return $link;
    }


    public function showForm()
    {
        $show = '';
        $data = $this->keyword == null ? 'other' : $this->keyword;
        $values = ['myfatoorah', 'cod', 'voguepay', 'sslcommerz', 'flutterwave', 'razorpay', 'mollie', 'paytm', 'paystack', 'paypal', 'instamojo', 'stripe'];
        if (in_array($data, $values)) {
            $show = 'no';
        } else {
            $show = 'yes';
        }
        return $show;
    }


    // API DATA
    public function showApiCheckoutLink()
    {
        $link = '';
        $data = $this->keyword == null ? 'other' : $this->keyword;
        if ($data == 'paypal') {
            $link = route('api.paypal.submit');
        } else if ($data == 'stripe') {
            $link = route('api.stripe.submit');
        } else if ($data == 'instamojo') {
            $link = route('api.instamojo.submit');
        } else if ($data == 'paystack') {
            $link = route('api.paystack.submit');
        } else if ($data == 'paytm') {
            $link = route('api.paytm.submit');
        } else if ($data == 'mollie') {
            $link = route('api.molly.submit');
        } else if ($data == 'razorpay') {
            $link = route('api.razorpay.submit');
        } else if ($data == 'authorize.net') {
            $link = route('api.authorize.submit');
        } else if ($data == 'mercadopago') {
            $link = route('api.mercadopago.submit');
        } else if ($data == 'flutterwave') {
            $link = route('api.flutter.submit');
        } else if ($data == '2checkout') {
            $link = route('api.twocheckout.submit');
        } else if ($data == 'sslcommerz') {
            $link = route('api.ssl.submit');
        } else if ($data == 'voguepay') {
            $link = route('api.voguepay.submit');
        } else if ($data == 'cod') {
            $link = route('api.cod.submit');
        } else {
            $link = route('api.manual.submit');
        }
        return $link;
    }


    public function ApiShowTopUpLink()
    {
        $link = '';
        $data = $this->keyword;
        if ($data == 'paypal') {
            $link = route('api.user.topup.paypal.submit');
        } else if ($data == 'stripe') {
            $link = route('api.user.topup.stripe.submit');
        } else if ($data == 'instamojo') {
            $link = route('api.user.topup.instamojo.submit');
        } else if ($data == 'paystack') {
            $link = route('api.user.topup.paystack.submit');
        } else if ($data == 'paytm') {
            $link = route('api.user.topup.paytm.submit');
        } else if ($data == 'mollie') {
            $link = route('api.user.topup.molly.submit');
        } else if ($data == 'razorpay') {
            $link = route('api.user.topup.razorpay.submit');
        } else if ($data == 'authorize.net') {
            $link = route('api.user.topup.authorize.submit');
        } else if ($data == 'mercadopago') {
            $link = route('api.user.topup.mercadopago.submit');
        } else if ($data == 'flutterwave') {
            $link = route('api.user.topup.flutter.submit');
        } else if ($data == '2checkout') {
            $link = route('api.user.topup.twocheckout.submit');
        } else if ($data == 'sslcommerz') {
            $link = route('api.user.topup.ssl.submit');
        } else if ($data == 'voguepay') {
            $link = route('api.user.topup.voguepay.submit');
        } else if ($data == null) {
            $link = route('api.user.topup.manual.submit');
        }
        return $link;
    }
}
