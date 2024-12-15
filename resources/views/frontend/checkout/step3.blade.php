@extends('layouts.front')
@section('content')
<section class="gs-breadcrumb-section bg-class"
    data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
    <div class="container">
        <div class="row justify-content-center content-wrapper">
            <div class="col-12">
                <h2 class="breadcrumb-title">@lang('Checkout')</h2>
                <ul class="bread-menu">
                    <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                    <li><a href="{{ route('front.checkout') }}">@lang('Checkout')</a></li>
                </ul>
            </div>
        </div>
    </div>
</section>


<div class="gs-checkout-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="checkout-step-wrapper wow-replaced" data-wow-delay=".1s">
                    <span class=" line"></span>
                    <span class="line-2"></span>
                    <span class="line-3 d-none"></span>
                    <div class="single-step active">
                        <span class="step-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none">
                                <path d="M20 6L9 17L4 12" stroke="white" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="step-txt">@lang('Address')</span>
                    </div>
                    <div class="single-step active">
                        <span class="step-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none">
                                <path d="M20 6L9 17L4 12" stroke="white" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="step-txt">@lang('Details')</span>
                    </div>
                    <div class="single-step active">
                        <span class="step-btn">3</span>
                        <span class="step-txt">@lang('Payment')</span>
                    </div>
                </div>
            </div>
        </div>
        <!-- address-->
        <form class="address-wrapper checkoutform" method="POST">
            @csrf
            <div class="row gy-4">
                <div class="col-lg-7 col-xl-8 wow-replaced" data-wow-delay=".1s">
                    <div class="select-payment-list-wrapper">
                        <h5 class="title">@lang('Select payment Method')</h5>

                        <div class="list-wrapper">
                            @foreach ($gateways as $gt)
                            @if ($gt->checkout == 1)
                            @if ($gt->type == 'manual')
                            @if ($digital == 0)
                            <!-- single payment input -->
                            <div class="gs-radio-wrapper payment" data-show="{{ $gt->showForm() }}"
                                data-form="{{ $gt->showCheckoutLink() }}"
                                data-href="{{ route('front.load.payment', ['slug1' => $gt->showKeyword(), 'slug2' => $gt->id]) }}">
                                <input type="radio" id="pl{{ $gt->id }}" name="payment_1">
                                <label class="icon-label" for="pl{{ $gt->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                                        fill="none">
                                        <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" fill="#FDFDFD" />
                                        <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" stroke="#EE1243" />
                                        <circle cx="10" cy="10" r="4" fill="#EE1243" />
                                    </svg>
                                </label>
                                <label class="label-wrapper" for="pl{{ $gt->id }}">
                                    <span class="label-title">{{ $gt->title }}</span>
                                    <span class="label-subtitle">{{ $gt->subtitle }}</span>
                                </label>
                            </div>
                            @endif
                            @else
                            <div class="gs-radio-wrapper payment" data-val="{{ $gt->keyword }}"
                                data-show="{{ $gt->showForm() }}" data-form="{{ $gt->showCheckoutLink() }}"
                                data-href="{{ route('front.load.payment', ['slug1' => $gt->showKeyword(), 'slug2' => $gt->id]) }}">
                                <input type="radio" id="pl{{ $gt->id }}" name="payment_1">
                                <label class="icon-label" for="pl{{ $gt->id }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"
                                        fill="none">
                                        <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" fill="#FDFDFD" />
                                        <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" stroke="#EE1243" />
                                        <circle cx="10" cy="10" r="4" fill="#EE1243" />
                                    </svg>
                                </label>
                                <label class="label-wrapper" for="pl{{ $gt->id }}">
                                    <span class="label-title"> {{ $gt->name }}</span>
                                    @if ($gt->information != null)
                                    <span class="label-subtitle">{{ $gt->getAutoDataText() }}</span>
                                    @endif
                                </label>
                            </div>
                            @endif
                            @endif
                            @endforeach
                        </div>

                        <div class="transection-wrapper pay-area">


                        </div>
                    </div>



                    <!-- form  start-->
                    {{-- <div class="mt-40">
                        <h4 class="form-title">Billing Details</h4>
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="input-wrapper">
                                    <label class="label-cls" for="Shipping">Shipping</label>
                                    <input class="input-cls" id="Shipping" type="text" placeholder="Ship To Address">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-wrapper">
                                    <label class="label-cls" for="shipping-name">Name</label>
                                    <input class="input-cls" id="shipping-name" type="text" placeholder="Full Name">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-wrapper">
                                    <label class="label-cls" for="shipping-email">Email</label>
                                    <input class="input-cls" id="shipping-email" type="email" placeholder="Enter Email">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="input-wrapper">
                                    <label class="label-cls">Select Country </label>
                                    <select class="nice-select">
                                        <option value="aus">Austratlia</option>
                                        <option value="ban">Bangladesh</option>
                                        <option value="can">Canada</option>
                                        <option value="den">Denmark</option>
                                    </select>
                                </div>
                            </div>
                            <!-- chekbox -->
                            <div class="col-lg-12">
                                <div class="gs-checkbox-wrapper">
                                    <input type="checkbox" id="shpto">
                                    <label class="icon-label" for="shpto">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                            viewBox="0 0 12 12" fill="none">
                                            <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </label>
                                    <label for="shpto">are you agree??</label>
                                </div>
                            </div>
                        </div>
                    </div> --}}
                    <!-- form  end-->
                </div>
                <div class="col-lg-5 col-xl-4 wow-replaced" data-wow-delay=".2s">
                    <div class="summary-box">
                        <h4 class="form-title">@lang('Summery')</h4>

                        <!-- Apply Coupon Code -->
                        <div class="summary-inner-box">
                            <h6 class="summary-title">@lang('Apply Coupon Code')</h6>
                            <div class="coupon-wrapper">
                                <input type="text" id="code" placeholder="@lang('Coupon Code')">
                                <button type="submit" id="check_coupon">@lang('Apply')</button>
                            </div>
                        </div>

                        <!-- Price Details -->
                        <div class="summary-inner-box">
                            <h6 class="summary-title">@lang('Price Details')</h6>
                            <div class="details-wrapper">
                                <div class="price-details">
                                    <span>@lang('Total MRP')</span>
                                    <span class="right-side cart-total">{{ Session::has('cart') ?
                                        App\Models\Product::convertPrice(Session::get('cart')->totalPrice) : '0.00'
                                        }}</span>
                                </div>


                                <div class="price-details tax_show d-none">
                                    <span>@lang('Tax')</span>
                                    <span class="right-side original_tax original_tax">0</span>
                                </div>




                                @if (Session::has('coupon'))
                                <div class="price-details discount-bar">
                                    <span>@lang('Discount') <span class="dpercent">{{ Session::get('coupon_percentage')
                                            == 0 ? '' : '(' . Session::get('coupon_percentage') . ')' }}</span></span>
                                    @if ($gs->currency_format == 0)
                                    <span id="discount" class="right-side">{{ $curr->sign }}{{ Session::get('coupon')
                                        }}</span>
                                    @else
                                    <span id="discount" class="right-side">{{ Session::get('coupon') }}{{ $curr->sign
                                        }}</span>
                                    @endif
                                </div>
                                @else
                                <div class="price-details d-none discount-bar">
                                    <span>@lang('Discount') <span class="dpercent"></span></span>
                                    <span id="discount" class="right-side">{{ $curr->sign }}{{ Session::get('coupon')
                                        }}</span>
                                </div>
                                @endif


                                @php

                                $shipping_cost = 0;
                                if (isset($step2->shipping) && is_array($step2->shipping)) {
                                foreach ($step2->shipping as $key => $value) {
                                $shpping = App\Models\Shipping::findOrFail($value);
                                $shipping_cost += $shpping->price;
                                }
                                }

                                $packing_cost = 0;
                                if (isset($step2->packeging) && is_array($step2->packeging)) {
                                foreach ($step2->packeging as $key => $value) {
                                $shpping = App\Models\Package::findOrFail($value);
                                $packing_cost += $shpping->price;
                                }
                                }

                                $extra_cost = $shipping_cost + $packing_cost;

                                @endphp


                                @if ($digital == 0)
                                @php

                                if (isset($step2->shipping_id)) {
                                $shipping_cost = App\Models\Shipping::findOrFail($step2->shipping_id)
                                ->price;
                                }

                                if (isset($step2->packaging_id)) {
                                $packing_cost = App\Models\Package::findOrFail($step2->packaging_id)
                                ->price;
                                }

                                @endphp

                                <div class="price-details">
                                    <span>@lang('Shipping Cost')</span>
                                    <span class="right-side">{{ App\Models\Product::convertPrice($shipping_cost)
                                        }}</span>
                                </div>


                                <div class="price-details">
                                    <span>@lang('Packaging Cost')</span>
                                    <span class="right-side packing_cost_view">{{
                                        App\Models\Product::convertPrice($packing_cost) }}</span>
                                </div>
                                @endif
                            </div>

                            <hr>
                            <div class="final-price">
                                <span>@lang('Final Price')</span>
                                @if (Session::has('coupon_total'))
                                @if ($gs->currency_format == 0)
                                <span class="total-amount">{{ $curr->sign }}{{ $step2->total }}</span>
                                @else
                                <span class="total-amount">{{ $step2->total }}{{ $curr->sign }}</span>
                                @endif
                                @elseif(Session::has('coupon_total1'))
                                <span class="total-amount">
                                    {{ Session::get('coupon_total1') }}</span>
                                @else
                                <span class="total-amount">{{ App\Models\Product::convertPrice($step2->total) }}</span>
                                @endif
                            </div>
                        </div>


                        <!-- btn wrapper -->
                        <div class="summary-inner-box">
                            <div class="btn-wrappers">
                                <button type="submit" class="template-btn w-100">
                                    @lang('Continue')
                                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24"
                                        fill="none">
                                        <g clip-path="url(#clip0_489_34176)">
                                            <path
                                                d="M23.62 9.9099L19.75 5.9999C19.657 5.90617 19.5464 5.83178 19.4246 5.78101C19.3027 5.73024 19.172 5.7041 19.04 5.7041C18.908 5.7041 18.7773 5.73024 18.6554 5.78101C18.5336 5.83178 18.423 5.90617 18.33 5.9999C18.1437 6.18726 18.0392 6.44071 18.0392 6.7049C18.0392 6.96909 18.1437 7.22254 18.33 7.4099L21.89 10.9999H1.5C1.23478 10.9999 0.98043 11.1053 0.792893 11.2928C0.605357 11.4803 0.5 11.7347 0.5 11.9999H0.5C0.5 12.2651 0.605357 12.5195 0.792893 12.707C0.98043 12.8945 1.23478 12.9999 1.5 12.9999H21.95L18.33 16.6099C18.2363 16.7029 18.1619 16.8135 18.1111 16.9353C18.0603 17.0572 18.0342 17.1879 18.0342 17.3199C18.0342 17.4519 18.0603 17.5826 18.1111 17.7045C18.1619 17.8263 18.2363 17.9369 18.33 18.0299C18.423 18.1236 18.5336 18.198 18.6554 18.2488C18.7773 18.2996 18.908 18.3257 19.04 18.3257C19.172 18.3257 19.3027 18.2996 19.4246 18.2488C19.5464 18.198 19.657 18.1236 19.75 18.0299L23.62 14.1499C24.1818 13.5874 24.4974 12.8249 24.4974 12.0299C24.4974 11.2349 24.1818 10.4724 23.62 9.9099Z"
                                                fill="white" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_489_34176">
                                                <rect width="24" height="24" fill="white" transform="translate(0.5)" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </button>
                                <a href="{{ route('front.checkout.step2') }}" class="template-btn dark-outline w-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24"
                                        fill="none">
                                        <g clip-path="url(#clip0_489_34179)">
                                            <path
                                                d="M1.38 9.9099L5.25 5.9999C5.34296 5.90617 5.45357 5.83178 5.57542 5.78101C5.69728 5.73024 5.82799 5.7041 5.96 5.7041C6.09201 5.7041 6.22272 5.73024 6.34458 5.78101C6.46643 5.83178 6.57704 5.90617 6.67 5.9999C6.85625 6.18726 6.96079 6.44071 6.96079 6.7049C6.96079 6.96909 6.85625 7.22254 6.67 7.4099L3.11 10.9999H23.5C23.7652 10.9999 24.0196 11.1053 24.2071 11.2928C24.3946 11.4803 24.5 11.7347 24.5 11.9999V11.9999C24.5 12.2651 24.3946 12.5195 24.2071 12.707C24.0196 12.8945 23.7652 12.9999 23.5 12.9999H3.05L6.67 16.6099C6.76373 16.7029 6.83812 16.8135 6.88889 16.9353C6.93966 17.0572 6.9658 17.1879 6.9658 17.3199C6.9658 17.4519 6.93966 17.5826 6.88889 17.7045C6.83812 17.8263 6.76373 17.9369 6.67 18.0299C6.57704 18.1236 6.46643 18.198 6.34458 18.2488C6.22272 18.2996 6.09201 18.3257 5.96 18.3257C5.82799 18.3257 5.69728 18.2996 5.57542 18.2488C5.45357 18.198 5.34296 18.1236 5.25 18.0299L1.38 14.1499C0.818197 13.5874 0.50264 12.8249 0.50264 12.0299C0.50264 11.2349 0.818197 10.4724 1.38 9.9099Z"
                                                fill="#030712" />
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_489_34179">
                                                <rect width="24" height="24" fill="white"
                                                    transform="matrix(-1 0 0 1 24.5 0)" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                    @lang('Back to Details')
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>




            <input type="hidden" name="dp" value="{{ $digital }}">
            <input type="hidden" id="input_tax" name="tax" value="">
            <input type="hidden" id="input_tax_type" name="tax_type" value="">
            <input type="hidden" name="totalQty" value="{{ $totalQty }}">
            <input type="hidden" name="vendor_shipping_id" value="{{ $vendor_shipping_id }}">
            <input type="hidden" name="vendor_packing_id" value="{{ $vendor_packing_id }}">
            <input type="hidden" name="currency_sign" value="{{ $curr->sign }}">
            <input type="hidden" name="currency_name" value="{{ $curr->name }}">
            <input type="hidden" name="currency_value" value="{{ $curr->value }}">



            @if (Session::has('coupon_total'))
            <input type="hidden" name="total" id="grandtotal" value="{{ round($totalPrice * $curr->value, 2) }}">
            <input type="hidden" id="tgrandtotal" value="{{ $totalPrice }}">
            @elseif(Session::has('coupon_total1'))
            <input type="hidden" name="total" id="grandtotal"
                value="{{ preg_replace(' /[^0-9,.]/', '', Session::get('coupon_total1')) }}">
            <input type="hidden" id="tgrandtotal"
                value="{{ preg_replace(' /[^0-9,.]/', '', Session::get('coupon_total1')) }}">
            @else
            <input type="hidden" name="total" id="grandtotal" value="{{ round($step2->total * $curr->value, 2) }}">
            <input type="hidden" id="tgrandtotal" value="{{ round($totalPrice * $curr->value, 2) }}">
            @endif



            <input type="hidden" id="original_tax" value="0">
            <input type="hidden" id="wallet-price" name="wallet_price" value="0">
            <input type="hidden" id="ttotal" value="{{ $step2->total }}">
            <input type="hidden" name="coupon_code" id="coupon_code"
                value="{{ Session::has('coupon_code') ? Session::get('coupon_code') : '' }}">
            <input type="hidden" name="coupon_discount" id="coupon_discount"
                value="{{ Session::has('coupon') ? Session::get('coupon') : '' }}">
            <input type="hidden" name="coupon_id" id="coupon_id"
                value="{{ Session::has('coupon') ? Session::get('coupon_id') : '' }}">
            <input type="hidden" name="user_id" id="user_id"
                value="{{ Auth::guard('web')->check() ? Auth::guard('web')->user()->id : '' }}">

        </form>
    </div>
</div>


@php
$country = App\Models\Country::where('country_name', $step1->customer_country)->first();
$isState = isset($step1->customer_state) ? $step1->customer_state : 0;
@endphp
<input type="hidden" id="select_country" name="country_id" value="{{ $country->id }}">
<input type="hidden" id="state_id" name="state_id"
    value="{{ isset($step1->customer_state) ? $step1->customer_state : 0 }}">
<input type="hidden" id="is_state" name="is_state" value="{{ $isState }}">
<input type="hidden" id="state_url" name="state_url" value=" {{ route('country.wise.state', $country->id) }}">


@endsection



@section('script')
<script src="https://js.paystack.co/v1/inline.js"></script>
<script src="https://sdk.mercadopago.com/js/v2"></script>
<script src="https://js.stripe.com/v3/"></script>




<script type="text/javascript">
    // under input field
    $('.payment:first').children('input').prop('checked', true);
    $('.checkoutform').attr('action', $('.payment:first').attr('data-form'));
    $(".pay-area").load($('.payment:first').data('href'));

    var show = $('.payment:first').data('show');
    if (show != 'no') {
        $('.pay-area').removeClass('d-none');
    } else {
        $('.pay-area').addClass('d-none');
    }
</script>





<script type="text/javascript">
    var coup = 0;
    var pos = {{ $gs-> currency_format }};
    let mship = 0;
    let mpack = 0;


    var ftotal = parseFloat($('#grandtotal').val());
    ftotal = parseFloat(ftotal).toFixed(2);

    if (pos == 0) {
        $('#final-cost').html('{{ $curr->sign }}' + ftotal)
    } else {
        $('#final-cost').html(ftotal + '{{ $curr->sign }}')
    }
    $('#grandtotal').val(ftotal);

    let original_tax = 0;

    $(document).ready(function () {
        let country_id = $('#select_country').val();
        let state_id = $('#state_id').val();
        let is_state = $('#is_state').val();
        let state_url = $('#state_url').val();


        if (is_state == 1) {
            if (is_state == 1) {
                $('.select_state').removeClass('d-none');
                $.get(state_url, function (response) {
                    $('#show_state').html(response.data);
                    tax_submit(country_id, response.state);
                });

            } else {
                tax_submit(country_id, state_id);
            }
        } else {
            tax_submit(country_id, state_id);
        }
    });


    function tax_submit(country_id, state_id) {

        $('.gocover').show();
        var total = $("#tgrandtotal").val();
        var ship = 0;
        $.ajax({
            type: "GET",
            url: mainurl + "/country/tax/check",

            data: {
                state_id: state_id,
                country_id: country_id,
                total: total,
                shipping_cost: ship
            },
            success: function (data) {


                $('.tax_show').removeClass('d-none');
                $('#input_tax').val(data[11]);
                $('#input_tax_type').val(data[12]);
                $('.original_tax').html(parseFloat(data[1]) + "%");
                var ttotal = parseFloat($('#grandtotal').val());
                var tttotal = parseFloat($('#grandtotal').val()) + (parseFloat(mship) + parseFloat(mpack));
                ttotal = parseFloat(ttotal).toFixed(2);
                tttotal = parseFloat(tttotal).toFixed(2);
                if (pos == 0) {
                    $('#final-cost').html('{{ $curr->sign }}' + tttotal);
                    $('.total-cost-dum #total-cost').html('{{ $curr->sign }}' + ttotal);
                } else {
                    $('#total-cost').html('');
                    $('#final-cost').html(tttotal + '{{ $curr->sign }}');
                    $('.total-cost-dum #total-cost').html(ttotal + '{{ $curr->sign }}');
                }
                $('.gocover').hide();
            }
        });
    }



    $(document).on("click", "#check_coupon", function () {
        var val = $("#code").val();
        var total = $("#ttotal").val();
        var ship = 0;
        $.ajax({
            type: "GET",
            url: mainurl + "/carts/coupon/check",
            data: {
                code: val,
                total: total,
                shipping_cost: ship
            },
            success: function (data) {
                if (data == 0) {
                    toastr.error('{{ __('Coupon not found') }}');
                    $("#code").val("");
                } else if (data == 2) {
                    toastr.error('{{ __('Coupon already have been taken') }}');
                    $("#code").val("");
                } else {
                    $("#check-coupon-form").toggle();
                    $(".discount-bar").removeClass('d-none');

                    if (pos == 0) {
                        $('.total-cost-dum #total-cost').html('{{ $curr->sign }}' + data[0]);
                        $('#discount').html('{{ $curr->sign }}' + data[2]);
                    } else {
                        $('.total-cost-dum #total-cost').html(data[0]);
                        $('#discount').html(data[2] + '{{ $curr->sign }}');
                    }
                    $("#coupon_id").val(data[3]);
                    $('#grandtotal').val(data[0]);
                    $('#tgrandtotal').val(data[0]);
                    $('#coupon_code').val(data[1]);
                    $('#coupon_discount').val(data[2]);
                    if (data[4] != 0) {
                        $('.dpercent').html('(' + data[4] + ')');
                    } else {
                        $('.dpercent').html('');
                    }


                    var ttotal = data[6] + parseFloat(mship) + parseFloat(mpack);
                    ttotal = parseFloat(ttotal);
                    if (ttotal % 1 != 0) {
                        ttotal = ttotal.toFixed(2);
                    }

                    if (pos == 0) {
                        $('.total-amount').html('{{ $curr->sign }}' + ttotal)
                    } else {
                        $('.total-amount').html(ttotal + '{{ $curr->sign }}')
                    }
                    toastr.success(lang.coupon_found);
                    $("#code").val("");
                }
            }
        });
        return false;
    });




    $('.payment').on('click', function () {

        if ($(this).data('val') == 'paystack') {
            $('.checkoutform').attr('id', 'step1-form');
        } else if ($(this).data('val') == 'mercadopago') {
            $('.checkoutform').attr('id', 'mercadopago');
            checkONE = 1;
        } else {
            $('.checkoutform').attr('id', '');
        }
        $('.checkoutform').attr('action', $(this).attr('data-form'));
        $('.payment').removeClass('active');

        var show = $(this).attr('data-show');
        if (show != 'no') {
            $('.pay-area').removeClass('d-none');
        } else {
            $('.pay-area').addClass('d-none');
        }
        $($('#v-pills-tabContent .tap-pane').removeClass('active show'));
        $(".pay-area").addClass('active show').load($(this).attr(
            'data-href'));
    })
</script>
@endsection