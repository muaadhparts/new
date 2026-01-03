@extends('layouts.front')
@section('content')
    <section class="gs-breadcrumb-section">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Checkout')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('front.cart') }}">@lang('Cart')</a></li>
                        <li>@lang('Checkout')</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>



    <div class="gs-checkout-wrapper muaadh-section-gray">
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
            <form class="address-wrapper" action="{{ isset($is_merchant_checkout) && $is_merchant_checkout ? route('front.checkout.merchant.step1.submit', $merchant_id) : route('front.checkout.step1.submit') }}" method="POST">
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
                                            <input type="checkbox" id="showca" name="create_account" value="1">
                                            <label class="icon-label" for="showca">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                    viewBox="0 0 12 12" fill="none">
                                                    <path d="M10 3L4.5 8.5L2 6" stroke="currentColor" stroke-width="1.6666"
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
                                                    <input class="input-cls" id="crpass" name="password" type="password"
                                                        placeholder="@lang('Create Your Password')" minlength="6">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="input-wrapper">
                                                    <label class="label-cls" for="conpass">
                                                        @lang('Confirm Password')
                                                    </label>
                                                    <input class="input-cls" id="conpass" name="password_confirmation" type="password"
                                                        placeholder="@lang('Confirm Password')" minlength="6">
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


                                {{-- Hidden dropdowns (kept for potential future use) --}}
                                {{-- Hidden dropdowns - name removed, using hidden fields instead --}}
                                <div class="col-lg-6 d-none">
                                    <div class="input-wrapper">
                                        <label class="label-cls">@lang('Select Country')</label>
                                        <select class="nice-select" id="select_country">
                                            @include('includes.countries')
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-6 d-none select_state">
                                    <div class="input-wrapper">
                                        <label class="label-cls">@lang('Select State')</label>
                                        <select class="nice-select" id="show_state">

                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-6 d-none">
                                    <div class="input-wrapper">
                                        <label class="label-cls">@lang('Select City')</label>
                                        {{-- name removed - using hidden field customer_city_hidden instead --}}
                                        <select class="nice-select " id="show_city">

                                        </select>
                                    </div>
                                </div>

                                <!-- Google Maps Location Picker - Simple Version -->
                                <div class="col-lg-12">
                                    <div class="m-alert m-alert--info d-flex align-items-center" role="alert">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        <strong>@lang('Please select your delivery location from the map')</strong>
                                    </div>
                                    <button type="button" class="m-btn m-btn--primary m-btn--outline w-100 mb-3" id="open-map-btn" data-bs-toggle="modal" data-bs-target="#mapModal">
                                        <i class="fas fa-map-marker-alt"></i> @lang('Select Location from Map')
                                    </button>
                                    <div id="selected-location-info" class="m-alert m-alert--success d-none">
                                        <i class="fas fa-check-circle"></i> <span id="location-text"></span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5 col-xl-4 wow fadeInUp" data-wow-delay=".2s">
                        <div class="summary-box">
                            <h4 class="form-title">@lang('Summery')</h4>

                            {{-- ✅ Unified Price Summary Component - Step 1 --}}
                            @include('includes.checkout-price-summary', [
                                'step' => 1,
                                'catalogItemsTotal' => $catalogItemsTotal ?? $totalPrice,
                                'totalPrice' => $totalPrice, // Backward compatibility
                                'digital' => $digital,
                                'curr' => $curr,
                                'gs' => $gs
                            ])

                            <!-- btn wrapper -->
                            <div class="summary-inner-box">
                                <div class="btn-wrappers">
                                    <button type="submit" class="template-btn w-100">
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
                                    <a href="{{ route('front.cart') }}" class="template-btn dark-outline w-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24"
                                            viewBox="0 0 25 24" fill="none">
                                            <g clip-path="url(#clip0_489_34179)">
                                                <path
                                                    d="M1.38 9.9099L5.25 5.9999C5.34296 5.90617 5.45357 5.83178 5.57542 5.78101C5.69728 5.73024 5.82799 5.7041 5.96 5.7041C6.09201 5.7041 6.22272 5.73024 6.34458 5.78101C6.46643 5.83178 6.57704 5.90617 6.67 5.9999C6.85625 6.18726 6.96079 6.44071 6.96079 6.7049C6.96079 6.96909 6.85625 7.22254 6.67 7.4099L3.11 10.9999H23.5C23.7652 10.9999 24.0196 11.1053 24.2071 11.2928C24.3946 11.4803 24.5 11.7347 24.5 11.9999V11.9999C24.5 12.2651 24.3946 12.5195 24.2071 12.707C24.0196 12.8945 23.7652 12.9999 23.5 12.9999H3.05L6.67 16.6099C6.76373 16.7029 6.83812 16.8135 6.88889 16.9353C6.93966 17.0572 6.9658 17.1879 6.9658 17.3199C6.9658 17.4519 6.93966 17.5826 6.88889 17.7045C6.83812 17.8263 6.76373 17.9369 6.67 18.0299C6.57704 18.1236 6.46643 18.198 6.34458 18.2488C6.22272 18.2996 6.09201 18.3257 5.96 18.3257C5.82799 18.3257 5.69728 18.2996 5.57542 18.2488C5.45357 18.198 5.34296 18.1236 5.25 18.0299L1.38 14.1499C0.818197 13.5874 0.50264 12.8249 0.50264 12.0299C0.50264 11.2349 0.818197 10.4724 1.38 9.9099Z"
                                                    fill="currentColor" />
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






                {{-- Location Data from Map (Primary Source) --}}
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="country_id" id="country_id">
                <input type="hidden" name="city_id" id="city_id">
                {{-- Hidden fields for backend - these are the primary source --}}
                <input type="hidden" name="customer_city" id="customer_city_hidden">
                <input type="hidden" name="customer_country" id="customer_country_hidden">
                <input type="hidden" name="customer_state" id="customer_state_hidden">
                {{-- City/Country/State names for shipping resolution (Step 2) --}}
                <input type="hidden" name="city_name" id="city_name_hidden">
                <input type="hidden" name="country_name" id="country_name_hidden">
                <input type="hidden" name="state_name" id="state_name_hidden">

                <input type="hidden" name="dp" value="{{ $digital }}">
                <input type="hidden" id="input_tax" name="tax" value="">
                <input type="hidden" id="input_tax_type" name="tax_type" value="">
                <input type="hidden" name="totalQty" value="{{ $totalQty }}">
                <input type="hidden" name="merchant_shipping_id" value="{{ $merchant_shipping_id }}">
                <input type="hidden" name="merchant_packing_id" value="{{ $merchant_packing_id }}">
                <input type="hidden" name="currency_sign" value="{{ $curr->sign }}">
                <input type="hidden" name="currency_name" value="{{ $curr->name }}">
                <input type="hidden" name="currency_value" value="{{ $curr->value }}">
                @php
                @endphp
                @if (Session::has('discount_total'))
                    <input type="hidden" name="total" id="grandtotal"
                        value="{{ round($totalPrice * $curr->value, 2) }}">
                    <input type="hidden" id="tgrandtotal" value="{{ $totalPrice }}">
                @elseif(Session::has('discount_total1'))
                    <input type="hidden" name="total" id="grandtotal"
                        value="{{ preg_replace(' /[^0-9,.]/', '', Session::get('discount_total1')) }}">
                    <input type="hidden" id="tgrandtotal"
                        value="{{ preg_replace(' /[^0-9,.]/', '', Session::get('discount_total1')) }}">
                @else
                    <input type="hidden" name="total" id="grandtotal"
                        value="{{ round($totalPrice * $curr->value, 2) }}">
                    <input type="hidden" id="tgrandtotal" value="{{ round($totalPrice * $curr->value, 2) }}">
                @endif
                <input type="hidden" id="original_tax" value="0">
                <input type="hidden" id="wallet-price" name="wallet_price" value="0">
                {{-- ttotal must be numeric (no currency sign) for tax calculations --}}
                <input type="hidden" id="ttotal"
                    value="{{ round($totalPrice * $curr->value, 2) }}">
                <input type="hidden" name="discount_code" id="discount_code"
                    value="{{ Session::has('discount_code_value') ? Session::get('discount_code_value') : '' }}">
                <input type="hidden" name="discount_amount" id="discount_amount"
                    value="{{ Session::has('discount_code') ? Session::get('discount_code') : '' }}">
                <input type="hidden" name="discount_code_id" id="discount_code_id"
                    value="{{ Session::has('discount_code') ? Session::get('discount_code_id') : '' }}">
                <input type="hidden" name="user_id" id="user_id"
                    value="{{ Auth::guard('web')->check() ? Auth::guard('web')->user()->id : '' }}">









            </form>
        </div>
    </div>
    <!--  checkout wrapper end-->

    {{-- Google Maps Modal with Search --}}
    <div class="modal fade" id="mapModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: var(--radius-lg, 12px); overflow: hidden;">
                <div class="modal-header" style="background: var(--action-primary); color: var(--text-on-primary, #fff); border: none;">
                    <h5 class="modal-title">
                        <i class="fas fa-map-marker-alt me-2"></i>
                        @lang('Select your location')
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    {{-- Search Box --}}
                    <div class="p-3" style="background: var(--bg-secondary); border-bottom: 1px solid var(--border-default);">
                        <div class="input-group">
                            <span class="input-group-text" style="background: var(--bg-primary); border-color: var(--border-default);">
                                <i class="fas fa-search" style="color: var(--text-muted);"></i>
                            </span>
                            <input type="text" id="map-search-input" class="form-control"
                                   style="border-color: var(--border-default);"
                                   placeholder="@lang('Search for a location...')" autocomplete="off">
                        </div>
                    </div>

                    {{-- Map Container --}}
                    <div id="map" style="height: 350px; width: 100%;"></div>

                    {{-- Location Display --}}
                    <div class="p-3" style="background: var(--bg-secondary); border-top: 1px solid var(--border-default);">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div class="flex-grow-1">
                                <small class="d-block mb-1" style="color: var(--text-muted);">@lang('Selected Location'):</small>
                                <div id="coords-display" class="fw-bold" style="word-break: break-word; color: var(--text-primary);">
                                    @lang('Click on map or search to select location')
                                </div>
                            </div>
                            <button type="button" class="m-btn m-btn--secondary m-btn--sm flex-shrink-0" id="my-location-btn">
                                <i class="fas fa-crosshairs"></i> @lang('My Location')
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: var(--bg-primary); border-top: 1px solid var(--border-default);">
                    <button type="button" class="m-btn m-btn--secondary" data-bs-dismiss="modal">@lang('Close')</button>
                    <button type="button" class="m-btn m-btn--primary" id="confirm-location-btn" disabled>
                        <i class="fas fa-check me-1"></i> @lang('Confirm Location')
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('script')
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script src="https://www.2checkout.com/checkout/api/2co.min.js"></script>
    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript">
        // Only run payment-related code if payment elements exist (Step 3)
        if ($('a.payment').length > 0) {
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
        }
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

        // ⚠️ DISABLED - Dropdowns are hidden, no longer trigger state/city loading
        // Tax calculation will be handled via hidden fields from map selection
        /*
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
        */


        function hide_state() {
            $('.select_state').addClass('d-none');
        }


        // ⚠️ DISABLED - Country/state initialization on page load (no longer needed)
        /*
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
        */


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
                    // data[0] = total with tax
                    // data[1] = tax percentage
                    // data[2] = tax amount
                    // data[3] = tax location (country/state name)

                    $('#grandtotal').val(data[0]);
                    $('#tgrandtotal').val(data[0]);
                    $('#original_tax').val(data[1]);
                    $('#input_tax').val(data[11]);
                    $('#input_tax_type').val(data[12]);

                    // Show tax display with rate and amount
                    if (data[1] && parseFloat(data[1]) > 0) {
                        $('.tax-display-wrapper').removeClass('d-none');
                        $('.tax-rate-text').html('(' + parseFloat(data[1]) + '%)');

                        // Display tax amount with currency
                        var taxAmount = parseFloat(data[2] || 0);
                        if (pos == 0) {
                            $('.tax-amount-value').html('{{ $curr->sign }}' + taxAmount.toFixed(2));
                        } else {
                            $('.tax-amount-value').html(taxAmount.toFixed(2) + '{{ $curr->sign }}');
                        }

                        // Show tax location if available
                        if (data[3]) {
                            $('.tax-location-wrapper').removeClass('d-none');
                            $('.tax-location-text').html(data[3]);
                        } else {
                            $('.tax-location-wrapper').addClass('d-none');
                        }
                    } else {
                        $('.tax-display-wrapper').addClass('d-none');
                        $('.tax-location-wrapper').addClass('d-none');
                    }

                    // Update final total
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

                    // Update PriceSummary component
                    if (typeof PriceSummary !== 'undefined') {
                        var taxRate = parseFloat(data[1]) || 0;
                        var taxAmount = parseFloat(data[2]) || 0;
                        PriceSummary.updateTax(taxRate, taxAmount);
                    }

                    $('.gocover').hide();
                }
            });
        }


        $('#shipop').on('change', function() {
            var val = $(this).val();
            if (val == 'pickup') {
                $('#shipshow').removeClass('d-none');
            } else {
                $('#shipshow').addClass('d-none');
            }
        });
    </script>

    {{-- Modal & Google Places fixes --}}
    <style>
        /* Google Places Autocomplete z-index fix */
        .pac-container {
            z-index: 10000 !important;
        }

        /* Modal - No transparency */
        #mapModal .modal-content {
            background: var(--bg-primary, #fff) !important;
            opacity: 1 !important;
        }

        #mapModal .modal-header {
            background: var(--action-primary) !important;
            opacity: 1 !important;
        }

        #mapModal .modal-body {
            background: var(--bg-primary, #fff) !important;
            opacity: 1 !important;
        }

        #mapModal .modal-footer {
            background: var(--bg-primary, #fff) !important;
            opacity: 1 !important;
        }

        /* Dark backdrop */
        .modal-backdrop.show {
            opacity: 0.7 !important;
            background: var(--overlay-backdrop, #000) !important;
        }
    </style>

    {{-- POLICY: Google Maps loads ONLY if API key exists in api_credentials table --}}
    @if(!empty($googleMapsApiKey))
    <script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&libraries=places&language=ar"></script>
    @else
        @php \Log::warning('Google Maps: API key not configured in api_credentials table - Checkout map disabled'); @endphp
    @endif
    <script>
    // Check if Google Maps is available
    const googleMapsAvailable = typeof google !== 'undefined' && typeof google.maps !== 'undefined';

    // Map Variables
    let map, marker, searchBox, geocoder;
    let selectedLat = null, selectedLng = null;
    let selectedAddress = '';

    // Checkout type detection
    const isMerchantCheckout = {{ isset($is_merchant_checkout) && $is_merchant_checkout ? 'true' : 'false' }};
    const checkoutMerchantId = {{ isset($merchant_id) ? $merchant_id : 'null' }};

    // Clear previous session and reset when modal opens
    $('#mapModal').on('show.bs.modal', function() {
        // Clear location_draft in backend (doesn't affect step1/step2/step3)
        @if(isset($merchant_id) && $merchant_id)
        $.ajax({
            url: '{{ route("front.checkout.merchant.location.reset", ["merchantId" => $merchant_id]) }}',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            }
        });
        @endif

        // Clear previous location data (client-side)
        selectedLat = null;
        selectedLng = null;
        selectedAddress = '';

        // Clear hidden fields
        $('#latitude').val('');
        $('#longitude').val('');
        $('#country_id').val('');
        $('#city_id').val('');
        $('#customer_city_hidden').val('');
        $('#customer_country_hidden').val('');
        $('#customer_state_hidden').val('');
        $('#city_name_hidden').val('');
        $('#country_name_hidden').val('');
        $('#state_name_hidden').val('');

        // Reset UI
        $('#open-map-btn').removeClass('m-btn--success').addClass('m-btn--primary m-btn--outline');
        $('#open-map-btn').html('<i class="fas fa-map-marker-alt"></i> @lang("Select Location from Map")');
        $('#selected-location-info').addClass('d-none');
        $('#location-text').text('');

        // Reset confirm button
        document.getElementById('confirm-location-btn').disabled = true;

        // Reset display
        document.getElementById('coords-display').innerHTML = '@lang("Click on map or search to select location")';

        // Clear search input
        $('#map-search-input').val('');
    });

    // Default center (Saudi Arabia)
    const defaultCenter = { lat: 24.7136, lng: 46.6753 };
    const defaultZoom = 6;

    // Initialize map when modal is shown
    $('#mapModal').on('shown.bs.modal', function() {
        if (!googleMapsAvailable) {
            document.getElementById('coords-display').innerHTML = '<span class="text-danger">@lang("Map service unavailable. Please enter address manually.")</span>';
            console.error('Google Maps API not loaded - API key may be missing from api_credentials table');
            return;
        }
        if (!map) {
            initMap();
        } else {
            google.maps.event.trigger(map, 'resize');
            resetMapState();
        }
    });

    // Reset map to clean state (no auto-detect)
    function resetMapState() {
        // Hide and reset marker
        if (marker) {
            marker.setVisible(false);
            marker.setPosition(defaultCenter);
        }

        // Reset map view
        map.setCenter(defaultCenter);
        map.setZoom(defaultZoom);

        // Clear selected location
        selectedLat = null;
        selectedLng = null;
        selectedAddress = '';

        // Reset UI
        document.getElementById('confirm-location-btn').disabled = true;
        document.getElementById('coords-display').innerHTML = '@lang("Search for your address or click Use My Location")';
    }

    function initMap() {
        // Check if Google Maps is available
        if (!googleMapsAvailable) {
            console.error('initMap called but Google Maps is not available');
            return;
        }

        // Initialize geocoder
        geocoder = new google.maps.Geocoder();

        // Create map centered on Saudi Arabia
        map = new google.maps.Map(document.getElementById('map'), {
            center: defaultCenter,
            zoom: defaultZoom,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true
        });

        // Create marker (hidden by default, positioned at center)
        marker = new google.maps.Marker({
            map: map,
            position: defaultCenter,
            draggable: true,
            visible: false
        });

        // Initialize Places Autocomplete
        const input = document.getElementById('map-search-input');
        if (!input) {
            console.error('Search input not found!');
            return;
        }

        try {
            searchBox = new google.maps.places.Autocomplete(input, {
                types: ['geocode', 'establishment'],
                fields: ['geometry', 'formatted_address', 'name']
            });
            console.log('Places Autocomplete initialized successfully');
        } catch (e) {
            console.error('Failed to initialize Places Autocomplete:', e);
            return;
        }

        // Bias search results to map viewport
        map.addListener('bounds_changed', function() {
            searchBox.setBounds(map.getBounds());
        });

        // Handle place selection from search
        searchBox.addListener('place_changed', function() {
            const place = searchBox.getPlace();
            console.log('Place selected:', place);

            if (!place.geometry || !place.geometry.location) {
                console.warn('No geometry for selected place');
                return;
            }

            // Move map to selected place
            map.setCenter(place.geometry.location);
            map.setZoom(15);

            // Set location with address
            setLocation(
                place.geometry.location.lat(),
                place.geometry.location.lng(),
                place.formatted_address
            );
        });

        // Click on map = set location
        map.addListener('click', function(e) {
            setLocationWithGeocode(e.latLng.lat(), e.latLng.lng());
        });

        // Drag marker = update location
        marker.addListener('dragend', function() {
            const pos = marker.getPosition();
            setLocationWithGeocode(pos.lat(), pos.lng());
        });

        // My Location button
        document.getElementById('my-location-btn').addEventListener('click', getMyLocation);

        // Confirm button
        document.getElementById('confirm-location-btn').addEventListener('click', confirmLocation);

        // Show initial message (no auto-detect - professional UX)
        document.getElementById('coords-display').innerHTML = '@lang("Search for your address or click Use My Location")';
    }

    function autoDetectLocation() {
        if (!navigator.geolocation) {
            console.log('Geolocation not supported');
            document.getElementById('coords-display').innerHTML = '@lang("Click on map or search to select location")';
            return;
        }

        document.getElementById('coords-display').innerHTML = '<i class="fas fa-spinner fa-spin"></i> @lang("Detecting your location...")';

        navigator.geolocation.getCurrentPosition(
            function(position) {
                console.log('Got fresh GPS position:', position.coords.latitude, position.coords.longitude);
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                map.setCenter({ lat: lat, lng: lng });
                map.setZoom(14);

                setLocationWithGeocode(lat, lng);
            },
            function(error) {
                console.log('Geolocation error:', error.code, error.message);
                // Show message based on error type
                let msg = '@lang("Click on map or search to select location")';
                if (error.code === error.PERMISSION_DENIED) {
                    msg = '<span class="text-warning"><i class="fas fa-exclamation-triangle"></i> @lang("Location permission denied - please select manually")</span>';
                }
                document.getElementById('coords-display').innerHTML = msg;
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0  // Always get fresh location, never use cached
            }
        );
    }

    function setLocationWithGeocode(lat, lng) {
        // Show loading
        document.getElementById('coords-display').innerHTML = '<i class="fas fa-spinner fa-spin"></i> @lang("Loading address...")';

        // Reverse geocode to get address
        geocoder.geocode({ location: { lat: lat, lng: lng } }, function(results, status) {
            if (status === 'OK' && results[0]) {
                setLocation(lat, lng, results[0].formatted_address);
            } else {
                setLocation(lat, lng, null);
            }
        });
    }

    function setLocation(lat, lng, address) {
        selectedLat = lat;
        selectedLng = lng;
        selectedAddress = address || '';

        // Update marker
        marker.setPosition({ lat: lat, lng: lng });
        marker.setVisible(true);

        // Update display with full address
        let displayText = '';
        if (address) {
            displayText = '<div class="mb-1">' + address + '</div>';
            displayText += '<small class="text-muted">(' + lat.toFixed(6) + ', ' + lng.toFixed(6) + ')</small>';
        } else {
            displayText = lat.toFixed(6) + ', ' + lng.toFixed(6);
        }
        document.getElementById('coords-display').innerHTML = displayText;

        // Enable confirm button
        document.getElementById('confirm-location-btn').disabled = false;
    }

    function getMyLocation() {
        if (!navigator.geolocation) {
            alert('@lang("Browser does not support geolocation")');
            return;
        }

        const btn = document.getElementById('my-location-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;

        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                map.setCenter({ lat: lat, lng: lng });
                map.setZoom(15);
                setLocationWithGeocode(lat, lng);

                btn.innerHTML = originalText;
                btn.disabled = false;
            },
            function(error) {
                btn.innerHTML = originalText;
                btn.disabled = false;

                let errorMessage = '@lang("Could not get your location")';
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = '@lang("Location permission denied")';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = '@lang("Location unavailable")';
                        break;
                    case error.TIMEOUT:
                        errorMessage = '@lang("Location request timed out")';
                        break;
                }
                alert(errorMessage);
            },
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0
            }
        );
    }

    function confirmLocation() {
        if (!selectedLat || !selectedLng) {
            return;
        }

        const confirmBtn = document.getElementById('confirm-location-btn');
        const originalBtnText = confirmBtn.innerHTML;

        // Show loading state (keep modal open)
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> @lang("Loading...")';
        confirmBtn.disabled = true;

        // Fetch tax info FIRST, then close modal
        $.ajax({
            url: '/geocoding/tax-from-coordinates',
            method: 'POST',
            data: {
                latitude: selectedLat,
                longitude: selectedLng,
                merchant_id: checkoutMerchantId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                // Fill hidden fields
                $('#latitude').val(selectedLat);
                $('#longitude').val(selectedLng);
                if (response.country_id) $('#country_id').val(response.country_id);
                if (response.formatted_address) $('#address').val(response.formatted_address);
                if (response.postal_code) $('#zip').val(response.postal_code);

                // Get display address
                let displayAddress = selectedAddress;
                if (response.geocoding_success && response.formatted_address) {
                    displayAddress = response.formatted_address;
                }

                // Update main page button
                $('#open-map-btn').removeClass('m-btn--primary m-btn--outline').addClass('m-btn--success');
                $('#open-map-btn').html('<i class="fas fa-check-circle"></i> @lang("Location Selected")');

                // Show location info
                $('#selected-location-info').removeClass('d-none');
                let locationDisplayText = displayAddress
                    ? displayAddress + ' <small class="text-muted">(' + selectedLat.toFixed(6) + ', ' + selectedLng.toFixed(6) + ')</small>'
                    : selectedLat.toFixed(6) + ', ' + selectedLng.toFixed(6);
                $('#location-text').html(locationDisplayText);

                // Update tax display
                if (response.tax_rate > 0) {
                    $('.tax-display-wrapper').removeClass('d-none');
                    $('.tax-rate-text').html('(' + response.tax_rate + '%)');

                    var taxAmount = parseFloat(response.tax_amount || 0);
                    @if($gs->currency_format == 0)
                        $('.tax-amount-value').html('{{ $curr->sign }}' + taxAmount.toFixed(2));
                    @else
                        $('.tax-amount-value').html(taxAmount.toFixed(2) + '{{ $curr->sign }}');
                    @endif

                    if (response.tax_location) {
                        $('.tax-location-wrapper').removeClass('d-none');
                        $('.tax-location-text').html(response.tax_location);
                    }

                    if (typeof PriceSummary !== 'undefined' && PriceSummary.updateTax) {
                        PriceSummary.updateTax(response.tax_rate, taxAmount);
                    }
                } else {
                    $('.tax-display-wrapper').addClass('d-none');
                    $('.tax-location-wrapper').addClass('d-none');
                }

                // NOW close modal (after everything is ready)
                $('#mapModal').modal('hide');

                // Reset button for next time
                confirmBtn.innerHTML = originalBtnText;
                confirmBtn.disabled = false;
            },
            error: function(xhr) {
                // Show error, keep modal open
                confirmBtn.innerHTML = originalBtnText;
                confirmBtn.disabled = false;

                let errorMsg = '@lang("Failed to get location details. Please try again.")';
                document.getElementById('coords-display').innerHTML =
                    '<span class="text-danger"><i class="fas fa-exclamation-circle"></i> ' + errorMsg + '</span>';
            }
        });
    }

    // Form validation - require location
    $('form.address-wrapper').on('submit', function(e) {
        const lat = $('#latitude').val();
        const lng = $('#longitude').val();

        if (!lat || !lng) {
            e.preventDefault();

            if (typeof toastr !== 'undefined') {
                toastr.error('@lang("Please select your location from the map")');
            } else {
                alert('@lang("Please select your location from the map")');
            }

            $('#open-map-btn').addClass('m-btn--danger').removeClass('m-btn--primary m-btn--outline m-btn--success');
            setTimeout(function() {
                $('#open-map-btn').removeClass('m-btn--danger').addClass('m-btn--primary m-btn--outline');
            }, 3000);

            return false;
        }
    });
    </script>
@endsection
