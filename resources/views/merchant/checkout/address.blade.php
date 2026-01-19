@extends('layouts.front')

@section('content')
    {{-- Breadcrumb --}}
    <section class="gs-breadcrumb-section">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-name">@lang('Checkout')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('merchant-cart.index') }}">@lang('Merchant Cart')</a></li>
                        <li>@lang('Address')</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <div class="gs-checkout-wrapper muaadh-section-gray">
        <div class="container">
            {{-- Step Indicator --}}
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="checkout-step-wrapper">
                        <span class="line"></span>
                        <div class="single-step active">
                            <span class="step-btn">1</span>
                            <span class="step-txt">@lang('Address')</span>
                        </div>
                        <div class="single-step">
                            <span class="step-btn">2</span>
                            <span class="step-txt">@lang('Shipping')</span>
                        </div>
                        <div class="single-step">
                            <span class="step-btn">3</span>
                            <span class="step-txt">@lang('Payment')</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Address Form --}}
            <form id="address-form" class="address-wrapper">
                @csrf
                <div class="row gy-4">
                    <div class="col-lg-7 col-xl-8">
                        <div class="m-card">
                            <div class="m-card__header">
                                <h4 class="m-0">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    @lang('Delivery Location')
                                </h4>
                            </div>
                            <div class="m-card__body">
                                {{-- Map Selection Button --}}
                                <div class="mb-4">
                                    <div class="m-alert m-alert--info mb-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        @lang('Please select your delivery location from the map')
                                    </div>
                                    <button type="button" class="m-btn m-btn--primary m-btn--lg w-100" id="open-map-btn" data-bs-toggle="modal" data-bs-target="#mapModal">
                                        <i class="fas fa-map-marker-alt me-2"></i> @lang('Select Location from Map')
                                    </button>
                                    <div id="selected-location-info" class="m-alert m-alert--success d-none mt-3">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <span id="location-text"></span>
                                    </div>
                                </div>

                                {{-- Order Note Only --}}
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">@lang('Order Note') (@lang('Optional'))</label>
                                        <textarea name="purchase_note" class="form-control" rows="2" placeholder="@lang('Special instructions for delivery...')">{{ $address['purchase_note'] ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Order Summary --}}
                    <div class="col-lg-5 col-xl-4">
                        <div class="summary-box">
                            <h4 class="form-name">@lang('Order Summary')</h4>

                            <div class="summary-inner-box">
                                <ul class="summary-list">
                                    <li>
                                        <span>@lang('Subtotal') ({{ $cart['total_qty'] ?? 0 }})</span>
                                        <span id="summary-subtotal">{{ $curr->sign ?? '' }}{{ number_format($cart['total_price'] ?? 0, 2) }}</span>
                                    </li>
                                    <li id="tax-row" class="d-none">
                                        <span>@lang('Tax') (<span id="tax-rate">0</span>%)</span>
                                        <span id="summary-tax">{{ $curr->sign ?? '' }}0.00</span>
                                    </li>
                                </ul>
                                <div class="total-cost">
                                    <span>@lang('Total')</span>
                                    <span id="summary-total">{{ $curr->sign ?? '' }}{{ number_format($cart['total_price'] ?? 0, 2) }}</span>
                                </div>
                            </div>

                            <div class="summary-inner-box">
                                <div class="btn-wrappers">
                                    <button type="submit" class="template-btn w-100" id="submit-btn">
                                        @lang('Continue to Shipping')
                                        <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                    <a href="{{ route('merchant-cart.index') }}" class="template-btn dark-outline w-100 mt-2">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        @lang('Back to Cart')
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Hidden Fields - Location --}}
                <input type="hidden" name="latitude" id="latitude" value="{{ $address['latitude'] ?? '' }}">
                <input type="hidden" name="longitude" id="longitude" value="{{ $address['longitude'] ?? '' }}">
                <input type="hidden" name="customer_country" id="customer_country" value="{{ $address['customer_country'] ?? '' }}">
                <input type="hidden" name="customer_city" id="customer_city" value="{{ $address['customer_city'] ?? '' }}">
                <input type="hidden" name="customer_state" id="customer_state" value="{{ $address['customer_state'] ?? '' }}">
                <input type="hidden" name="country_id" id="country_id" value="{{ $address['country_id'] ?? '' }}">
                <input type="hidden" name="state_id" id="state_id" value="{{ $address['state_id'] ?? '' }}">
                <input type="hidden" name="city_id" id="city_id" value="{{ $address['city_id'] ?? '' }}">

                {{-- Hidden Fields - Customer Info (from authenticated user) --}}
                <input type="hidden" name="customer_name" id="customer_name" value="{{ $address['customer_name'] ?? (auth()->user()->name ?? '') }}">
                <input type="hidden" name="customer_email" id="customer_email" value="{{ $address['customer_email'] ?? (auth()->user()->email ?? '') }}">
                <input type="hidden" name="customer_phone" id="customer_phone" value="{{ $address['customer_phone'] ?? (auth()->user()->phone ?? '') }}">
                <input type="hidden" name="customer_zip" id="customer_zip" value="{{ $address['customer_zip'] ?? '' }}">
                <input type="hidden" name="customer_address" id="customer_address" value="{{ $address['customer_address'] ?? '' }}">
            </form>
        </div>
    </div>

    {{-- Map Modal --}}
    @include('merchant.checkout.partials.map-modal')
@endsection

@section('script')
<script>
const branchId = {{ $branch_id }};
const apiBaseUrl = '/branch/' + branchId + '/checkout';

// Calculate tax when location is selected
function calculateTax() {
    const countryId = $('#country_id').val() || 0;
    const stateId = $('#state_id').val() || 0;

    if (!countryId && !stateId) {
        return;
    }

    $.ajax({
        url: apiBaseUrl + '/calculate-tax',
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            country_id: countryId,
            state_id: stateId
        },
        success: function(response) {
            if (response.success) {
                if (response.tax_rate > 0) {
                    $('#tax-rate').text(response.tax_rate);
                    $('#summary-tax').text(response.formatted.tax_amount);
                    $('#summary-total').text(response.formatted.total);
                    $('#tax-row').removeClass('d-none');
                } else {
                    $('#tax-row').addClass('d-none');
                    $('#summary-total').text(response.formatted.subtotal);
                }
            }
        }
    });
}

// Listen for location changes (will be called from map-script)
$(document).on('locationSelected', function(e, data) {
    if (data.country_id) {
        $('#country_id').val(data.country_id);
    }
    if (data.state_id) {
        $('#state_id').val(data.state_id);
    }
    if (data.city_id) {
        $('#city_id').val(data.city_id);
    }
    calculateTax();
});

// Form Submission via AJAX
$('#address-form').on('submit', function(e) {
    e.preventDefault();

    // Validate location is selected
    if (!$('#latitude').val() || !$('#longitude').val()) {
        toastr.error('@lang("Please select your location from the map")');
        return;
    }

    const btn = $('#submit-btn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> @lang("Processing...")');

    $.ajax({
        url: apiBaseUrl + '/address',
        method: 'POST',
        data: $(this).serialize(),
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                window.location.href = response.redirect || (apiBaseUrl + '/shipping');
            } else {
                toastr.error(response.message || '@lang("Something went wrong")');
                btn.prop('disabled', false).html('@lang("Continue to Shipping") <i class="fas fa-arrow-right ms-2"></i>');
            }
        },
        error: function(xhr) {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                Object.values(errors).forEach(function(err) {
                    toastr.error(err[0]);
                });
            } else {
                toastr.error(xhr.responseJSON?.message || '@lang("Something went wrong")');
            }
            btn.prop('disabled', false).html('@lang("Continue to Shipping") <i class="fas fa-arrow-right ms-2"></i>');
        }
    });
});
</script>

{{-- Map Script --}}
@if(!empty($googleMapsApiKey))
<script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&libraries=places&language={{ app()->getLocale() == 'ar' ? 'ar' : 'en' }}"></script>
@include('merchant.checkout.partials.map-script')
@endif
@endsection
