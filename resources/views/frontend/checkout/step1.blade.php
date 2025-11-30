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
                        <li><a href="{{ route('front.cart') }}">@lang('Cart')</a></li>
                        <li>@lang('Checkout')</li>
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
            <form class="address-wrapper" action="{{ isset($is_vendor_checkout) && $is_vendor_checkout ? route('front.checkout.vendor.step1.submit', $vendor_id) : route('front.checkout.step1.submit') }}" method="POST">
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

                                <!-- Google Maps Location Picker -->
                                <div class="col-lg-12">
                                    <div class="alert alert-info d-flex align-items-center" role="alert">
                                        <i class="fas fa-map-marker-alt me-2" style="font-size: 20px;"></i>
                                        <div>
                                            <strong>@lang('Please select your delivery location from the map below')</strong>
                                        </div>
                                    </div>
                                    <div class="mt-3 mb-3">
                                        <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#mapModal" style="padding: 12px;">
                                            <i class="fas fa-map-marker-alt"></i> @lang('Select Location from Map')
                                        </button>
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

                            {{-- ✅ Unified Price Summary Component - Step 1 --}}
                            @include('includes.checkout-price-summary', [
                                'step' => 1,
                                'productsTotal' => $productsTotal ?? $totalPrice,
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






                {{-- Location Data from Map (Primary Source) --}}
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="country_id" id="country_id">
                <input type="hidden" name="state_id" id="state_id">
                <input type="hidden" name="city_id" id="city_id">
                {{-- Hidden fields for backend - these are the primary source --}}
                <input type="hidden" name="customer_city" id="customer_city_hidden">
                <input type="hidden" name="customer_country" id="customer_country_hidden">
                <input type="hidden" name="customer_state" id="customer_state_hidden">

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
                    value="{{ App\Models\Product::convertPrice($totalPrice) }}">
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

    {{-- Google Maps Modal --}}
    <div class="modal fade" id="mapModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h5 class="modal-title">@lang('Select location on map')</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="alert-container-modal" style="padding: 15px;"></div>

                    <div style="padding: 20px;">
                        <div id="map-container" style="position: relative; height: 500px; border-radius: 8px; overflow: hidden; border: 2px solid #e0e0e0;">
                            <div class="map-search" style="position: absolute; top: 10px; right: 10px; left: 10px; z-index: 10;">
                                <input type="text" id="map-search-input" placeholder="@lang('Search for an address...')"
                                       style="width: 100%; padding: 12px 15px; border: 2px solid #667eea; border-radius: 8px; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); background: white;">
                            </div>
                            <div id="map" style="width: 100%; height: 100%;"></div>
                            <div class="loading-overlay" id="loading-overlay-modal" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.9); display: none; align-items: center; justify-content: center; z-index: 20;">
                                <div class="spinner" style="border: 3px solid #f3f3f3; border-top: 3px solid #667eea; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite;"></div>
                            </div>
                        </div>

                        <div class="buttons-container" style="display: flex; gap: 10px; margin-top: 15px;">
                            <button class="btn btn-secondary" id="reset-btn-modal" type="button">
                                @lang('Reset Location')
                            </button>
                            <button class="btn btn-secondary" id="current-location-btn-modal" type="button">
                                @lang('My Current Location')
                            </button>
                        </div>

                        <div class="location-info" id="location-info-modal" style="display: none; background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px;">
                            <h6 style="font-size: 16px; color: #333; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">@lang('Location Information')</h6>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                                <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">@lang('Country (Arabic)')</label>
                                    <div id="country-ar-modal" style="font-size: 14px; color: #333;">-</div>
                                </div>
                                <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">@lang('Country (English)')</label>
                                    <div id="country-en-modal" style="font-size: 14px; color: #333;">-</div>
                                </div>
                                <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">@lang('State (Arabic)')</label>
                                    <div id="state-ar-modal" style="font-size: 14px; color: #333;">-</div>
                                </div>
                                <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">@lang('State (English)')</label>
                                    <div id="state-en-modal" style="font-size: 14px; color: #333;">-</div>
                                </div>
                                <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">@lang('City (Arabic)')</label>
                                    <div id="city-ar-modal" style="font-size: 14px; color: #333;">-</div>
                                </div>
                                <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">@lang('City (English)')</label>
                                    <div id="city-en-modal" style="font-size: 14px; color: #333;">-</div>
                                </div>
                            </div>

                            <div style="display: flex; gap: 15px; margin-top: 15px;">
                                <div style="flex: 1; background: white; padding: 15px; border-radius: 6px; text-align: center;">
                                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">@lang('Latitude')</label>
                                    <div id="latitude-value-modal" style="font-size: 16px; font-weight: 600; color: #667eea;">-</div>
                                </div>
                                <div style="flex: 1; background: white; padding: 15px; border-radius: 6px; text-align: center;">
                                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">@lang('Longitude')</label>
                                    <div id="longitude-value-modal" style="font-size: 16px; font-weight: 600; color: #667eea;">-</div>
                                </div>
                            </div>

                            <div style="margin-top: 15px;">
                                <div style="background: white; padding: 15px; border-radius: 6px;">
                                    <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">@lang('Full Address')</label>
                                    <div id="full-address-modal" style="font-size: 14px; color: #333;">-</div>
                                </div>
                            </div>

                            {{-- Tryoto Verification Section --}}
                            <div id="tryoto-info-modal" style="display: none; margin-top: 15px; padding: 15px; border-radius: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                    <i class="fas fa-shipping-fast" style="color: white; font-size: 20px; margin-left: 10px;"></i>
                                    <h6 style="color: white; margin: 0; font-size: 15px; font-weight: 600;">@lang('Tryoto Shipping Information')</h6>
                                </div>

                                <div id="tryoto-verified-box" style="background: rgba(255,255,255,0.95); padding: 12px; border-radius: 6px; margin-top: 10px;">
                                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                                        <i class="fas fa-check-circle" style="color: #28a745; margin-left: 8px;"></i>
                                        <span id="tryoto-status-text" style="font-size: 13px; color: #333; font-weight: 600;">-</span>
                                    </div>
                                    <div id="tryoto-companies-box" style="display: none; margin-top: 8px; padding-top: 8px; border-top: 1px dashed #dee2e6;">
                                        <small style="color: #666; display: flex; align-items: center;">
                                            <i class="fas fa-truck" style="margin-left: 5px; color: #667eea;"></i>
                                            <span id="tryoto-companies-text">-</span>
                                        </small>
                                    </div>
                                </div>

                                {{-- Alternative City Warning --}}
                                <div id="tryoto-alternative-box" style="display: none; background: rgba(255, 243, 205, 0.95); padding: 12px; border-radius: 6px; margin-top: 10px; border-right: 4px solid #ffc107;">
                                    <div style="display: flex; align-items: start;">
                                        <i class="fas fa-exclamation-triangle" style="color: #ff9800; margin-left: 8px; margin-top: 2px;"></i>
                                        <div style="flex: 1;">
                                            <p style="margin: 0; font-size: 13px; color: #856404; font-weight: 600;">
                                                @lang('Selected location is not supported for shipping')
                                            </p>
                                            <p style="margin: 5px 0 0 0; font-size: 12px; color: #856404;">
                                                <strong>@lang('Nearest supported city'):</strong>
                                                <span id="tryoto-alternative-city" style="font-weight: 700;">-</span>
                                                (<span id="tryoto-alternative-distance">-</span> @lang('km'))
                                            </p>
                                            <p style="margin: 5px 0 0 0; font-size: 11px; color: #856404;">
                                                @lang('Shipping will be to') <strong id="tryoto-alternative-city-ar">-</strong> @lang('and we will contact you to coordinate delivery')
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Not Supported Warning --}}
                                <div id="tryoto-not-supported-box" style="display: none; background: rgba(255, 82, 82, 0.1); padding: 12px; border-radius: 6px; margin-top: 10px; border-right: 4px solid #dc3545;">
                                    <div style="display: flex; align-items: center;">
                                        <i class="fas fa-times-circle" style="color: #dc3545; margin-left: 8px;"></i>
                                        <p style="margin: 0; font-size: 13px; color: #721c24; font-weight: 600;">
                                            @lang('Sorry, this location is outside the available shipping area')
                                        </p>
                                    </div>
                                    <p style="margin: 5px 0 0 0; font-size: 11px; color: #721c24;">
                                        @lang('Please select a location within Saudi Arabia')
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Close')</button>
                    <button type="button" class="btn btn-primary" id="use-location-btn-modal" disabled>@lang('Use This Location')</button>
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


        // ⚠️ DISABLED - State dropdown change handler (no longer needed)
        /*
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

    {{-- Google Maps Scripts --}}
    <style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .loading-overlay.active {
        display: flex !important;
    }
    /* Map-selected fields styling */
    select.map-selected,
    input.map-selected {
        background-color: #f0f8ff !important;
        border-color: #28a745 !important;
        cursor: not-allowed;
    }
    .map-selected + .nice-select {
        background-color: #f0f8ff !important;
        border-color: #28a745 !important;
        pointer-events: none;
    }
    .badge.bg-success {
        font-size: 11px;
        padding: 3px 8px;
        margin-left: 5px;
        vertical-align: middle;
    }
    </style>

    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places&language=en" async defer></script>
    <script>
    // Google Maps variables for modal
    let mapModal, markerModal, geocoderModal, searchBoxModal, debounceTimerModal, selectedLocationData;
    const DEBOUNCE_DELAY = 400;
    const DEFAULT_CENTER = { lat: 24.7136, lng: 46.6753 }; // Riyadh, Saudi Arabia

    // Wait for Google Maps to load
    function waitForGoogleMaps(callback) {
        if (typeof google !== 'undefined' && google.maps) {
            callback();
        } else {
            setTimeout(() => waitForGoogleMaps(callback), 100);
        }
    }

    // ============================================
    // استرجاع الموقع المحفوظ من localStorage
    // ============================================
    function restoreSavedLocation() {
        const savedLocation = localStorage.getItem('selectedLocation');

        if (!savedLocation) {
            console.log('ℹ️ لا يوجد موقع محفوظ في localStorage');
            return;
        }

        try {
            selectedLocationData = JSON.parse(savedLocation);

            console.log('📍 استرجاع الموقع المحفوظ:', selectedLocationData);

            // ملء الـ hidden fields
            $('#latitude').val(selectedLocationData.coordinates?.latitude || '');
            $('#longitude').val(selectedLocationData.coordinates?.longitude || '');
            $('#country_id').val(selectedLocationData.country?.id || '');
            $('#state_id').val(selectedLocationData.state?.id || '');
            $('#city_id').val(selectedLocationData.city?.id || '');

            // ✅ Fill customer_* hidden fields for backend validation
            $('#customer_city_hidden').val(selectedLocationData.city?.id || '');
            $('#customer_country_hidden').val(selectedLocationData.country?.name || '');
            $('#customer_state_hidden').val(selectedLocationData.state?.name || '');

            console.log('✅ Hidden fields restored from localStorage:', {
                customer_city: $('#customer_city_hidden').val(),
                customer_country: $('#customer_country_hidden').val(),
                customer_state: $('#customer_state_hidden').val()
            });

            const fullAddress = selectedLocationData.address?.ar || selectedLocationData.address?.en || '';
            $('#address').val(fullAddress);

            // Update ZIP code if available
            if (selectedLocationData.postal_code) {
                $('#zip').val(selectedLocationData.postal_code);
            }

            // تحديث UI
            const mapBtn = $('[data-bs-target="#mapModal"]');
            mapBtn.removeClass('btn-outline-primary btn-danger').addClass('btn-success');
            mapBtn.html('<i class="fas fa-check-circle"></i> تم اختيار الموقع من الخريطة بنجاح');

            const locationSummary = `${selectedLocationData.city?.name_ar || ''}, ${selectedLocationData.state?.name_ar || ''}, ${selectedLocationData.country?.name_ar || ''}`;

            // إزالة رسالة قديمة إن وجدت
            $('.map-location-info').remove();

            mapBtn.parent().append(`
                <div class="map-location-info mt-2 alert alert-success">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>الموقع المحدد:</strong> ${locationSummary}
                </div>
            `);

            console.log('✅ تم استرجاع الموقع المحفوظ بنجاح');

            // ⚠️ المهم: حساب الضريبة!
            calculateTaxIfNeeded();

        } catch (error) {
            console.error('❌ فشل استرجاع الموقع المحفوظ:', error);
            localStorage.removeItem('selectedLocation');
        }
    }

    // ============================================
    // حساب الضريبة بناءً على state_id
    // ============================================
    function calculateTaxIfNeeded() {
        const stateId = $('#state_id').val();
        const countryId = $('#country_id').val();

        if (!stateId && !countryId) {
            console.log('⚠️ لا يوجد state_id أو country_id لحساب الضريبة');
            return;
        }

        console.log(`🔍 حساب الضريبة - Country ID: ${countryId}, State ID: ${stateId}`);

        // استدعاء دالة tax_submit الموجودة بالفعل
        // IMPORTANT: التأكد من أن tax_submit موجود في الصفحة
        if (typeof tax_submit === 'function') {
            tax_submit(countryId, stateId);
            console.log('✅ تم استدعاء دالة حساب الضريبة');
        } else {
            console.error('❌ دالة tax_submit غير موجودة');

            // Fallback: استدعاء مباشر للـ API
            const total = $("#ttotal").val();
            const ship = 0;

            $.ajax({
                type: "GET",
                url: mainurl + "/country/tax/check",
                data: {
                    state_id: stateId,
                    country_id: countryId,
                    total: total,
                    shipping_cost: ship
                },
                success: function(data) {
                    console.log('✅ استجابة API للضريبة:', data);

                    // Update hidden fields
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

                        console.log(`✅ تم عرض الضريبة: ${data[1]}% = ${taxAmount} ${data[3] || ''}`);
                    } else {
                        $('.tax-display-wrapper').addClass('d-none');
                        $('.tax-location-wrapper').addClass('d-none');
                        console.log('ℹ️ لا توجد ضريبة على هذا الموقع');
                    }

                    // Update final total
                    var ttotal = parseFloat($('#grandtotal').val());
                    ttotal = parseFloat(ttotal).toFixed(2);

                    if (pos == 0) {
                        $('#final-cost').html('{{ $curr->sign }}' + ttotal);
                    } else {
                        $('#final-cost').html(ttotal + '{{ $curr->sign }}');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ فشل حساب الضريبة:', error);
                }
            });
        }
    }

    // Initialize map when modal is shown
    $('#mapModal').on('shown.bs.modal', function() {
        if (!mapModal) {
            waitForGoogleMaps(initializeMap);
        } else {
            google.maps.event.trigger(mapModal, 'resize');
        }
    });

    function initializeMap() {
        geocoderModal = new google.maps.Geocoder();

        mapModal = new google.maps.Map(document.getElementById('map'), {
            center: DEFAULT_CENTER,
            zoom: 12,
            mapTypeControl: true,
            streetViewControl: false,
            fullscreenControl: true,
        });

        markerModal = new google.maps.Marker({
            map: mapModal,
            draggable: true,
            animation: google.maps.Animation.DROP,
        });

        // Setup search box
        const searchInput = document.getElementById('map-search-input');
        searchBoxModal = new google.maps.places.SearchBox(searchInput);

        // Bias search results to map viewport
        mapModal.addListener('bounds_changed', () => {
            searchBoxModal.setBounds(mapModal.getBounds());
        });

        // Handle search selection
        searchBoxModal.addListener('places_changed', () => {
            const places = searchBoxModal.getPlaces();
            if (places.length === 0) return;

            const place = places[0];
            if (!place.geometry || !place.geometry.location) return;

            mapModal.setCenter(place.geometry.location);
            markerModal.setPosition(place.geometry.location);
            markerModal.setVisible(true);

            handleLocationChange(place.geometry.location.lat(), place.geometry.location.lng());
        });

        // Map click event
        mapModal.addListener('click', (event) => {
            markerModal.setPosition(event.latLng);
            markerModal.setVisible(true);
            handleLocationChange(event.latLng.lat(), event.latLng.lng());
        });

        // Marker drag event
        markerModal.addListener('dragend', () => {
            const position = markerModal.getPosition();
            handleLocationChange(position.lat(), position.lng());
        });

        // Button events
        document.getElementById('use-location-btn-modal').addEventListener('click', useLocation);
        document.getElementById('reset-btn-modal').addEventListener('click', resetSelection);
        document.getElementById('current-location-btn-modal').addEventListener('click', getCurrentLocationModal);
    }

    // Handle location change with debouncing
    function handleLocationChange(lat, lng) {
        clearTimeout(debounceTimerModal);
        debounceTimerModal = setTimeout(() => {
            reverseGeocode(lat, lng);
        }, DEBOUNCE_DELAY);
    }

    // Reverse geocode coordinates
    async function reverseGeocode(lat, lng) {
        showLoadingModal(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            const headers = {
                'Content-Type': 'application/json'
            };

            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken.content;
            }

            const response = await fetch('/geocoding/reverse', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify({
                    latitude: lat,
                    longitude: lng
                })
            });

            const result = await response.json();

            if (result.success) {
                selectedLocationData = result.data;
                displayLocationInfoModal(result.data);
                document.getElementById('use-location-btn-modal').disabled = false;

                // Show warning if using nearest city
                if (result.warning) {
                    showAlertModal(result.warning, 'warning');
                } else {
                    showAlertModal('تم تحديد الموقع بنجاح', 'success');
                }
            } else {
                showAlertModal('فشل في الحصول على معلومات الموقع: ' + (result.message || 'خطأ غير معروف'), 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlertModal('حدث خطأ في الاتصال بالخادم', 'error');
        } finally {
            showLoadingModal(false);
        }
    }

    // Display location information in modal
    function displayLocationInfoModal(data) {
        document.getElementById('country-ar-modal').textContent = data.country?.name_ar || '-';
        document.getElementById('country-en-modal').textContent = data.country?.name || '-';
        document.getElementById('state-ar-modal').textContent = data.state?.name_ar || '-';
        document.getElementById('state-en-modal').textContent = data.state?.name || '-';
        document.getElementById('city-ar-modal').textContent = data.city?.name_ar || '-';
        document.getElementById('city-en-modal').textContent = data.city?.name || '-';
        document.getElementById('latitude-value-modal').textContent = data.coordinates?.latitude.toFixed(6) || '-';
        document.getElementById('longitude-value-modal').textContent = data.coordinates?.longitude.toFixed(6) || '-';
        document.getElementById('full-address-modal').textContent = data.address?.ar || data.address?.en || '-';

        document.getElementById('location-info-modal').style.display = 'block';

        // Display Tryoto verification info
        displayTryotoInfo(data);
    }

    // Display Tryoto verification information
    function displayTryotoInfo(data) {
        const tryotoBox = document.getElementById('tryoto-info-modal');
        if (!tryotoBox) return; // Element doesn't exist in DOM

        const verifiedBox = document.getElementById('tryoto-verified-box');
        const alternativeBox = document.getElementById('tryoto-alternative-box');
        const notSupportedBox = document.getElementById('tryoto-not-supported-box');
        const companiesBox = document.getElementById('tryoto-companies-box');

        // Hide all boxes first
        if (verifiedBox) verifiedBox.style.display = 'none';
        if (alternativeBox) alternativeBox.style.display = 'none';
        if (notSupportedBox) notSupportedBox.style.display = 'none';
        if (companiesBox) companiesBox.style.display = 'none';

        // Check resolution_info from new API
        if (!data.resolution_info) {
            tryotoBox.style.display = 'none';
            return;
        }

        const resolutionInfo = data.resolution_info;
        const strategy = resolutionInfo.strategy;

        // Show Tryoto box
        tryotoBox.style.display = 'block';
        if (verifiedBox) verifiedBox.style.display = 'block';

        if (strategy === 'exact_match') {
            // Perfect match
            if (document.getElementById('tryoto-status-text')) {
                document.getElementById('tryoto-status-text').textContent = 'الموقع مدعوم للشحن عبر Tryoto ✓';
            }

            if (resolutionInfo.shipping_companies > 0 && companiesBox) {
                companiesBox.style.display = 'block';
                if (document.getElementById('tryoto-companies-text')) {
                    document.getElementById('tryoto-companies-text').textContent =
                        `${resolutionInfo.shipping_companies} شركة شحن متاحة`;
                }
            }

        } else if (strategy === 'name_variation') {
            // Name variation match
            if (document.getElementById('tryoto-status-text')) {
                document.getElementById('tryoto-status-text').textContent = 'الموقع مدعوم للشحن (تم التحقق من الاسم) ✓';
            }

            if (resolutionInfo.shipping_companies > 0 && companiesBox) {
                companiesBox.style.display = 'block';
                if (document.getElementById('tryoto-companies-text')) {
                    document.getElementById('tryoto-companies-text').textContent =
                        `${resolutionInfo.shipping_companies} شركة شحن متاحة`;
                }
            }

        } else if (strategy === 'nearest_city' && resolutionInfo.is_nearest_city) {
            // Alternative city found (nearest city)
            if (document.getElementById('tryoto-status-text')) {
                document.getElementById('tryoto-status-text').textContent = 'سيتم استخدام أقرب مدينة مدعومة للشحن';
            }

            if (alternativeBox) {
                alternativeBox.style.display = 'block';
                if (document.getElementById('tryoto-alternative-city')) {
                    document.getElementById('tryoto-alternative-city').textContent = resolutionInfo.resolved_city;
                }
                if (document.getElementById('tryoto-alternative-city-ar')) {
                    document.getElementById('tryoto-alternative-city-ar').textContent = data.city?.name_ar || resolutionInfo.resolved_city;
                }
                if (document.getElementById('tryoto-alternative-distance')) {
                    document.getElementById('tryoto-alternative-distance').textContent = resolutionInfo.distance_km || 0;
                }
            }

            if (resolutionInfo.shipping_companies > 0 && companiesBox) {
                companiesBox.style.display = 'block';
                if (document.getElementById('tryoto-companies-text')) {
                    document.getElementById('tryoto-companies-text').textContent =
                        `${resolutionInfo.shipping_companies} شركة شحن متاحة في ${data.city?.name_ar || resolutionInfo.resolved_city}`;
                }
            }
        }
    }

    // Use selected location - populate hidden fields only
    function useLocation() {
        if (!selectedLocationData) return;

        // Check if using nearest city (from resolution_info)
        let useNearestCity = false;

        if (selectedLocationData.resolution_info &&
            selectedLocationData.resolution_info.is_nearest_city) {
            useNearestCity = true;

            // Show confirmation message
            if (typeof toastr !== 'undefined') {
                toastr.warning(
                    `⚠️ سيتم الشحن إلى ${selectedLocationData.city.name_ar} (${selectedLocationData.resolution_info.distance_km} كم من موقعك الأصلي)`,
                    'معلومات الشحن',
                    {timeOut: 10000, closeButton: true, progressBar: true}
                );
            }
        }

        // ✅ Fill HIDDEN FIELDS ONLY (Primary data source)
        $('#latitude').val(selectedLocationData.coordinates?.latitude || '');
        $('#longitude').val(selectedLocationData.coordinates?.longitude || '');
        $('#country_id').val(selectedLocationData.country?.id || '');
        $('#state_id').val(selectedLocationData.state?.id || '');
        $('#city_id').val(selectedLocationData.city?.id || '');

        // ✅ Fill customer_* hidden fields for backend validation
        $('#customer_city_hidden').val(selectedLocationData.city?.id || '');
        $('#customer_country_hidden').val(selectedLocationData.country?.name || '');
        $('#customer_state_hidden').val(selectedLocationData.state?.name || '');

        console.log('✅ Hidden fields filled:', {
            customer_city: $('#customer_city_hidden').val(),
            customer_country: $('#customer_country_hidden').val(),
            customer_state: $('#customer_state_hidden').val()
        });

        // Update visible address field
        const fullAddress = selectedLocationData.address?.ar || selectedLocationData.address?.en || '';
        $('#address').val(fullAddress);

        // Update ZIP code if available
        if (selectedLocationData.postal_code) {
            $('#zip').val(selectedLocationData.postal_code);
        }

        // Store original city name if using nearest city
        if (useNearestCity && selectedLocationData.resolution_info) {
            let originalCityInput = $('input[name="original_city_name"]');
            if (originalCityInput.length === 0) {
                $('form.address-wrapper').append(
                    `<input type="hidden" name="original_city_name" value="${selectedLocationData.resolution_info.original_city}">`
                );
            } else {
                originalCityInput.val(selectedLocationData.resolution_info.original_city);
            }
        }

        // ✅ حفظ الموقع في localStorage لاستخدامه عند Refresh/Back/Forward
        try {
            localStorage.setItem('selectedLocation', JSON.stringify({
                country: selectedLocationData.country,
                state: selectedLocationData.state,
                city: selectedLocationData.city,
                coordinates: selectedLocationData.coordinates,
                address: selectedLocationData.address,
                resolution_info: selectedLocationData.resolution_info,
                postal_code: selectedLocationData.postal_code
            }));
            console.log('✅ تم حفظ الموقع في localStorage');
        } catch (error) {
            console.error('❌ فشل حفظ الموقع في localStorage:', error);
        }

        // ✅ Update UI to show success
        const mapBtn = $('[data-bs-target="#mapModal"]');
        mapBtn.removeClass('btn-outline-primary btn-danger').addClass('btn-success');
        mapBtn.html('<i class="fas fa-check-circle"></i> تم اختيار الموقع من الخريطة بنجاح');

        // Show location summary
        const locationSummary = `${selectedLocationData.city?.name_ar || ''}, ${selectedLocationData.state?.name_ar || ''}, ${selectedLocationData.country?.name_ar || ''}`;

        // Add location info below button if not exists
        let locationInfo = $('.map-location-info');
        if (locationInfo.length === 0) {
            mapBtn.parent().append(`
                <div class="map-location-info mt-2 alert alert-success">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>الموقع المحدد:</strong> ${locationSummary}
                </div>
            `);
        } else {
            locationInfo.html(`
                <i class="fas fa-map-marker-alt"></i>
                <strong>الموقع المحدد:</strong> ${locationSummary}
            `);
        }

        // ✅ حساب الضريبة بعد اختيار الموقع
        // إعطاء وقت قصير للـ DOM ليحدّث القيم
        setTimeout(function() {
            calculateTaxIfNeeded();
        }, 100);

        if (typeof toastr !== 'undefined') {
            toastr.success('تم حفظ الموقع بنجاح! سيتم حساب الضريبة إذا وجدت', 'نجاح', {
                timeOut: 5000,
                progressBar: true
            });
        }

        $('#mapModal').modal('hide');
    }


    // Reset selection
    function resetSelection() {
        markerModal.setVisible(false);
        selectedLocationData = null;
        document.getElementById('use-location-btn-modal').disabled = true;
        document.getElementById('location-info-modal').style.display = 'none';
        document.getElementById('map-search-input').value = '';
        mapModal.setCenter(DEFAULT_CENTER);
        mapModal.setZoom(12);
        clearAlertModal();
    }

    // Get current location
    function getCurrentLocationModal() {
        if (navigator.geolocation) {
            showLoadingModal(true);
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const pos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    mapModal.setCenter(pos);
                    markerModal.setPosition(pos);
                    markerModal.setVisible(true);
                    handleLocationChange(pos.lat, pos.lng);
                },
                () => {
                    showLoadingModal(false);
                    showAlertModal('فشل في الحصول على موقعك الحالي', 'error');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        } else {
            showAlertModal('المتصفح لا يدعم خدمة تحديد الموقع', 'error');
        }
    }

    // Show/hide loading overlay
    function showLoadingModal(show) {
        const overlay = document.getElementById('loading-overlay-modal');
        if (overlay) {
            if (show) {
                overlay.classList.add('active');
            } else {
                overlay.classList.remove('active');
            }
        }
    }

    // Show alert message
    function showAlertModal(message, type) {
        const container = document.getElementById('alert-container-modal');
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        container.innerHTML = `
            <div class="alert ${alertClass}" style="margin-bottom: 0;">
                ${message}
            </div>
        `;
    }

    // Clear alert
    function clearAlertModal() {
        const container = document.getElementById('alert-container-modal');
        if (container) {
            container.innerHTML = '';
        }
    }

    // ===================== Tryoto City Verification =====================
    // ⚠️ DISABLED - No longer needed as we rely on map selection only
    // Dropdowns are hidden and not used for form submission

    // ============================================
    // استدعاء استرجاع الموقع عند تحميل الصفحة
    // ============================================
    $(document).ready(function() {
        console.log('📄 تحميل صفحة Checkout Step 1');
        restoreSavedLocation();
    });

    // ============================================
    // Form Validation for Map Location + CSRF Token Refresh
    // ============================================
    $('form.address-wrapper').on('submit', function(e) {
        const $form = $(this);

        // ✅ STEP 1: Validate coordinates exist FIRST (quick check)
        const lat = $('#latitude').val();
        const lng = $('#longitude').val();

        console.log('📝 Form submission attempt', { lat, lng });

        if (!lat || !lng || lat === '' || lng === '') {
            e.preventDefault();
            e.stopPropagation();

            console.warn('⚠️ Form submission blocked - missing coordinates');

            // Scroll to map button
            const mapBtn = $('[data-bs-target="#mapModal"]');
            if (mapBtn.length) {
                $('html, body').animate({
                    scrollTop: mapBtn.offset().top - 100
                }, 500);
            }

            // Show error
            if (typeof toastr !== 'undefined') {
                toastr.error(
                    'يرجى اختيار موقع التوصيل من الخريطة قبل المتابعة',
                    'خطأ',
                    {
                        timeOut: 5000,
                        closeButton: true,
                        positionClass: 'toast-top-center'
                    }
                );
            } else {
                alert('يرجى اختيار موقع التوصيل من الخريطة قبل المتابعة');
            }

            // Highlight the map button
            mapBtn.addClass('btn-danger').removeClass('btn-outline-primary btn-success');
            setTimeout(function() {
                mapBtn.removeClass('btn-danger').addClass('btn-outline-primary');
            }, 3000);

            return false;
        }

        // ✅ STEP 2: Fetch fresh CSRF token from server before submission
        e.preventDefault(); // Prevent default submission

        console.log('🔄 Fetching fresh CSRF token from server...');

        $.get(mainurl + '/csrf-token', function(response) {
            const freshToken = response.token;
            const currentMetaToken = $('meta[name="csrf-token"]').attr('content');
            const currentFormToken = $form.find('input[name="_token"]').val();

            console.log('🔐 CSRF Token Refresh', {
                fresh_token: freshToken,
                old_meta_token: currentMetaToken,
                old_form_token: currentFormToken,
                tokens_changed: freshToken !== currentFormToken
            });

            // Update both meta tag and form token with fresh token
            $('meta[name="csrf-token"]').attr('content', freshToken);
            $form.find('input[name="_token"]').val(freshToken);

            console.log('✅ تم تحديث CSRF token - إرسال النموذج الآن');

            // Now submit the form with fresh token
            $form.off('submit').submit();
        }).fail(function() {
            console.error('❌ فشل الحصول على CSRF token جديد - المحاولة بالـ token الحالي');
            // If fetch fails, try submitting with current token anyway
            $form.off('submit').submit();
        });

        return false; // Prevent default until we get fresh token
    });

    console.log('✅ Google Maps Checkout Integration - Fully Loaded');
    </script>
@endsection
