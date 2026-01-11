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
                        <li>@lang('Payment')</li>
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
                        <span class="line-3"></span>
                        <div class="single-step active">
                            <span class="step-btn"><i class="fas fa-check"></i></span>
                            <span class="step-txt">@lang('Address')</span>
                        </div>
                        <div class="single-step active">
                            <span class="step-btn"><i class="fas fa-check"></i></span>
                            <span class="step-txt">@lang('Shipping')</span>
                        </div>
                        <div class="single-step active">
                            <span class="step-btn">3</span>
                            <span class="step-txt">@lang('Payment')</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row gy-4">
                <div class="col-lg-7 col-xl-8">
                    {{-- Delivery Summary --}}
                    <div class="m-card mb-4">
                        <div class="m-card__header d-flex justify-content-between align-items-center">
                            <h5 class="m-0">
                                <i class="fas fa-shipping-fast me-2"></i>
                                @lang('Delivery Details')
                            </h5>
                            <a href="{{ route('merchant.checkout.shipping', $merchant_id) }}" class="m-btn m-btn--sm m-btn--outline">
                                <i class="fas fa-edit me-1"></i> @lang('Edit')
                            </a>
                        </div>
                        <div class="m-card__body">
                            <div class="row">
                                {{-- Address --}}
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <h6 class="text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        @lang('Delivery Address')
                                    </h6>
                                    <p class="mb-1"><strong>{{ $address['customer_name'] ?? '-' }}</strong></p>
                                    <p class="mb-1 small text-muted">{{ $address['customer_address'] ?? '-' }}</p>
                                    <p class="mb-0 small text-muted">
                                        <i class="fas fa-phone me-1"></i>{{ $address['customer_phone'] ?? '-' }}
                                    </p>
                                </div>
                                {{-- Shipping/Courier --}}
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-2">
                                        @if(($shipping['delivery_type'] ?? 'shipping') === 'local_courier')
                                            <i class="fas fa-motorcycle me-1"></i>
                                            @lang('Courier Delivery')
                                        @else
                                            <i class="fas fa-truck me-1"></i>
                                            @lang('Shipping Method')
                                        @endif
                                    </h6>
                                    @if(($shipping['delivery_type'] ?? 'shipping') === 'local_courier')
                                        <p class="mb-1"><strong>{{ $shipping['courier_name'] ?? '-' }}</strong></p>
                                        <p class="mb-0 small text-success">
                                            {{ $curr->sign ?? '' }}{{ number_format($shipping['courier_fee'] ?? 0, 2) }}
                                        </p>
                                    @else
                                        <p class="mb-1"><strong>{{ $shipping['shipping_name'] ?? ucfirst($shipping['shipping_provider'] ?? 'Standard') }}</strong></p>
                                        <p class="mb-0 small">
                                            @if($shipping['is_free_shipping'] ?? false)
                                                <span class="text-decoration-line-through text-muted me-1">
                                                    {{ $curr->sign ?? '' }}{{ number_format($shipping['original_shipping_cost'] ?? 0, 2) }}
                                                </span>
                                                <span class="badge bg-success">@lang('Free')</span>
                                            @else
                                                <span class="text-success">{{ $curr->sign ?? '' }}{{ number_format($shipping['shipping_cost'] ?? 0, 2) }}</span>
                                            @endif
                                        </p>
                                    @endif

                                    @if(!empty($shipping['packing_name']))
                                    <p class="mb-0 mt-2 small text-muted">
                                        <i class="fas fa-box me-1"></i>
                                        {{ $shipping['packing_name'] }}: {{ $curr->sign ?? '' }}{{ number_format($shipping['packing_cost'] ?? 0, 2) }}
                                    </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Methods --}}
                    <div class="m-card">
                        <div class="m-card__header">
                            <h5 class="m-0">
                                <i class="fas fa-credit-card me-2"></i>
                                @lang('Select Payment Method')
                            </h5>
                        </div>
                        <div class="m-card__body">
                            <div class="row g-3" id="payment-methods-container">
                                @foreach($payment_methods ?? [] as $method)
                                <div class="col-md-6 col-lg-4">
                                    <div class="payment-method-card border rounded p-3 h-100" data-method="{{ $method['keyword'] }}">
                                        <input type="radio" name="payment_method" id="pay_{{ $method['keyword'] }}"
                                               value="{{ $method['keyword'] }}" class="d-none">
                                        <label for="pay_{{ $method['keyword'] }}" class="d-flex flex-column align-items-center text-center cursor-pointer w-100 h-100">
                                            @if(!empty($method['image']))
                                            <img src="{{ asset('assets/images/'.$method['image']) }}" alt="{{ $method['title'] }}"
                                                 style="max-height: 40px; max-width: 100px;" class="mb-2">
                                            @else
                                            <i class="fas fa-credit-card fa-2x text-muted mb-2"></i>
                                            @endif
                                            <span class="small">{{ $method['title'] }}</span>
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            {{-- Payment Form Container --}}
                            <div id="payment-form-container" class="mt-4 d-none">
                                {{-- Dynamic payment forms will be loaded here --}}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Order Summary --}}
                <div class="col-lg-5 col-xl-4">
                    <div class="summary-box sticky-top" style="top: 20px;">
                        <h4 class="form-title">@lang('Order Summary')</h4>

                        <div class="summary-inner-box">
                            <ul class="summary-list">
                                <li>
                                    <span>@lang('Subtotal') ({{ $cart['total_qty'] ?? 0 }})</span>
                                    <span>{{ $curr->sign ?? '' }}{{ number_format($cart['total_price'] ?? 0, 2) }}</span>
                                </li>
                                @if(($totals['discount_amount'] ?? 0) > 0)
                                <li class="text-success">
                                    <span>@lang('Discount')</span>
                                    <span>-{{ $curr->sign ?? '' }}{{ number_format($totals['discount_amount'] ?? 0, 2) }}</span>
                                </li>
                                @endif

                                {{-- Shipping or Courier --}}
                                @if(($shipping['delivery_type'] ?? 'shipping') === 'local_courier')
                                    {{-- Courier Delivery --}}
                                    <li>
                                        <span>
                                            <i class="fas fa-motorcycle me-1 text-muted"></i>
                                            @lang('Courier')
                                            @if(!empty($shipping['courier_name']))
                                                <small class="text-muted d-block">{{ $shipping['courier_name'] }}</small>
                                            @endif
                                        </span>
                                        <span>{{ $curr->sign ?? '' }}{{ number_format($totals['courier_fee'] ?? 0, 2) }}</span>
                                    </li>
                                @else
                                    {{-- Regular Shipping --}}
                                    <li>
                                        <span>
                                            <i class="fas fa-truck me-1 text-muted"></i>
                                            @lang('Shipping')
                                            @if(!empty($shipping['shipping_name']))
                                                <small class="text-muted d-block">{{ $shipping['shipping_name'] }}</small>
                                            @endif
                                        </span>
                                        <span>
                                            @if($shipping['is_free_shipping'] ?? false)
                                                <span class="text-decoration-line-through text-muted me-1">
                                                    {{ $curr->sign ?? '' }}{{ number_format($shipping['original_shipping_cost'] ?? 0, 2) }}
                                                </span>
                                                <span class="badge bg-success">@lang('Free')</span>
                                            @else
                                                {{ $curr->sign ?? '' }}{{ number_format($totals['shipping_cost'] ?? 0, 2) }}
                                            @endif
                                        </span>
                                    </li>
                                @endif

                                @if(($totals['packing_cost'] ?? 0) > 0 || !empty($shipping['packing_name']))
                                <li>
                                    <span>
                                        <i class="fas fa-box me-1 text-muted"></i>
                                        @lang('Packaging')
                                        @if(!empty($shipping['packing_name']))
                                            <small class="text-muted d-block">{{ $shipping['packing_name'] }}</small>
                                        @endif
                                    </span>
                                    <span>
                                        @if(($totals['packing_cost'] ?? 0) > 0)
                                            {{ $curr->sign ?? '' }}{{ number_format($totals['packing_cost'], 2) }}
                                        @else
                                            <span class="badge bg-success">@lang('Free')</span>
                                        @endif
                                    </span>
                                </li>
                                @endif
                                @if(($totals['tax_amount'] ?? 0) > 0)
                                <li>
                                    <span>@lang('Tax') ({{ $totals['tax_rate'] ?? 0 }}%)</span>
                                    <span>{{ $curr->sign ?? '' }}{{ number_format($totals['tax_amount'] ?? 0, 2) }}</span>
                                </li>
                                @endif
                            </ul>
                            <div class="total-cost">
                                <span>@lang('Total')</span>
                                <span class="text-success">{{ $curr->sign ?? '' }}{{ number_format($totals['grand_total'] ?? 0, 2) }}</span>
                            </div>
                        </div>

                        {{-- Wallet Balance --}}
                        @if(auth()->check() && ($wallet_balance ?? 0) > 0)
                        <div class="summary-inner-box">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="use_wallet" name="use_wallet" value="1">
                                <label class="form-check-label" for="use_wallet">
                                    @lang('Use Wallet Balance')
                                    <span class="text-success">({{ App\Models\CatalogItem::convertPrice($wallet_balance) }})</span>
                                </label>
                            </div>
                        </div>
                        @endif

                        <div class="summary-inner-box">
                            <div class="btn-wrappers">
                                <button type="button" class="template-btn w-100" id="place-order-btn" disabled>
                                    <i class="fas fa-lock me-2"></i>
                                    @lang('Place Order')
                                </button>
                                <a href="{{ route('merchant.checkout.shipping', $merchant_id) }}" class="template-btn dark-outline w-100 mt-2">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    @lang('Back')
                                </a>
                            </div>
                        </div>

                        <p class="text-center small text-muted mt-3">
                            <i class="fas fa-shield-alt me-1"></i>
                            @lang('Your payment information is secure')
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
{{-- Payment Gateway Scripts --}}
<script src="https://js.stripe.com/v3/"></script>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
const merchantId = {{ $merchant_id }};
const apiBaseUrl = '/merchant/' + merchantId + '/checkout';
let selectedPaymentMethod = null;

// Handle payment method selection
$(document).on('click', '.payment-method-card', function() {
    const method = $(this).data('method');
    selectedPaymentMethod = method;

    $('.payment-method-card').removeClass('border-primary bg-light');
    $(this).addClass('border-primary bg-light');
    $(this).find('input[type="radio"]').prop('checked', true);

    $('#place-order-btn').prop('disabled', false);

    // Load payment form based on method
    loadPaymentForm(method);
});

// Load payment-specific form
function loadPaymentForm(method) {
    const container = $('#payment-form-container');

    // Methods that need additional form fields
    const formMethods = {
        'stripe': `
            <div class="m-alert m-alert--info">
                <i class="fas fa-info-circle me-2"></i>
                @lang('You will be redirected to Stripe to complete your payment.')
            </div>
        `,
        'authorize.net': `
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">@lang('Card Number')</label>
                    <input type="text" name="cardNumber" class="form-control" placeholder="1234 5678 9012 3456" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">@lang('Month')</label>
                    <select name="month" class="form-control" required>
                        @for($i = 1; $i <= 12; $i++)
                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">@lang('Year')</label>
                    <select name="year" class="form-control" required>
                        @for($i = date('Y'); $i <= date('Y') + 10; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">@lang('CVV')</label>
                    <input type="text" name="cardCode" class="form-control" placeholder="123" maxlength="4" required>
                </div>
            </div>
        `,
        'manual': `
            <div class="mb-3">
                <label class="form-label">@lang('Upload Payment Proof')</label>
                <input type="file" name="txn_img" class="form-control" accept="image/*" required>
                <small class="text-muted">@lang('Upload screenshot of your bank transfer')</small>
            </div>
        `,
        'cod': `
            <div class="m-alert m-alert--info">
                <i class="fas fa-money-bill-wave me-2"></i>
                @lang('Pay with cash when your order is delivered.')
            </div>
        `
    };

    if (formMethods[method]) {
        container.html(formMethods[method]).removeClass('d-none');
    } else {
        container.html(`
            <div class="m-alert m-alert--info">
                <i class="fas fa-external-link-alt me-2"></i>
                @lang('You will be redirected to complete your payment.')
            </div>
        `).removeClass('d-none');
    }
}

// Place order
$('#place-order-btn').on('click', function() {
    if (!selectedPaymentMethod) {
        toastr.error('@lang("Please select a payment method")');
        return;
    }

    const btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> @lang("Processing...")');

    const formData = new FormData();
    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
    formData.append('use_wallet', $('#use_wallet').is(':checked') ? 1 : 0);

    // Add card details if needed
    if (selectedPaymentMethod === 'authorize.net') {
        formData.append('cardNumber', $('input[name="cardNumber"]').val());
        formData.append('month', $('select[name="month"]').val());
        formData.append('year', $('select[name="year"]').val());
        formData.append('cardCode', $('input[name="cardCode"]').val());
    }

    // Add manual payment image
    if (selectedPaymentMethod === 'manual') {
        const fileInput = $('input[name="txn_img"]')[0];
        if (fileInput.files.length > 0) {
            formData.append('txn_img', fileInput.files[0]);
        }
    }

    $.ajax({
        url: apiBaseUrl + '/payment/' + selectedPaymentMethod,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                if (response.redirect) {
                    window.location.href = response.redirect;
                } else {
                    toastr.success(response.message || '@lang("Order placed successfully!")');
                    window.location.href = apiBaseUrl + '/return/success';
                }
            } else {
                toastr.error(response.message || '@lang("Payment failed")');
                btn.prop('disabled', false).html('<i class="fas fa-lock me-2"></i>@lang("Place Order")');
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || '@lang("Something went wrong")');
            btn.prop('disabled', false).html('<i class="fas fa-lock me-2"></i>@lang("Place Order")');
        }
    });
});
</script>
@endsection
