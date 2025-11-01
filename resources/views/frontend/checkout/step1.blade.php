@extends('layouts.front')
@section('content')
   {{-- ... breadcrumb محذوف للتخفيف ... --}}

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
                                               placeholder="@lang('Enter Your Name')" {{ Auth::check() ? '' : '' }}>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="email">@lang('Email')</label>
                                        <input class="input-cls" id="email" type="email" name="personal_email"
                                               placeholder="@lang('Enter Your Emai')l"
                                               value="{{ Auth::check() ? Auth::user()->email : '' }}">
                                    </div>
                                </div>

                                @if (!Auth::check())
                                    <div class="col-lg-12">
                                        <div class="gs-checkbox-wrapper" data-bs-toggle="collapse"
                                             data-bs-target="#show_passwords" aria-expanded="false"
                                             aria-controls="show_passwords" role="region">
                                            <input type="checkbox" id="showca">
                                            <label class="icon-label" for="showca">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                                    <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </label>
                                            <label for="showca">@lang('Create an account ?')</label>
                                        </div>
                                    </div>
                                    <div class="col-12 collapse" id="show_passwords">
                                        <div class="row gy-4">
                                            <div class="col-lg-6">
                                                <div class="input-wrapper">
                                                    <label class="label-cls" for="crpass">@lang('Create Password')</label>
                                                    <input class="input-cls" id="crpass" type="password" placeholder="@lang('Create Your Password')">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="input-wrapper">
                                                    <label class="label-cls" for="conpass">@lang('Confirm Password')</label>
                                                    <input class="input-cls" id="conpass" type="password" placeholder="@lang('Confirm Password')">
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
                                        <select class="input-cls" id="shipop" name="shipping" required>
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
                                                <option value="{{ $pickup->location }}">{{ $pickup->location }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="customer_name">@lang('Name')</label>
                                        <input class="input-cls" id="customer_name" type="text" name="customer_name"
                                               placeholder="@lang('Full Name')"
                                               value="{{ old('customer_name', Auth::check() ? Auth::user()->name : '') }}">
                                        @error('customer_name') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="customer_email">@lang('Email')</label>
                                        <input class="input-cls" id="customer_email" type="text" name="customer_email"
                                               placeholder="@lang('Your Email')"
                                               value="{{ old('customer_email', Auth::check() ? Auth::user()->email : '') }}">
                                        @error('customer_email') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="phone">@lang('Phone Number')</label>
                                        <input class="input-cls" id="phone" type="tel" name="customer_phone"
                                               placeholder="@lang('Phone Number')"
                                               value="{{ old('customer_phone', Auth::check() ? Auth::user()->phone : '') }}">
                                        @error('customer_phone') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="address">@lang('Address')</label>
                                        <input class="input-cls" id="address" type="text" name="customer_address"
                                               placeholder="@lang('Address')"
                                               value="{{ old('customer_address', Auth::check() ? Auth::user()->address : '') }}">
                                        @error('customer_address') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="zip">@lang('Postal Code')</label>
                                        <input class="input-cls" id="zip" type="text" placeholder="@lang('Postal Code')" name="customer_zip"
                                               value="{{ old('customer_zip', Auth::check() ? Auth::user()->zip : '') }}">
                                    </div>
                                </div>

                                {{-- للضيف + للمسجّل: نظهر حقول الدولة/الولاية/المدينة للجميع --}}
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls">@lang('Select Country')</label>
                                        {{-- إزالة class="nice-select" لمنع التفعيل التلقائي المزدوج --}}
                                        <select id="select_country" name="customer_country" class="input-cls" required>
                                            @include('includes.countries')
                                        </select>
                                        @error('customer_country') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="col-lg-6 select_state d-none">
                                    <div class="input-wrapper">
                                        <label class="label-cls">@lang('Select State')</label>
                                        <select id="show_state" name="customer_state" class="input-cls"></select>
                                        @error('customer_state') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="col-lg-6 select_city d-none">
                                    <div class="input-wrapper">
                                        <label class="label-cls">@lang('Select City')</label>
                                        <select id="show_city" name="customer_city" class="input-cls"></select>
                                        @error('customer_city') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- Google Maps Location Picker -->
                                <div class="col-lg-12">
                                    <div class="mt-3 mb-3">
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#mapModal">
                                            <i class="fas fa-map-marker-alt"></i> @lang('Select Location from Map')
                                        </button>
                                        <small class="text-muted d-block mt-2">@lang('Click to open map and select your exact location')</small>
                                    </div>
                                </div>

                                <!-- chekbox -->
                                <div class="col-lg-12  {{ $digital == 1 ? 'd-none' : '' }}" id="ship_deff">
                                    <div class="gs-checkbox-wrapper" data-bs-toggle="collapse"
                                         data-bs-target="#show_shipping_address" role="region" aria-expanded="false"
                                         aria-controls="show_shipping_address">
                                        <input type="checkbox" id="shpto" name="is_shipping" value="0">
                                        <label class="icon-label" for="shpto">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none">
                                                <path d="M10 3L4.5 8.5L2 6" stroke="#EE1243" stroke-width="1.6666" stroke-linecap="round" stroke-linejoin="round"/>
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
                                        <label class="label-cls" for="shipping_name">@lang('Name')</label>
                                        <input class="input-cls" id="shipping_name" type="text" placeholder="@lang('Full Name')" name="shipping_name">
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="shipping_phone">@lang('Phone Number')</label>
                                        <input class="input-cls" id="shipping_phone" name="shipping_phone" type="tel" placeholder="@lang('Phone Number')">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="shipping_address">@lang('Address')</label>
                                        <input class="input-cls" id="shipping_address" name="shipping_address" type="text" placeholder="@lang('Address')">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="shipping_zip">@lang('Postal Code')</label>
                                        <input class="input-cls" id="shipping_zip" name="shipping_zip" type="text" placeholder="@lang('Postal Code')">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="shipping_city">@lang('City')</label>
                                        <input class="input-cls" id="shipping_city" name="shipping_city" type="text" placeholder="@lang('City')">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="shipping_state">@lang('State')</label>
                                        <input class="input-cls" id="shipping_state" name="shipping_state" type="text" placeholder="@lang('State')">
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="input-wrapper">
                                        <label class="label-cls">@lang('Select Country')</label>
                                        {{-- إزالة nice-select هنا أيضًا لتفادي أي ازدواجية --}}
                                        <select class="input-cls" name="shipping_country">
                                            @include('partials.user.countries')
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <div class="input-wrapper">
                                        <label class="label-cls" for="Order-Note">@lang('Order Note')</label>
                                        <input class="input-cls" id="Order-Note" name="order_notes" type="text" placeholder="@lang('Order note (Optional)')">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SUMMARY --}}
                    <div class="col-lg-5 col-xl-4 wow fadeInUp" data-wow-delay=".2s">
                        <div class="summary-box">
                            <h4 class="form-title">@lang('Summery')</h4>

                            <!-- Price Details -->
                            <div class="summary-inner-box">
                                <h6 class="summary-title">@lang('Price Details')</h6>
                                <div class="details-wrapper">
                                    <div class="price-details">
                                        <span>@lang('Total MRP')</span>
                                        <span class="right-side cart-total">
                                            {{ App\Models\Product::convertPrice($totalPrice) }}
                                        </span>
                                    </div>

                                    <div class="price-details tax_show">
                                        <span>@lang('Tax') (<span class="tax-percentage">0</span>%)</span>
                                        <span class="right-side tax-amount">{{ App\Models\Product::convertPrice(0) }}</span>
                                    </div>

                                    @if (!isset($is_vendor_checkout) || !$is_vendor_checkout)
                                        @if (Session::has('coupon'))
                                            <div class="price-details">
                                                <span>@lang('Discount')
                                                    <span class="dpercent">
                                                        {{ Session::get('coupon_percentage') == 0 ? '' : '(' . Session::get('coupon_percentage') . ')' }}
                                                    </span>
                                                </span>
                                                @if ($gs->currency_format == 0)
                                                    <span id="discount" class="right-side">{{ $curr->sign }}{{ Session::get('coupon') }}</span>
                                                @else
                                                    <span id="discount" class="right-side">{{ Session::get('coupon') }}{{ $curr->sign }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="price-details d-none">
                                                <span>@lang('Discount') <span class="dpercent"></span></span>
                                                <span id="discount" class="right-side">{{ $curr->sign }}{{ Session::get('coupon') }}</span>
                                            </div>
                                        @endif
                                    @endif

                                    @if ($digital == 0)
                                        <div class="price-details">
                                            <span>@lang('Shipping Cost')</span>
                                            <span class="right-side shipping_cost_view">{{ App\Models\Product::convertPrice(0) }}</span>
                                        </div>

                                        <div class="price-details">
                                            <span>@lang('Packaging Cost')</span>
                                            <span class="right-side packing_cost_view">{{ App\Models\Product::convertPrice(0) }}</span>
                                        </div>
                                    @endif
                                </div>

                                <hr>
                                <div class="final-price">
                                    <span>@lang('Final Price')</span>
                                    <span class="total-amount" id="final-cost">{{ App\Models\Product::convertPrice($totalPrice) }}</span>
                                </div>
                            </div>

                            <div class="summary-inner-box">
                                <div class="btn-wrappers">
                                    <button type="submit" class="template-btn w-100">
                                        @lang('Continue')
                                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24" fill="none">
                                            <g clip-path="url(#clip0_489_34176))">
                                                <path d="M23.62 9.9099L19.75 5.9999C19.657 5.90617 19.5464 5.83178 19.4246 5.78101C19.3027 5.73024 19.172 5.7041 19.04 5.7041C18.908 5.7041 18.7773 5.73024 18.6554 5.78101C18.5336 5.83178 18.423 5.90617 18.33 5.9999C18.1437 6.18726 18.0392 6.44071 18.0392 6.7049C18.0392 6.96909 18.1437 7.22254 18.33 7.4099L21.89 10.9999H1.5C1.23478 10.9999 0.98043 11.1053 0.792893 11.2928C0.605357 11.4803 0.5 11.7347 0.5 11.9999H0.5C0.5 12.2651 0.605357 12.5195 0.792893 12.707C0.98043 12.8945 1.23478 12.9999 1.5 12.9999H21.95L18.33 16.6099C18.2363 16.7029 18.1619 16.8135 18.1111 16.9353C18.0603 17.0572 18.0342 17.1879 18.0342 17.3199C18.0342 17.4519 18.0603 17.5826 18.1111 17.7045C18.1619 17.8263 18.2363 17.9369 18.33 18.0299C18.423 18.1236 18.5336 18.198 18.6554 18.2488C18.7773 18.2996 18.908 18.3257 19.04 18.3257C19.172 18.3257 19.3027 18.2996 19.4246 18.2488C19.5464 18.198 19.657 18.1236 19.75 18.0299L23.62 14.1499C24.1818 13.5874 24.4974 12.8249 24.4974 12.0299C24.4974 11.2349 24.1818 10.4724 23.62 9.9099Z" fill="white"/>
                                            </g>
                                        </svg>
                                    </button>
                                    <a href="{{ route('front.checkout') }}" class="template-btn dark-outline w-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24" fill="none">
                                            <g clip-path="url(#clip0_489_34179)">
                                                <path d="M1.38 9.9099L5.25 5.9999C5.34296 5.90617 5.45357 5.83178 5.57542 5.78101C5.69728 5.73024 5.82799 5.7041 5.96 5.7041C6.09201 5.7041 6.22272 5.73024 6.34458 5.78101C6.46643 5.83178 6.57704 5.90617 6.67 5.9999C6.85625 6.18726 6.96079 6.44071 6.96079 6.7049C6.96079 6.96909 6.85625 7.22254 6.67 7.4099L3.11 10.9999H23.5C23.7652 10.9999 24.0196 11.1053 24.2071 11.2928C24.3946 11.4803 24.5 11.7347 24.5 11.9999V11.9999C24.5 12.2651 24.3946 12.5195 24.2071 12.707C24.0196 12.8945 23.7652 12.9999 23.5 12.9999H3.05L6.67 16.6099C6.76373 16.7029 6.83812 16.8135 6.88889 16.9353C6.93966 17.0572 6.9658 17.1879 6.9658 17.3199C6.9658 17.4519 6.93966 17.5826 6.88889 17.7045C6.83812 17.8263 6.76373 17.9369 6.67 18.0299C6.57704 18.1236 6.46643 18.198 6.34458 18.2488C6.22272 18.2996 6.09201 18.3257 5.96 18.3257C5.82799 18.3257 5.69728 18.2996 5.57542 18.2488C5.45357 18.198 5.34296 18.1236 5.25 18.0299L1.38 14.1499C0.818197 13.5874 0.50264 12.8249 0.50264 12.0299C0.50264 11.2349 0.818197 10.4724 1.38 9.9099Z" fill="#030712"/>
                                            </g>
                                        </svg>
                                        @lang('Back to Previous Step')
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- HIDDEN INPUTS --}}
                <input type="hidden" name="dp" value="{{ $digital }}">
                <input type="hidden" id="input_tax" name="tax" value="">
                <input type="hidden" id="input_tax_type" name="tax_type" value="">
                <input type="hidden" name="totalQty" value="{{ $totalQty }}">
                <input type="hidden" name="vendor_shipping_id" value="{{ $vendor_shipping_id }}">
                <input type="hidden" name="vendor_packing_id" value="{{ $vendor_packing_id }}">
                <input type="hidden" name="currency_sign" value="{{ $curr->sign }}">
                <input type="hidden" name="currency_name" value="{{ $curr->name }}">
                <input type="hidden" name="currency_value" value="{{ $curr->value }}">

                {{-- Use vendor-specific totalPrice (already calculated by controller) --}}
                <input type="hidden" name="total" id="grandtotal" value="{{ round($totalPrice * $curr->value, 2) }}">
                <input type="hidden" id="tgrandtotal" value="{{ round($totalPrice * $curr->value, 2) }}">
                <input type="hidden" id="ttotal" value="{{ $totalPrice }}">

                <input type="hidden" id="original_tax" value="0">
                <input type="hidden" id="wallet-price" name="wallet_price" value="0">

                @if (!isset($is_vendor_checkout) || !$is_vendor_checkout)
                    <input type="hidden" name="coupon_code" id="coupon_code" value="{{ Session::has('coupon_code') ? Session::get('coupon_code') : '' }}">
                    <input type="hidden" name="coupon_discount" id="coupon_discount" value="{{ Session::has('coupon') ? Session::get('coupon') : '' }}">
                    <input type="hidden" name="coupon_id" id="coupon_id" value="{{ Session::has('coupon') ? Session::get('coupon_id') : '' }}">
                @else
                    <input type="hidden" name="coupon_code" id="coupon_code" value="">
                    <input type="hidden" name="coupon_discount" id="coupon_discount" value="">
                    <input type="hidden" name="coupon_id" id="coupon_id" value="">
                @endif

                <input type="hidden" name="user_id" id="user_id" value="{{ Auth::guard('web')->check() ? Auth::guard('web')->user()->id : '' }}">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">

            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    var options = { searchable: true };

    // نفعّل NiceSelect لعنصر الدولة مرة واحدة فقط (أزلنا class=nice-select من HTML)
    const countryEl = document.getElementById("select_country");
    if (countryEl && !countryEl.dataset.nsBound) {
        NiceSelect.bind(countryEl, options);
        countryEl.dataset.nsBound = "1";
    }
});
</script>
@endpush

@push('scripts')
<script type="text/javascript">
    $('a.payment:first').addClass('active');
    $('.checkoutform').attr('action', $('a.payment:first').attr('data-form'));
    $($('a.payment:first').attr('href')).load($('a.payment:first').data('href'));
    var show = $('a.payment:first').data('show');
    if (show != 'no') { $('.pay-area').removeClass('d-none'); } else { $('.pay-area').addClass('d-none'); }
    $($('a.payment:first').attr('href')).addClass('active').addClass('show');
</script>

<script type="text/javascript">
    var coup = 0;
    var pos  = {{ $gs->currency_format }};

    let mship = 0;
    let mpack = 0;

    var ftotal = parseFloat($('#grandtotal').val());
    ftotal = parseFloat(ftotal).toFixed(2);
    if (pos == 0) { $('#final-cost').html('{{ $curr->sign }}' + ftotal) }
    else          { $('#final-cost').html(ftotal + '{{ $curr->sign }}') }
    $('#grandtotal').val(ftotal);

    let original_tax = 0;

    // قيم المستخدم المسجّل (لتعبئة الدولة/الولاية/المدينة)
    var IS_LOGGED  = {{ Auth::check() ? 'true' : 'false' }};
    var SAVED_COUNTRY = {!! json_encode(Auth::check() ? (Auth::user()->country ?? '') : '') !!};
    var SAVED_STATEID = {!! json_encode(Auth::check() ? (Auth::user()->state_id ?? '') : '') !!};
    var SAVED_CITYID  = {!! json_encode(Auth::check() ? (Auth::user()->city_id ?? '') : '') !!};

    // تغيير الدولة
    $(document).on('change', '#select_country', function() {
        var options = { searchable: true };

        document.getElementById("show_state").innerHTML = '';
        document.getElementById("show_city").innerHTML  = '';

        let state_id   = 0;
        let country_id = $('#select_country option:selected').attr('data');
        let is_state   = $('option:selected', this).attr('rel');
        let is_auth    = $('option:selected', this).attr('rel1');
        let is_user    = $('option:selected', this).attr('rel5');
        let state_url  = $('option:selected', this).attr('data-href');

        if (!country_id) {
            $('.tax_show').addClass('d-none');
            $('.select_state, .select_city').addClass('d-none');
            return;
        }

        if (is_auth == 1 || is_state == 1) {
            if (is_state == 1) {
                $('.select_state').removeClass('d-none');
                $.get(state_url, function(response) {
                    $('#show_state').html(response.data);

                    // نفعّل NiceSelect للولاية مرة واحدة
                    const st = document.getElementById("show_state");
                    if (st && !st.dataset.nsBound) { NiceSelect.bind(st, options); st.dataset.nsBound="1"; }

                    // للمسجّل: اختَر الولاية المحفوظة إن وُجدت
                    if (IS_LOGGED && SAVED_STATEID) {
                        $('#show_state').val(String(SAVED_STATEID)).trigger('change');
                    } else if (is_user == 1) {
                        tax_submit(country_id, response.state);
                    } else {
                        tax_submit(country_id, state_id);
                    }
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

    // تغيير الولاية
    $(document).on('change', '#show_state', function() {
        let state_id   = $(this).val();
        let country_id = $('#select_country option:selected').attr('data');
        if (!country_id) return;

        $.get("{{ route('state.wise.city') }}", { state_id: state_id }, function(data) {
            $('#show_city').parent().parent().removeClass('d-none');
            $('#show_city').html(data.data);

            // اختيار المدينة المحفوظة (للمسجّل)
            if (IS_LOGGED && SAVED_CITYID) {
                $('#show_city').val(String(SAVED_CITYID));
            }
        });

        tax_submit(country_id, state_id);
    });

    function hide_state() {
        $('.select_state').addClass('d-none');
        $('.select_city').addClass('d-none');
    }

    $(document).ready(function() {
        // لا نربط NiceSelect للولاية هنا (نربطه بعد تحميل الخيارات)
        const $opt = $('#select_country option');

        // للمسجّل: اضبط الدولة المحفوظة ثم فعّل سلسلة التهيئة (تحميل الولايات/المدن)
        if (IS_LOGGED && SAVED_COUNTRY) {
            // حاول المطابقة على value أو النص
            let matched = false;
            $opt.each(function(){
                if ($(this).val() === SAVED_COUNTRY || $(this).text().trim().toUpperCase() === String(SAVED_COUNTRY).trim().toUpperCase()) {
                    $(this).prop('selected', true);
                    matched = true;
                    return false;
                }
            });
            if (matched) { $('#select_country').trigger('change'); }
        } else {
            // ضيف: إظهار الضريبة مع قيمة افتراضية 0
            $('.tax_show').removeClass('d-none');
            $('.original_tax').html('0%');
        }
    });

    // استدعاء حساب الضريبة من السيرفر
    function tax_submit(country_id, state_id) {
        if (!country_id) return;

        $('.gocover').show();
        var total = $("#ttotal").val();
        var ship  = 0;  // في step1 لا يوجد شحن بعد

        $.ajax({
            type: "GET",
            url: mainurl + "/country/tax/check",
            data: { state_id: state_id, country_id: country_id, total: total, shipping_cost: ship },
            success: function(data) {
                var taxPct = parseFloat(data[1]) || 0;
                $('#original_tax').val(taxPct);
                $('.tax_show').removeClass('d-none');
                $('#input_tax').val(data[11]);
                $('#input_tax_type').val(data[12]);

                // حساب مبلغ الضريبة بناءً على النسبة والقاعدة الضريبية
                var subtotal = parseFloat($("#ttotal").val()) || 0;
                var discountAmt = parseFloat($('#discount').text().replace(/[^0-9.-]/g, '')) || 0;
                var taxableBase = Math.max(0, subtotal - discountAmt);
                var taxAmt = (taxableBase * taxPct / 100);

                // عرض الضريبة بالتنسيق الموحد: Tax (15%) ثم المبلغ
                $('.tax-percentage').text(taxPct);
                var taxAmtWithSign = (pos == 0) ? '{{ $curr->sign }}' + taxAmt.toFixed(2) : taxAmt.toFixed(2) + '{{ $curr->sign }}';
                $('.tax-amount').html(taxAmtWithSign);

                // حساب الإجمالي النهائي
                var finalTotal = taxableBase + taxAmt + parseFloat(mship) + parseFloat(mpack);
                $('#grandtotal').val(finalTotal.toFixed(2));
                $('#tgrandtotal').val(finalTotal.toFixed(2));

                if (pos == 0) {
                    $('#final-cost').html('{{ $curr->sign }}' + finalTotal.toFixed(2));
                    $('.total-amount').html('{{ $curr->sign }}' + finalTotal.toFixed(2));
                } else {
                    $('#final-cost').html(finalTotal.toFixed(2) + '{{ $curr->sign }}');
                    $('.total-amount').html(finalTotal.toFixed(2) + '{{ $curr->sign }}');
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

    // Google Maps Location Picker Integration
    let locationPicker;

    function initLocationPicker() {
        locationPicker = new GoogleMapsLocationPicker({
            containerId: 'map-picker-container',
            mapId: 'location-map',
            onLocationSelect: function(data) {
                // Update form fields
                $('#select_country').val(data.country.id).trigger('change');
                $('#address').val(data.address.ar || data.address.en);
                $('#latitude').val(data.coordinates.latitude);
                $('#longitude').val(data.coordinates.longitude);

                // Show selected location info
                $('#location-info-display').addClass('show');
                $('#confirm-location-btn').prop('disabled', false);
            }
        });

        locationPicker.init();
    }

    // Initialize when modal is shown
    $('#google-maps-modal').on('shown.bs.modal', function() {
        if (!locationPicker) {
            initLocationPicker();
        }
    });

    // Confirm location button
    $(document).on('click', '#confirm-location-btn', function() {
        const location = locationPicker.getSelectedLocation();
        if (location) {
            toastr.success('@lang("Location selected successfully!")');
            $('#google-maps-modal').modal('hide');
        }
    });

    // Reset button
    $(document).on('click', '#reset-location-btn', function() {
        if (locationPicker) {
            locationPicker.reset();
            $('#confirm-location-btn').prop('disabled', true);
        }
    });
</script>
@endpush

{{-- Google Maps Modal --}}
<div class="modal fade" id="mapModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h5 class="modal-title">اختيار الموقع على الخريطة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="alert-container-modal" style="padding: 15px;"></div>

                <div style="padding: 20px;">
                    <div id="map-container" style="position: relative; height: 500px; border-radius: 8px; overflow: hidden; border: 2px solid #e0e0e0;">
                        <div class="map-search" style="position: absolute; top: 10px; right: 10px; left: 10px; z-index: 10;">
                            <input type="text" id="map-search-input" placeholder="ابحث عن عنوان..."
                                   style="width: 100%; padding: 12px 15px; border: 2px solid #667eea; border-radius: 8px; font-size: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); background: white;">
                        </div>
                        <div id="map" style="width: 100%; height: 100%;"></div>
                        <div class="loading-overlay" id="loading-overlay-modal" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.9); display: none; align-items: center; justify-content: center; z-index: 20;">
                            <div class="spinner" style="border: 3px solid #f3f3f3; border-top: 3px solid #667eea; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite;"></div>
                        </div>
                    </div>

                    <div class="buttons-container" style="display: flex; gap: 10px; margin-top: 15px;">
                        <button class="btn btn-secondary" id="reset-btn-modal" type="button">
                            إعادة تحديد
                        </button>
                        <button class="btn btn-secondary" id="current-location-btn-modal" type="button">
                            موقعي الحالي
                        </button>
                    </div>

                    <div class="location-info" id="location-info-modal" style="display: none; background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <h6 style="font-size: 16px; color: #333; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">معلومات الموقع المحدد</h6>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                            <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">الدولة (عربي)</label>
                                <div id="country-ar-modal" style="font-size: 14px; color: #333;">-</div>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">الدولة (إنجليزي)</label>
                                <div id="country-en-modal" style="font-size: 14px; color: #333;">-</div>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">المنطقة (عربي)</label>
                                <div id="state-ar-modal" style="font-size: 14px; color: #333;">-</div>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">المنطقة (إنجليزي)</label>
                                <div id="state-en-modal" style="font-size: 14px; color: #333;">-</div>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">المدينة (عربي)</label>
                                <div id="city-ar-modal" style="font-size: 14px; color: #333;">-</div>
                            </div>
                            <div style="background: white; padding: 15px; border-radius: 6px; border-right: 3px solid #667eea;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">المدينة (إنجليزي)</label>
                                <div id="city-en-modal" style="font-size: 14px; color: #333;">-</div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 15px; margin-top: 15px;">
                            <div style="flex: 1; background: white; padding: 15px; border-radius: 6px; text-align: center;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">خط العرض</label>
                                <div id="latitude-value-modal" style="font-size: 16px; font-weight: 600; color: #667eea;">-</div>
                            </div>
                            <div style="flex: 1; background: white; padding: 15px; border-radius: 6px; text-align: center;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px;">خط الطول</label>
                                <div id="longitude-value-modal" style="font-size: 16px; font-weight: 600; color: #667eea;">-</div>
                            </div>
                        </div>

                        <div style="margin-top: 15px;">
                            <div style="background: white; padding: 15px; border-radius: 6px;">
                                <label style="display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: 600;">العنوان الكامل</label>
                                <div id="full-address-modal" style="font-size: 14px; color: #333;">-</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Close')</button>
                <button type="button" class="btn btn-primary" id="use-location-btn-modal" disabled>استخدم هذا الموقع</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.loading-overlay.active {
    display: flex !important;
}
</style>
@endpush

@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&libraries=places&language=ar" async defer></script>
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

        const response = await fetch('/api/geocoding/reverse', {
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
            showAlertModal('تم تحديد الموقع بنجاح', 'success');
        } else {
            showAlertModal('فشل في الحصول على معلومات الموقع: ' + (result.error || 'خطأ غير معروف'), 'error');
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
}

// Use selected location - populate form fields
function useLocation() {
    if (!selectedLocationData) return;

    // Update hidden latitude/longitude fields
    $('#latitude').val(selectedLocationData.coordinates?.latitude || '');
    $('#longitude').val(selectedLocationData.coordinates?.longitude || '');

    // Update address field
    const fullAddress = selectedLocationData.address?.ar || selectedLocationData.address?.en || '';
    $('#address').val(fullAddress);

    // Get IDs from API response
    const countryId = selectedLocationData.country?.id;
    const stateId = selectedLocationData.state?.id;
    const cityId = selectedLocationData.city?.id;

    if (!countryId) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('لم يتم العثور على معرّف الدولة');
        }
        $('#mapModal').modal('hide');
        return;
    }

    // Step 1: Find and select country by ID
    selectCountryById(countryId, stateId, cityId);
}

// Select country by ID and trigger cascade
function selectCountryById(countryId, stateId, cityId) {
    let countryFound = false;

    $('#select_country option').each(function() {
        const optionCountryId = $(this).attr('data'); // data attribute contains country ID

        if (optionCountryId && parseInt(optionCountryId) === parseInt(countryId)) {
            $(this).prop('selected', true);
            countryFound = true;

            // Update NiceSelect display text only
            updateNiceSelectDisplay('select_country', $(this).text());

            // Trigger change to load states via AJAX
            $('#select_country').trigger('change');

            // Wait for states to load, then select state
            if (stateId) {
                waitAndSelectState(stateId, cityId);
            } else {
                // No state, show success and close
                showFinalSuccessMessage();
            }

            return false; // break loop
        }
    });

    if (!countryFound) {
        if (typeof toastr !== 'undefined') {
            toastr.warning('لم يتم العثور على الدولة في القائمة');
        }
        $('#mapModal').modal('hide');
    }
}

// Wait for states to load via AJAX, then select by ID
function waitAndSelectState(stateId, cityId) {
    let attempts = 0;
    const maxAttempts = 50; // 5 seconds max

    const checkStatesInterval = setInterval(() => {
        attempts++;

        const stateOptions = $('#show_state option');

        if (stateOptions.length > 0) {
            clearInterval(checkStatesInterval);

            let stateFound = false;

            stateOptions.each(function() {
                const optionValue = $(this).val();

                // Match by state ID (value contains state ID)
                if (optionValue && parseInt(optionValue) === parseInt(stateId)) {
                    $(this).prop('selected', true);
                    stateFound = true;

                    // Update NiceSelect display text only
                    updateNiceSelectDisplay('show_state', $(this).text());

                    // Trigger change to load cities via AJAX
                    $('#show_state').trigger('change');

                    // Wait for cities to load, then select city
                    if (cityId) {
                        waitAndSelectCity(cityId);
                    } else {
                        // No city, show success and close
                        showFinalSuccessMessage();
                    }

                    return false; // break loop
                }
            });

            if (!stateFound) {
                // State not found, but still show success
                showFinalSuccessMessage();
            }
        } else if (attempts >= maxAttempts) {
            // Timeout waiting for states
            clearInterval(checkStatesInterval);
            showFinalSuccessMessage();
        }
    }, 100); // Check every 100ms
}

// Wait for cities to load via AJAX, then select by name
// Note: City options use city_name as value (not ID) in the current system
function waitAndSelectCity(cityId) {
    let attempts = 0;
    const maxAttempts = 50; // 5 seconds max

    // Get city names from API response for matching
    const cityNameEn = selectedLocationData.city?.name || '';
    const cityNameAr = selectedLocationData.city?.name_ar || '';

    const checkCitiesInterval = setInterval(() => {
        attempts++;

        const cityOptions = $('#show_city option');

        if (cityOptions.length > 0) {
            clearInterval(checkCitiesInterval);

            let cityFound = false;

            cityOptions.each(function() {
                const optionValue = $(this).val();
                const optionText = $(this).text().trim();

                // Match by city name (value contains city_name, not ID)
                if (optionValue && (
                    optionValue.toLowerCase() === cityNameEn.toLowerCase() ||
                    optionValue.toLowerCase() === cityNameAr.toLowerCase() ||
                    optionText.toLowerCase() === cityNameEn.toLowerCase() ||
                    optionText.toLowerCase() === cityNameAr.toLowerCase()
                )) {
                    $(this).prop('selected', true);
                    cityFound = true;

                    // Update NiceSelect display text only
                    updateNiceSelectDisplay('show_city', $(this).text());

                    return false; // break loop
                }
            });

            // Show final success message (whether city found or not)
            showFinalSuccessMessage();
        } else if (attempts >= maxAttempts) {
            // Timeout waiting for cities
            clearInterval(checkCitiesInterval);
            showFinalSuccessMessage();
        }
    }, 100); // Check every 100ms
}

// Update NiceSelect display text without re-initializing
function updateNiceSelectDisplay(selectId, displayText) {
    const selectElement = document.getElementById(selectId);
    if (!selectElement) return;

    // Find the NiceSelect wrapper
    const niceSelectWrapper = selectElement.nextElementSibling;
    if (niceSelectWrapper && niceSelectWrapper.classList.contains('nice-select')) {
        const currentSpan = niceSelectWrapper.querySelector('.current');
        if (currentSpan) {
            currentSpan.textContent = displayText;
        }
    }
}

// Show final success message once
function showFinalSuccessMessage() {
    if (typeof toastr !== 'undefined') {
        toastr.success('تم حفظ الموقع بنجاح! تم تعبئة جميع الحقول تلقائياً');
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
</script>
@endpush
