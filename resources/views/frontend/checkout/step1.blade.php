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
                <div class="col-lg-6 wow fadeInUp" data-wow-delay=".1s">
                    <div class="checkout-step-wrapper">
                        <span class="line"></span>
                        <span class="line-2 d-none"></span>
                        <span class="line-3 d-none"></span>
                        <div class="single-step active">
                            <span class="step-btn">1</span>
                            <span class="step-txt">@lang('Address')</span>
                        </div>
                        <div class="single-step">
                            <span class="step-btn">2</span>
                            <span class="step-txt">@lang('Details')</span>
                        </div>
                        <div class="single-step">
                            <span class="step-btn">3</span>
                            <span class="step-txt">@lang('Payment')</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- address-->
            <form class="address-wrapper" action="{{ route('front.checkout.step1.submit') }}" method="POST">
                @csrf
                <div class="row gy-4">
                    <div class="col-lg-7 col-xl-8 wow fadeInUp" data-wow-delay=".2s">
                        <!-- personal information -->
                        <div class="mb-40">
                            <h4 class="form-title">@lang('Personal Information')</h4>
                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="name">@lang('Name')</label>
                                        <input class="input-cls" id="name" name="personal_name"
                                            value="{{ Auth::check() ? Auth::user()->name : '' }}" type="text"
                                            placeholder="@lang('Enter Your Name')" {{ Auth::check() ? 'readonly' : '' }}>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="email">@lang('Email')</label>
                                        <input class="input-cls" id="email" type="email" name="personal_email"
                                            placeholder="@lang('Enter Your Emai')l"
                                            value="{{ Auth::check() ? Auth::user()->email : '' }}"
                                            {{ Auth::check() ? 'readonly' : '' }}>
                                    </div>
                                </div>




                                @if (!Auth::check())
                                    <div class="col-lg-12">
                                        <div class="gs-checkbox-wrapper" data-bs-toggle="collapse"
                                            data-bs-target="#show_passwords" aria-expanded="false"
                                            aria-controls="show_passwords" role="region">
                                            <input type="checkbox" id="showca">
                                            <label class="icon-label" for="showca">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                    viewBox="0 0 12 12" fill="none">
                                                    <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666"
                                                        stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </label>
                                            <label for="showca">@lang('Create an account ?')</label>
                                        </div>
                                    </div>
                                    <div class="col-12 collapse" id="show_passwords">
                                        <div class="row gy-4">
                                            <div class="col-lg-6">
                                                <div class="input-wrapper">
                                                    <label class="label-cls" for="crpass">
                                                        @lang('Create Password')
                                                    </label>
                                                    <input class="input-cls" id="crpass" type="password"
                                                        placeholder="@lang('Create Your Password')">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="input-wrapper">
                                                    <label class="label-cls" for="conpass">
                                                        @lang('Confirm Password')
                                                    </label>
                                                    <input class="input-cls" id="conpass" type="password"
                                                        placeholder="@lang('Confirm Password')">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>

                        <!-- Billing Details -->
                        <div class="mb-40">
                            <h4 class="form-title">@lang('Billing Details')</h4>
                            <div class="row g-4">
                                <div class="col-lg-6 {{ $digital == 1 ? 'd-none' : '' }}">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="Shipping">@lang('Shipping')</label>
                                        <select class="input-cls nice-select" id="shipop" name="shipping" required="">
                                            <option value="shipto">{{ __('Ship To Address') }}</option>
                                            <option value="pickup">{{ __('Pick Up') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-6 d-none" id="shipshow">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="Shipping">@lang('Shipping')</label>
                                        <select class="input-cls" name="pickup_location">
                                            @foreach ($pickups as $pickup)
                                                <option value="{{ $pickup->location }}">
                                                    {{ $pickup->location }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>


                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="customer_name">@lang('Name')</label>
                                        <input class="input-cls" id="customer_name" type="text" name="customer_name"
                                            placeholder="@lang('Full Name')"
                                            value="{{ Auth::check() ? Auth::user()->name : '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="customer_email">@lang('Email')</label>
                                        <input class="input-cls" id="customer_email" type="text"
                                            name="customer_email" placeholder="@lang('Your Email')"
                                            value="{{ Auth::check() ? Auth::user()->email : '' }}">
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="phone">
                                            @lang('Phone Number')
                                        </label>
                                        <input class="input-cls" id="phone" type="tel"
                                            placeholder="@lang('Phone Number')" name="customer_phone"
                                            value="{{ Auth::check() ? Auth::user()->phone : '' }}">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="address">
                                            @lang('Address')
                                        </label>
                                        <input class="input-cls" id="address" type="text"
                                            placeholder="@lang('Address')" name="customer_address"
                                            value="{{ Auth::check() ? Auth::user()->address : '' }}">
                                    </div>
                                </div>


                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="zip">
                                            @lang('Postal Code')
                                        </label>
                                        <input class="input-cls" id="zip" type="text"
                                            placeholder="@lang('Postal Code')" name="customer_zip"
                                            value="{{ Auth::check() ? Auth::user()->zip : '' }}">
                                    </div>
                                </div>


                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls">@lang('Select Country')</label>
                                        <select class="nice-select" id="select_country" name="customer_country" required>
                                            @include('includes.countries')
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-6 d-none select_state">
                                    <div class="input-wrapper">
                                        <label class="label-cls">@lang('Select State')</label>
                                        <select class="nice-select" id="show_state" name="customer_state" required>

                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-6 d-none">
                                    <div class="input-wrapper">
                                        <label class="label-cls">@lang('Select City')</label>
                                        <select class="nice-select " id="show_city" name="customer_city" required>

                                        </select>
                                    </div>
                                </div>


                                <!-- chekbox -->
                                <div class="col-lg-12  {{ $digital == 1 ? 'd-none' : '' }}" id="ship_deff">
                                    <div class="gs-checkbox-wrapper" data-bs-toggle="collapse"
                                        data-bs-target="#show_shipping_address" role="region" aria-expanded="false"
                                        aria-controls="show_shipping_address">
                                        <input type="checkbox" id="shpto" name="is_shipping" value="1">
                                        <label class="icon-label" for="shpto">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                viewBox="0 0 12 12" fill="none">
                                                <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </label>
                                        <label for="shpto">@lang('Ship to a Different Address?')</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div class="collapse" id="show_shipping_address">
                            <h4 class="form-title">@lang('Shipping Address')</h4>
                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="shipping_name">
                                            @lang('Name')
                                        </label>
                                        <input class="input-cls" id="shipping_name" type="text"
                                            placeholder="@lang('Full Name')" name="shipping_name">
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="shipping_phone">
                                            @lang('Phone Number')
                                        </label>
                                        <input class="input-cls" id="shipping_phone" name="shipping_phone"
                                            type="tel" placeholder="@lang('Phone Number')">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="shipping_address">
                                            @lang('Address')
                                        </label>
                                        <input class="input-cls" id="shipping_address" name="shipping_address"
                                            type="text" placeholder="@lang('Address')">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="shipping_zip">
                                            @lang('Postal Code')
                                        </label>
                                        <input class="input-cls" id="shipping_zip" name="shipping_zip" type="text"
                                            placeholder="@lang('Postal Code')">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="shipping_city">@lang('City')</label>
                                        <input class="input-cls" id="shipping_city" name="shipping_city" type="text"
                                            placeholder="@lang('City')">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="shipping_state">@lang('State')</label>
                                        <input class="input-cls" id="shipping_state" name="shipping_state"
                                            type="text" placeholder="@lang('State')">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls">@lang('Select Country')</label>
                                        <select class="nice-select" name="shipping_country">
                                            @include('partials.user.countries')
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="Order-Note">
                                            @lang('Order Note')
                                        </label>
                                        <input class="input-cls" id="Order-Note" name="order_notes" type="text"
                                            placeholder="@lang('Order note (Optional)')">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 col-xl-4 wow fadeInUp" data-wow-delay=".2s">
                        <div class="summary-box">
                            <h4 class="form-title">@lang('Summery')</h4>


                            <!-- Price Details -->
                            <div class="summary-inner-box">
                                <h6 class="summary-title">@lang('Price Details')</h6>
                                <div class="details-wrapper">
                                    <div class="price-details">
                                        <span>@lang('Total MRP')</span>
                                        <span
                                            class="right-side cart-total">{{ Session::has('cart') ? App\Models\Product::convertPrice(Session::get('cart')->totalPrice) : '0.00' }}</span>
                                    </div>


                                    <div class="price-details tax_show d-none">
                                        <span>@lang('Tax')</span>
                                        <span class="right-side original_tax original_tax">0</span>
                                    </div>


                                    @if (Session::has('coupon'))
                                        <div class="price-details">
                                            <span>@lang('Discount') <span
                                                    class="dpercent">{{ Session::get('coupon_percentage') == 0 ? '' : '(' . Session::get('coupon_percentage') . ')' }}</span></span>
                                            @if ($gs->currency_format == 0)
                                                <span id="discount"
                                                    class="right-side">{{ $curr->sign }}{{ Session::get('coupon') }}</span>
                                            @else
                                                <span id="discount"
                                                    class="right-side">{{ Session::get('coupon') }}{{ $curr->sign }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <div class="price-details d-none">
                                            <span>@lang('Discount') <span class="dpercent"></span></span>
                                            <span id="discount"
                                                class="right-side">{{ $curr->sign }}{{ Session::get('coupon') }}</span>
                                        </div>
                                    @endif


                                    @if ($digital == 0)
                                        <div class="price-details">
                                            <span>@lang('Shipping Cost')</span>
                                            <span
                                                class="right-side shipping_cost_view">{{ App\Models\Product::convertPrice(0) }}</span>
                                        </div>

                                        <div class="price-details">
                                            <span>@lang('Packaging Cost')</span>
                                            <span
                                                class="right-side packing_cost_view">{{ App\Models\Product::convertPrice(0) }}</span>
                                        </div>
                                    @endif
                                </div>

                                <hr>
                                <div class="final-price">


                                    <span>@lang('Final Price')</span>
                                    @if (Session::has('coupon_total'))
                                        @if ($gs->currency_format == 0)
                                            <span class="total-amount"
                                                id="final-cost">{{ $curr->sign }}{{ $totalPrice }}</span>
                                        @else
                                            <span class="total-amount"
                                                id="final-cost">{{ $totalPrice }}{{ $curr->sign }}</span>
                                        @endif
                                    @elseif(Session::has('coupon_total1'))
                                        <span class="total-amount" id="final-cost">
                                            {{ Session::get('coupon_total1') }}</span>
                                    @else
                                        <span class="total-amount"
                                            id="final-cost">{{ App\Models\Product::convertPrice($totalPrice) }}</span>
                                    @endif
                                </div>
                            </div>

                            <!-- btn wrapper -->
                            <div class="summary-inner-box">
                                <div class="btn-wrappers">
                                    <button type="submit" href="#" class="template-btn w-100">
                                        @lang('Continue')
                                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24"
                                            viewBox="0 0 25 24" fill="none">
                                            <g clip-path="url(#clip0_489_34176)">
                                                <path
                                                    d="M23.62 9.9099L19.75 5.9999C19.657 5.90617 19.5464 5.83178 19.4246 5.78101C19.3027 5.73024 19.172 5.7041 19.04 5.7041C18.908 5.7041 18.7773 5.73024 18.6554 5.78101C18.5336 5.83178 18.423 5.90617 18.33 5.9999C18.1437 6.18726 18.0392 6.44071 18.0392 6.7049C18.0392 6.96909 18.1437 7.22254 18.33 7.4099L21.89 10.9999H1.5C1.23478 10.9999 0.98043 11.1053 0.792893 11.2928C0.605357 11.4803 0.5 11.7347 0.5 11.9999H0.5C0.5 12.2651 0.605357 12.5195 0.792893 12.707C0.98043 12.8945 1.23478 12.9999 1.5 12.9999H21.95L18.33 16.6099C18.2363 16.7029 18.1619 16.8135 18.1111 16.9353C18.0603 17.0572 18.0342 17.1879 18.0342 17.3199C18.0342 17.4519 18.0603 17.5826 18.1111 17.7045C18.1619 17.8263 18.2363 17.9369 18.33 18.0299C18.423 18.1236 18.5336 18.198 18.6554 18.2488C18.7773 18.2996 18.908 18.3257 19.04 18.3257C19.172 18.3257 19.3027 18.2996 19.4246 18.2488C19.5464 18.198 19.657 18.1236 19.75 18.0299L23.62 14.1499C24.1818 13.5874 24.4974 12.8249 24.4974 12.0299C24.4974 11.2349 24.1818 10.4724 23.62 9.9099Z"
                                                    fill="white" />
                                            </g>
                                            <defs>
                                                <clipPath id="clip0_489_34176">
                                                    <rect width="24" height="24" fill="white"
                                                        transform="translate(0.5)" />
                                                </clipPath>
                                            </defs>
                                        </svg>
                                    </button>
                                    <a href="{{ route('front.checkout') }}" class="template-btn dark-outline w-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24"
                                            viewBox="0 0 25 24" fill="none">
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
                                        @lang('Back to Previous Step')
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
                @php
                @endphp
                @if (Session::has('coupon_total'))
                    <input type="hidden" name="total" id="grandtotal"
                        value="{{ round($totalPrice * $curr->value, 2) }}">
                    <input type="hidden" id="tgrandtotal" value="{{ $totalPrice }}">
                @elseif(Session::has('coupon_total1'))
                    <input type="hidden" name="total" id="grandtotal"
                        value="{{ preg_replace(' /[^0-9,.]/', '', Session::get('coupon_total1')) }}">
                    <input type="hidden" id="tgrandtotal"
                        value="{{ preg_replace(' /[^0-9,.]/', '', Session::get('coupon_total1')) }}">
                @else
                    <input type="hidden" name="total" id="grandtotal"
                        value="{{ round($totalPrice * $curr->value, 2) }}">
                    <input type="hidden" id="tgrandtotal" value="{{ round($totalPrice * $curr->value, 2) }}">
                @endif
                <input type="hidden" id="original_tax" value="0">
                <input type="hidden" id="wallet-price" name="wallet_price" value="0">
                <input type="hidden" id="ttotal"
                    value="{{ Session::has('cart') ? App\Models\Product::convertPrice(Session::get('cart')->totalPrice) : '0' }}">
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
    <!--  checkout wrapper end-->
@endsection


@section('script')
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script src="https://www.2checkout.com/checkout/api/2co.min.js"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript">
        $('a.payment:first').addClass('active');

        $('.checkoutform').attr('action', $('a.payment:first').attr('data-form'));
        $($('a.payment:first').attr('href')).load($('a.payment:first').data('href'));


        var show = $('a.payment:first').data('show');
        if (show != 'no') {
            $('.pay-area').removeClass('d-none');
        } else {
            $('.pay-area').addClass('d-none');
        }
        $($('a.payment:first').attr('href')).addClass('active').addClass('show');
    </script>
    <script type="text/javascript">
        var coup = 0;
        var pos = {{ $gs->currency_format }};


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

        $(document).on('change', '#select_country', function() {
            $('#show_state').niceSelect("destroy"); //update the plugin
            $(this).attr('data-href');
            let state_id = 0;
            let country_id = $('#select_country option:selected').attr('data');
            let is_state = $('option:selected', this).attr('rel');
            let is_auth = $('option:selected', this).attr('rel1');
            let is_user = $('option:selected', this).attr('rel5');
            let state_url = $('option:selected', this).attr('data-href');


            if (is_auth == 1 || is_state == 1) {
                if (is_state == 1) {
                    $('.select_state').removeClass('d-none');
                    $.get(state_url, function(response) {

                        $('#show_state').html(response.data);
                        if (is_user == 1) {
                            tax_submit(country_id, response.state);
                        } else {
                            tax_submit(country_id, state_id);
                        }
                        $('#show_state').niceSelect();
                    });

                } else {
                    tax_submit(country_id, state_id);
                    hide_state();
                }

            } else {
                tax_submit(country_id, state_id);
                hide_state();
            }


        });


        $(document).on('change', '#show_state', function() {
            $('#show_city').niceSelect("destroy");
            let state_id = $(this).val();
            let country_id = $('#select_country option:selected').attr('data');

            $.get("{{ route('state.wise.city') }}", {
                state_id: state_id
            }, function(data) {
                $('#show_city').parent().parent().removeClass('d-none');

                $('#show_city').html(data.data);
                $('#show_city').niceSelect();
            });
            tax_submit(country_id, state_id);
        });


        function hide_state() {
            $('.select_state').addClass('d-none');
        }


        $(document).ready(function() {
    
            $('#show_state').niceSelect("destroy");
            let country_id = $('#select_country option:selected').attr('data');
            let state_id = $('#select_country option:selected').attr('rel2');
            let is_state = $('#select_country option:selected', this).attr('rel');
            let is_auth = $('#select_country option:selected', this).attr('rel1');
            let state_url = $('#select_country option:selected', this).attr('data-href');

            if (is_auth == 1 && is_state == 1) {
                if (is_state == 1) {
                    $('.select_state').removeClass('d-none');
                    $.get(state_url, function(response) {
                        $(".nice-select").niceSelect("update");
                        $('#show_state').html(response.data);
                        tax_submit(country_id, response.state);
                    });

                } else {
                    tax_submit(country_id, state_id);
                    hide_state();
                }
            } else {
                tax_submit(country_id, state_id);
                hide_state();
            }
        });


        function tax_submit(country_id, state_id) {

            $('.gocover').show();
            var total = $("#ttotal").val();
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
                success: function(data) {

                    $('#grandtotal').val(data[0]);
                    $('#tgrandtotal').val(data[0]);
                    $('#original_tax').val(data[1]);
                    $('.tax_show').removeClass('d-none');
                    $('#input_tax').val(data[11]);
                    $('#input_tax_type').val(data[12]);
                    $('.original_tax').html(parseFloat(data[1]) + "%");
                    var ttotal = parseFloat($('#grandtotal').val());
                    var tttotal = parseFloat($('#grandtotal').val()) + (parseFloat(mship) + parseFloat(mpack));
                    ttotal = parseFloat(ttotal).toFixed(2);
                    tttotal = parseFloat(tttotal).toFixed(2);
                    $('#grandtotal').val(data[0] + parseFloat(mship) + parseFloat(mpack));
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


        $('#shipop').on('change', function() {

            var val = $(this).val();
            if (val == 'pickup') {
                $('#shipshow').removeClass('d-none');
                $('.show_shipping_address').addClass('d-none');

            } else {
                $('#shipshow').addClass('d-none');
                $('#show_shipping_address').removeClass('d-none');
            }

        });


        $("#shpto").on("change", function() {
            if (this.checked) {
                $('#show_shipping_address input, #show_shipping_address select').prop('required', true);
            } else {
                $('#show_shipping_address input, #show_shipping_address select').prop('required', false);
            }
            $('#show_shipping_address input[name="order_notes"]').prop('required', false);

        });
    </script>
@endsection
