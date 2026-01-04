<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Muaadhsetting;
use App\Models\Purchase;
use App\Models\MerchantPayment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class StripeController extends Controller
{

    public function __construct()
    {

        $data = MerchantPayment::whereKeyword('stripe')->first();
        $paydata = $data->convertAutoData();
        Config::set('services.stripe.key', $paydata['key']);
        Config::set('services.stripe.secret', $paydata['secret']);
    }

    public function store(Request $request)
    {

        if ($request->has('purchase_number')) {
            $purchase_number = $request->purchase_number;
            $purchase = Purchase::where('purchase_number', $purchase_number)->firstOrFail();
            $curr = Currency::where('sign', '=', $purchase->currency_sign)->firstOrFail();
            if ($curr->name != "USD") {
                return redirect()->back()->with('unsuccess', 'Please Select USD Currency For Stripe.');
            }

            $item_amount = $purchase->pay_amount * $purchase->currency_value;
            $gs = Muaadhsetting::findOrFail(1);

            try {
                $stripe_secret_key = Config::get('services.stripe.secret');
                \Stripe\Stripe::setApiKey($stripe_secret_key);
                $checkout_session = \Stripe\Checkout\Session::create([
                    "mode" => "payment",
                    "success_url" => route('payment.notify') . '?session_id={CHECKOUT_SESSION_ID}',
                    "cancel_url" => route('front.payment.cancle'),
                    "locale" => "auto",
                    "line_items" => [
                        [
                            "quantity" => 1,
                            "price_data" => [
                                "currency" => $purchase->currency_name,
                                "unit_amount" => $item_amount * 100,
                                "product_data" => [
                                    "name" => $gs->title . 'Payment'
                                ]
                            ]
                        ],
                    ]
                ]);

                Session::put('purchase_number', $purchase_number);
                return redirect($checkout_session->url);
            } catch (Exception $e) {
                return back()->with('unsuccess', $e->getMessage());
            }
        }
        return response()->json(['status' => false, 'data' => [], 'error' => 'Invalid Request']);
    }


    public function notify(Request $request)
    {
        $purchase_number = Session::get('purchase_number');
        $stripe = new \Stripe\StripeClient(Config::get('services.stripe.secret'));
        $response = $stripe->checkout->sessions->retrieve($request->session_id);
        $purchase = Purchase::where('purchase_number', $purchase_number)->firstOrFail();

        if ($response->status == 'complete') {
            $purchase->method = "Stripe";
            $purchase->txnid = $response->payment_intent;
            $purchase->payment_status = 'Completed';
            $purchase->save();
            return redirect(route('front.payment.success', 1));
        } else {
            return redirect(route('front.payment.cancle'));
        }
    }
}
