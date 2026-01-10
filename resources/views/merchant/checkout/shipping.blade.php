@extends('layouts.front')

@section('content')
    {{-- Breadcrumb --}}
    <section class="gs-breadcrumb-section">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Checkout')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('front.cart') }}">@lang('Cart')</a></li>
                        <li>@lang('Shipping')</li>
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
                        <span class="line-2"></span>
                        <div class="single-step active">
                            <span class="step-btn"><i class="fas fa-check"></i></span>
                            <span class="step-txt">@lang('Address')</span>
                        </div>
                        <div class="single-step active">
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

            <form id="shipping-form">
                @csrf
                <div class="row gy-4">
                    <div class="col-lg-7 col-xl-8">
                        {{-- Billing Address Summary --}}
                        <div class="m-card mb-4">
                            <div class="m-card__header d-flex justify-content-between align-items-center">
                                <h5 class="m-0">@lang('Delivery Address')</h5>
                                <a href="{{ route('merchant.checkout.address', $merchant_id) }}" class="m-btn m-btn--sm m-btn--outline">
                                    <i class="fas fa-edit me-1"></i> @lang('Edit')
                                </a>
                            </div>
                            <div class="m-card__body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2"><i class="fas fa-user me-2 text-muted"></i> {{ $address['customer_name'] ?? '-' }}</li>
                                    <li class="mb-2"><i class="fas fa-map-marker-alt me-2 text-muted"></i> {{ $address['customer_address'] ?? '-' }}</li>
                                    <li class="mb-2"><i class="fas fa-phone me-2 text-muted"></i> {{ $address['customer_phone'] ?? '-' }}</li>
                                    <li><i class="fas fa-envelope me-2 text-muted"></i> {{ $address['customer_email'] ?? '-' }}</li>
                                </ul>
                            </div>
                        </div>

                        {{-- Shipping Providers Buttons --}}
                        <div class="m-card mb-4">
                            <div class="m-card__header">
                                <h5 class="m-0">
                                    <i class="fas fa-truck me-2"></i>
                                    @lang('Shipping Method')
                                </h5>
                            </div>
                            <div class="m-card__body">
                                @if(!empty($shipping_providers) && count($shipping_providers) > 0)
                                <div class="row g-3">
                                    @foreach($shipping_providers as $providerData)
                                    <div class="col-md-6">
                                        <button type="button" class="m-btn m-btn--outline w-100 d-flex align-items-center justify-content-between provider-btn"
                                                id="provider-btn-{{ $providerData['provider'] }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modal_{{ $providerData['provider'] }}_{{ $merchant_id }}"
                                                data-provider="{{ $providerData['provider'] }}">
                                            <span>
                                                <i class="{{ $providerData['icon'] }} me-2"></i>
                                                {{ $providerData['label'] }}
                                            </span>
                                            <span class="provider-selected-text text-muted" id="provider-text-{{ $providerData['provider'] }}">
                                                @lang('Select')
                                            </span>
                                        </button>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <div class="m-alert m-alert--warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    @lang('No shipping methods available')
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Packaging Button --}}
                        @if(!empty($packaging) && count($packaging) > 0)
                        <div class="m-card mb-4">
                            <div class="m-card__header">
                                <h5 class="m-0">
                                    <i class="fas fa-box me-2"></i>
                                    @lang('Packaging')
                                </h5>
                            </div>
                            <div class="m-card__body">
                                <button type="button" class="m-btn m-btn--outline w-100 d-flex align-items-center justify-content-between"
                                        id="packaging-btn" data-bs-toggle="modal" data-bs-target="#packagingModal">
                                    <span>
                                        <i class="fas fa-gift me-2"></i>
                                        @lang('Select Packaging')
                                    </span>
                                    <span id="packaging-selected-text" class="text-muted">@lang('Optional')</span>
                                </button>
                            </div>
                        </div>
                        @endif

                        {{-- Courier Options (Local Delivery) --}}
                        @if(!empty($couriers) && count($couriers) > 0)
                        <div class="m-card">
                            <div class="m-card__header">
                                <h5 class="m-0">
                                    <i class="fas fa-motorcycle me-2"></i>
                                    @lang('Local Courier Delivery')
                                    <small class="text-muted ms-2">(@lang('Alternative to shipping'))</small>
                                </h5>
                            </div>
                            <div class="m-card__body">
                                <div class="row g-3">
                                    @foreach($couriers as $courier)
                                    <div class="col-md-6">
                                        <div class="form-check p-3 border rounded courier-option" data-price="{{ $courier['delivery_fee'] }}">
                                            <input class="form-check-input" type="radio" name="courier_option" id="courier_{{ $courier['courier_id'] }}"
                                                   value="{{ $courier['courier_id'] }}" data-price="{{ $courier['delivery_fee'] }}"
                                                   data-service-area="{{ $courier['service_area_id'] }}"
                                                   data-name="{{ $courier['courier_name'] }}">
                                            <label class="form-check-label w-100" for="courier_{{ $courier['courier_id'] }}">
                                                <div class="d-flex align-items-center">
                                                    @if(!empty($courier['courier_photo']))
                                                    <img src="{{ asset('assets/images/' . $courier['courier_photo']) }}" alt="" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                    @else
                                                    <div class="rounded-circle bg-secondary me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                    @endif
                                                    <div>
                                                        <strong>{{ $courier['courier_name'] }}</strong>
                                                        @if(!empty($courier['courier_phone']))
                                                        <br><small class="text-muted"><i class="fas fa-phone me-1"></i>{{ $courier['courier_phone'] }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                                <span class="float-end text-success fw-bold">
                                                    {{ $curr->sign ?? '' }}{{ number_format($courier['delivery_fee'], 2) }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Order Summary --}}
                    <div class="col-lg-5 col-xl-4">
                        <div class="summary-box">
                            <h4 class="form-title">@lang('Order Summary')</h4>

                            <div class="summary-inner-box">
                                <ul class="summary-list">
                                    <li>
                                        <span>@lang('Subtotal') ({{ $cart['total_qty'] ?? 0 }})</span>
                                        <span id="summary-subtotal">{{ $curr->sign ?? '' }}{{ number_format($cart['total_price'] ?? 0, 2) }}</span>
                                    </li>
                                    @if(($totals['discount_amount'] ?? 0) > 0)
                                    <li class="text-success">
                                        <span>@lang('Discount')</span>
                                        <span>-{{ $curr->sign ?? '' }}{{ number_format($totals['discount_amount'] ?? 0, 2) }}</span>
                                    </li>
                                    @endif
                                    <li class="shipping-row">
                                        <span>@lang('Shipping')</span>
                                        <span id="summary-shipping">@lang('Select method')</span>
                                    </li>
                                    <li class="packing-row d-none">
                                        <span>@lang('Packaging')</span>
                                        <span id="summary-packing">-</span>
                                    </li>
                                    @if(($totals['tax_amount'] ?? 0) > 0)
                                    <li>
                                        <span>@lang('Tax') ({{ $totals['tax_rate'] ?? 0 }}%)</span>
                                        <span>{{ $curr->sign ?? '' }}{{ number_format($totals['tax_amount'] ?? 0, 2) }}</span>
                                    </li>
                                    @endif
                                </ul>
                                <div class="total-cost">
                                    <span>@lang('Total')</span>
                                    <span id="summary-total">{{ $curr->sign ?? '' }}{{ number_format($totals['grand_total'] ?? $cart['total_price'] ?? 0, 2) }}</span>
                                </div>
                            </div>

                            <div class="summary-inner-box">
                                <div class="btn-wrappers">
                                    <button type="submit" class="template-btn w-100" id="submit-btn" disabled>
                                        @lang('Continue to Payment')
                                        <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                    <a href="{{ route('merchant.checkout.address', $merchant_id) }}" class="template-btn dark-outline w-100 mt-2">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        @lang('Back')
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Hidden Fields --}}
                <input type="hidden" name="delivery_type" id="delivery_type" value="shipping">
                <input type="hidden" name="shipping_id" id="selected_shipping_id" value="">
                <input type="hidden" name="shipping_cost" id="selected_shipping_cost" value="0">
                <input type="hidden" name="shipping_provider" id="selected_shipping_provider" value="">
                <input type="hidden" name="packing_id" id="selected_packing_id" value="">
                <input type="hidden" name="packing_cost" id="selected_packing_cost" value="0">
                <input type="hidden" name="courier_id" id="selected_courier_id" value="">
                <input type="hidden" name="courier_fee" id="selected_courier_fee" value="0">
            </form>
        </div>
    </div>

    {{-- Provider Modals - Dynamic for each provider --}}
    @if(!empty($shipping_providers))
        @foreach($shipping_providers as $providerData)
            @if($providerData['is_api'] ?? false)
                {{-- API Provider Modal (e.g., Tryoto) - Loads from API --}}
                <div class="modal fade gs-modal api-provider-modal" id="modal_{{ $providerData['provider'] }}_{{ $merchant_id }}" data-provider="{{ $providerData['provider'] }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="{{ $providerData['icon'] }} me-2"></i>
                                    {{ $providerData['label'] }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="api-options-{{ $providerData['provider'] }}" class="shipping-options-list api-options-container" data-provider="{{ $providerData['provider'] }}">
                                    <div class="text-center py-4">
                                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                        <p class="mt-2 text-muted">@lang('Loading shipping options...')</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- Regular Provider Modal - Data from DB --}}
                <div class="modal fade gs-modal" id="modal_{{ $providerData['provider'] }}_{{ $merchant_id }}" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="{{ $providerData['icon'] }} me-2"></i>
                                    {{ $providerData['label'] }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="shipping-options-list">
                                    @if(!empty($providerData['methods']))
                                        @foreach($providerData['methods'] as $method)
                                        <div class="form-check p-3 border rounded mb-2 shipping-option" data-price="{{ $method['price'] }}">
                                            <input class="form-check-input" type="radio" name="shipping_option"
                                                   id="ship_{{ $method['id'] }}" value="{{ $method['id'] }}"
                                                   data-price="{{ $method['final_price'] }}"
                                                   data-original-price="{{ $method['price'] }}"
                                                   data-title="{{ $method['title'] }}"
                                                   data-provider="{{ $providerData['provider'] }}"
                                                   data-free-above="{{ $method['free_above'] }}"
                                                   data-is-free="{{ $method['is_free'] ? '1' : '0' }}">
                                            <label class="form-check-label w-100 d-flex justify-content-between align-items-center" for="ship_{{ $method['id'] }}">
                                                <div>
                                                    <strong>{{ $method['title'] }}</strong>
                                                    @if(!empty($method['subtitle']))
                                                    <br><small class="text-muted">{{ $method['subtitle'] }}</small>
                                                    @endif
                                                    @if($method['free_above'] > 0)
                                                    <br><small class="text-success">
                                                        <i class="fas fa-gift me-1"></i>
                                                        @lang('Free if order above') {{ $curr->sign ?? '' }}{{ number_format($method['free_above'], 2) }}
                                                    </small>
                                                    @endif
                                                </div>
                                                <span class="text-success fw-bold">
                                                    @if($method['is_free'])
                                                        <span class="text-decoration-line-through text-muted me-1">{{ $curr->sign ?? '' }}{{ number_format($method['price'], 2) }}</span>
                                                        @lang('Free')
                                                    @elseif($method['price'] > 0)
                                                        {{ $curr->sign ?? '' }}{{ number_format($method['price'], 2) }}
                                                    @else
                                                        @lang('Free')
                                                    @endif
                                                </span>
                                            </label>
                                        </div>
                                        @endforeach
                                    @else
                                        <div class="m-alert m-alert--info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            @lang('No options available')
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endif

    {{-- Packaging Modal --}}
    @if(!empty($packaging) && count($packaging) > 0)
    <div class="modal fade gs-modal" id="packagingModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-box me-2"></i>
                        @lang('Select Packaging')
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="packaging-options">
                        @foreach($packaging as $pack)
                        <div class="form-check p-3 border rounded mb-2 packaging-option" data-price="{{ $pack['price'] }}">
                            <input class="form-check-input" type="radio" name="packaging_option"
                                   id="pack_{{ $pack['id'] }}" value="{{ $pack['id'] }}"
                                   data-price="{{ $pack['price'] }}"
                                   data-title="{{ $pack['title'] }}">
                            <label class="form-check-label w-100 d-flex justify-content-between align-items-center" for="pack_{{ $pack['id'] }}">
                                <div>
                                    <strong>{{ $pack['title'] }}</strong>
                                    @if(!empty($pack['subtitle']))
                                    <br><small class="text-muted">{{ $pack['subtitle'] }}</small>
                                    @endif
                                </div>
                                <span class="text-success fw-bold">
                                    @if($pack['price'] > 0)
                                        {{ $curr->sign ?? '' }}{{ number_format($pack['price'], 2) }}
                                    @else
                                        @lang('Free')
                                    @endif
                                </span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

@section('script')
<script>
const merchantId = {{ $merchant_id }};
const apiBaseUrl = '/merchant/' + merchantId + '/checkout';
const currencySign = '{{ $curr->sign ?? "" }}';
const currencyFormat = {{ $gs->currency_format ?? 0 }};
let baseTotal = {{ $totals['subtotal'] ?? $cart['total_price'] ?? 0 }};
let taxAmount = {{ $totals['tax_amount'] ?? 0 }};
// Track which API providers have been loaded
let apiProvidersLoaded = {};

// Format price
function formatPrice(amount) {
    const formatted = parseFloat(amount).toFixed(2);
    return currencyFormat === 0 ? currencySign + formatted : formatted + currencySign;
}

// Update summary totals
function updateSummary() {
    const deliveryType = $('#delivery_type').val();
    let deliveryCost = 0;

    if (deliveryType === 'local_courier') {
        deliveryCost = parseFloat($('#selected_courier_fee').val()) || 0;
    } else {
        deliveryCost = parseFloat($('#selected_shipping_cost').val()) || 0;
    }

    const packingCost = parseFloat($('#selected_packing_cost').val()) || 0;
    const grandTotal = baseTotal + taxAmount + deliveryCost + packingCost;

    $('#summary-shipping').text(formatPrice(deliveryCost));

    if (packingCost > 0) {
        $('.packing-row').removeClass('d-none');
        $('#summary-packing').text(formatPrice(packingCost));
    } else {
        $('.packing-row').addClass('d-none');
    }

    $('#summary-total').text(formatPrice(grandTotal));
}

// Check if can submit
function checkSubmitBtn() {
    const hasShipping = $('#selected_shipping_id').val();
    const hasCourier = $('#selected_courier_id').val();
    $('#submit-btn').prop('disabled', !hasShipping && !hasCourier);
}

// Reset all provider buttons except selected
function resetProviderButtons(exceptProvider) {
    $('.provider-btn').each(function() {
        const provider = $(this).data('provider');
        if (provider !== exceptProvider) {
            $('#provider-text-' + provider).text('@lang("Select")').removeClass('text-success').addClass('text-muted');
            $(this).removeClass('m-btn--success-outline').addClass('m-btn--outline');
        }
    });
}

// Load API provider options (e.g., Tryoto)
function loadApiProviderOptions(provider) {
    if (apiProvidersLoaded[provider]) return;

    const container = $('#api-options-' + provider);
    if (!container.length) return;

    // Currently only tryoto has API endpoint
    // Add more providers here if needed
    let apiUrl = null;
    if (provider === 'tryoto') {
        apiUrl = '{{ route("api.shipping.tryoto.options") }}';
    }

    if (!apiUrl) {
        container.html('<div class="m-alert m-alert--warning"><i class="fas fa-info-circle me-2"></i>@lang("API not configured for this provider")</div>');
        return;
    }

    $.ajax({
        url: apiUrl,
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            merchant_id: merchantId
        },
        success: function(response) {
            apiProvidersLoaded[provider] = true;
            // API returns delivery_options (from Tryoto)
            const options = response.delivery_options || response.options || [];
            if (response.success && options.length > 0) {
                renderApiProviderOptions(provider, options);
            } else {
                container.html(
                    '<div class="m-alert m-alert--warning"><i class="fas fa-info-circle me-2"></i>' +
                    (response.error || response.message || '@lang("No shipping options available")') + '</div>'
                );
            }
        },
        error: function(xhr) {
            container.html(
                '<div class="m-alert m-alert--danger"><i class="fas fa-exclamation-triangle me-2"></i>@lang("Failed to load shipping options")</div>'
            );
        }
    });
}

// Render API provider options (e.g., Tryoto response format)
function renderApiProviderOptions(provider, options) {
    let html = '';
    options.forEach(function(option, index) {
        // Handle both camelCase (Tryoto API) and snake_case formats
        const companyName = option.companyName || option.company_name || option.title || provider;
        const serviceName = option.serviceName || option.service_name || '';
        const price = parseFloat(option.price) || 0;
        const logo = option.logo || '';
        const estimatedDays = option.estimatedDays || option.estimated_days || '';
        const optionId = option.id || (provider + '_' + index);

        html += `
            <div class="form-check p-3 border rounded mb-2 shipping-option api-option">
                <input class="form-check-input" type="radio" name="shipping_option"
                       id="${provider}_${optionId}" value="${optionId}"
                       data-price="${price}"
                       data-title="${companyName}${serviceName ? ' - ' + serviceName : ''}"
                       data-provider="${provider}"
                       data-logo="${logo}"
                       data-service="${serviceName}"
                       data-company="${companyName}">
                <label class="form-check-label w-100" for="${provider}_${optionId}">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            ${logo ? '<img src="' + logo + '" alt="" class="me-2" style="height: 30px;">' : ''}
                            <div>
                                <strong>${companyName}</strong>
                                ${serviceName ? '<br><small class="text-muted">' + serviceName + '</small>' : ''}
                                ${estimatedDays ? '<br><small class="text-muted"><i class="fas fa-clock me-1"></i>' + estimatedDays + '</small>' : ''}
                            </div>
                        </div>
                        <span class="text-success fw-bold">${formatPrice(price)}</span>
                    </div>
                </label>
            </div>
        `;
    });
    $('#api-options-' + provider).html(html);
}

// Load API options when modal opens
$('.api-provider-modal').on('show.bs.modal', function() {
    const provider = $(this).data('provider');
    if (provider) {
        loadApiProviderOptions(provider);
    }
});

// Handle shipping selection in any modal
$(document).on('change', 'input[name="shipping_option"]', function() {
    const option = $(this);
    const price = parseFloat(option.data('price')) || 0;
    const title = option.data('title');
    const provider = option.data('provider');
    const isFree = option.data('is-free') == '1';

    // Set delivery type to shipping
    $('#delivery_type').val('shipping');

    // Update shipping hidden fields
    $('#selected_shipping_id').val(option.val());
    $('#selected_shipping_cost').val(price);
    $('#selected_shipping_provider').val(provider);

    // Clear courier selection
    $('#selected_courier_id').val('');
    $('#selected_courier_fee').val('0');
    $('input[name="courier_option"]').prop('checked', false);
    $('.courier-option').removeClass('border-primary bg-light');

    // Reset all provider buttons
    resetProviderButtons(provider);

    // Update selected provider button
    const displayText = isFree ? title + ': @lang("Free")' : title + ': ' + formatPrice(price);
    $('#provider-text-' + provider).text(displayText).removeClass('text-muted').addClass('text-success');
    $('#provider-btn-' + provider).removeClass('m-btn--outline').addClass('m-btn--success-outline');

    // Update UI
    $('.shipping-option').removeClass('border-primary bg-light');
    option.closest('.shipping-option').addClass('border-primary bg-light');

    updateSummary();
    checkSubmitBtn();

    // Close modal after selection
    setTimeout(function() {
        $('.modal').modal('hide');
    }, 300);
});

// Handle packaging selection
$(document).on('change', 'input[name="packaging_option"]', function() {
    const option = $(this);
    const price = parseFloat(option.data('price')) || 0;
    const title = option.data('title');

    // Update packing hidden fields
    $('#selected_packing_id').val(option.val());
    $('#selected_packing_cost').val(price);

    // Update button text
    const displayText = price > 0 ? title + ': ' + formatPrice(price) : title + ': @lang("Free")';
    $('#packaging-selected-text').text(displayText).removeClass('text-muted').addClass('text-success');
    $('#packaging-btn').removeClass('m-btn--outline').addClass('m-btn--success-outline');

    // Update UI
    $('.packaging-option').removeClass('border-primary bg-light');
    option.closest('.packaging-option').addClass('border-primary bg-light');

    updateSummary();

    // Close modal
    setTimeout(function() {
        $('#packagingModal').modal('hide');
    }, 300);
});

// Handle courier selection - deselect shipping
$(document).on('change', 'input[name="courier_option"]', function() {
    const option = $(this);
    const price = parseFloat(option.data('price')) || 0;

    // Set delivery type to local_courier
    $('#delivery_type').val('local_courier');

    // Update courier hidden fields
    $('#selected_courier_id').val(option.val());
    $('#selected_courier_fee').val(price);

    // Clear shipping selection
    $('#selected_shipping_id').val('');
    $('#selected_shipping_cost').val('0');
    $('#selected_shipping_provider').val('');
    $('input[name="shipping_option"]').prop('checked', false);
    $('.shipping-option').removeClass('border-primary bg-light');

    // Reset all provider buttons
    resetProviderButtons(null);

    // Update UI
    $('.courier-option').removeClass('border-primary bg-light');
    option.closest('.courier-option').addClass('border-primary bg-light');

    updateSummary();
    checkSubmitBtn();
});

// Form submission
$('#shipping-form').on('submit', function(e) {
    e.preventDefault();

    const hasShipping = $('#selected_shipping_id').val();
    const hasCourier = $('#selected_courier_id').val();

    if (!hasShipping && !hasCourier) {
        toastr.error('@lang("Please select a delivery method")');
        return;
    }

    const btn = $('#submit-btn');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> @lang("Processing...")');

    $.ajax({
        url: apiBaseUrl + '/shipping',
        method: 'POST',
        data: $(this).serialize(),
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                window.location.href = response.redirect || (apiBaseUrl + '/payment');
            } else {
                toastr.error(response.message || '@lang("Something went wrong")');
                btn.prop('disabled', false).html('@lang("Continue to Payment") <i class="fas fa-arrow-right ms-2"></i>');
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || '@lang("Something went wrong")');
            btn.prop('disabled', false).html('@lang("Continue to Payment") <i class="fas fa-arrow-right ms-2"></i>');
        }
    });
});

// Initialize
$(document).ready(function() {
    checkSubmitBtn();
});
</script>
@endsection
