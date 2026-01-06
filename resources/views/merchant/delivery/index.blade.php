@extends('layouts.merchant')

@section('content')
    <!-- outlet start  -->
    <div class="gs-merchant-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-merchant-breadcrumb has-mb">
            <h4 class="text-capitalize">@lang('Purchase Delivery')</h4>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" class="home-icon-merchant-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">
                        @lang('Dashboard')
                    </a>
                </li>

                <li>
                    <a href="javascript:;" class="text-capitalize">
                        @lang('Purchase Delivery')
                    </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        {{-- ✅ Tryoto Status Alert --}}
        @if(isset($tryotoStatus) && !$tryotoStatus['available'])
            <div class="alert alert-warning mb-3">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>@lang('Smart Shipping (Tryoto)'):</strong>
                {{ $tryotoStatus['message'] ?? __('Not configured') }}
                @if(!empty($tryotoStatus['issues']))
                    <ul class="mb-0 mt-2">
                        @foreach($tryotoStatus['issues'] as $issue)
                            <li>{{ $issue }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @elseif(isset($tryotoStatus) && $tryotoStatus['sandbox'])
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>@lang('Sandbox Mode'):</strong>
                @lang('Tryoto is running in sandbox/test mode')
            </div>
        @endif

        {{-- ✅ Empty Orders Alert --}}
        @if($datas->isEmpty())
            <div class="alert alert-secondary mb-3">
                <i class="fas fa-inbox me-2"></i>
                @lang('No orders found for delivery.')
                <br>
                <small class="text-muted">@lang('Purchases will appear here once customers place orders with your catalogItems.')</small>
            </div>
        @endif

        <!-- Table area start  -->
        <div class="merchant-table-wrapper all-orders-table-wrapper">
            <div class="user-table table-responsive position-relative">
                <table class="gs-data-table w-100">
                    <thead>
                        <tr>
                            <th>{{ __('Purchase Number') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Total Cost') }}</th>
                            <th>{{ __('Payment Method') }}</th>
                            <th>{{ __('Shipping Status') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($datas as $data)
                            @php
                                $merchantId = auth()->id();

                                // Check for local courier delivery
                                $delivery = App\Models\DeliveryCourier::where('purchase_id', $data->id)
                                    ->where('merchant_id', $merchantId)
                                    ->first();

                                // Check for Tryoto shipment
                                $shipment = App\Models\ShipmentStatusLog::where('purchase_id', $data->id)
                                    ->where('merchant_id', $merchantId)
                                    ->orderBy('status_date', 'desc')
                                    ->orderBy('created_at', 'desc')
                                    ->first();

                                // ✅ Get customer's shipping choice
                                $customerChoice = $data->getCustomerShippingChoice($merchantId);

                                // Calculate price
                                $purchase = $data;
                                $price = $purchase->merchantPurchases()->where('user_id', $merchantId)->sum('price');
                            @endphp
                            <tr>
                                <!-- Purchase Number -->
                                <td>
                                    <span class="content">{{ $data->purchase_number }}</span>
                                    <br>
                                    <small class="text-muted">{{ $data->created_at->format('Y-m-d') }}</small>
                                </td>

                                <!-- Customer Info -->
                                <td>
                                    <strong>{{ $data->customer_name }}</strong>
                                    <br>
                                    <small><i class="fas fa-phone"></i> {{ $data->customer_phone }}</small>
                                    <br>
                                    <small><i class="fas fa-city"></i> {{ $data->customer_city }}</small>
                                    <br>
                                    <small title="{{ $data->customer_address }}"><i class="fas fa-map-marker-alt"></i> {{ Str::limit($data->customer_address, 25) }}</small>
                                </td>

                                <!-- Total Cost -->
                                <td>
                                    <span class="content">{{ PriceHelper::showOrderCurrencyPrice($price, $data->currency_sign) }}</span>
                                </td>

                                <!-- Payment Method -->
                                <td>
                                    {{ $data->method }}
                                    <br>
                                    <span class="badge {{ $data->payment_status == 'Completed' ? 'bg-success' : 'bg-warning text-dark' }}">
                                        {{ $data->payment_status }}
                                    </span>
                                </td>

                                <!-- Shipping Status -->
                                <td>
                                    {{-- ✅ Show Customer's Shipping Choice --}}
                                    @if ($customerChoice && !$shipment && !$delivery)
                                        @php
                                            $isFreeShipping = $customerChoice['is_free_shipping'] ?? false;
                                            $originalPrice = $customerChoice['original_price'] ?? $customerChoice['price'] ?? 0;
                                            $actualPrice = $customerChoice['price'] ?? 0;
                                        @endphp
                                        <div class="mb-1">
                                            <small class="text-primary fw-bold">
                                                <i class="fas fa-user-check"></i> @lang('Customer Selected:')
                                            </small>
                                            <br>
                                            <span class="badge bg-primary">{{ $customerChoice['company_name'] ?? 'N/A' }}</span>
                                            <br>
                                            @if($isFreeShipping)
                                                {{-- ✅ Free Shipping Alert --}}
                                                <span class="text-decoration-line-through text-muted">
                                                    {{ $data->currency_sign }}{{ number_format($originalPrice, 2) }}
                                                </span>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-gift"></i> @lang('Free!')
                                                </span>
                                                <br>
                                                <small class="text-danger fw-bold">
                                                    <i class="fas fa-exclamation-circle"></i>
                                                    @lang('Merchant pays shipping')
                                                </small>
                                            @else
                                                <small>{{ $data->currency_sign }}{{ number_format($actualPrice, 2) }}</small>
                                            @endif
                                        </div>
                                        <span class="badge bg-warning text-dark">@lang('Not Assigned')</span>
                                    @elseif ($shipment)
                                        {{-- Tryoto Shipment --}}
                                        <span class="badge bg-info mb-1">{{ $shipment->company_name }}</span>
                                        <br>
                                        <small>{{ $shipment->tracking_number }}</small>
                                        <br>
                                        <span class="badge
                                            @if($shipment->status == 'delivered') bg-success
                                            @elseif($shipment->status == 'in_transit') bg-primary
                                            @elseif($shipment->status == 'out_for_delivery') bg-info
                                            @elseif(in_array($shipment->status, ['failed', 'returned', 'cancelled'])) bg-danger
                                            @else bg-secondary
                                            @endif">
                                            {{ $shipment->status_ar ?? $shipment->status }}
                                        </span>
                                    @elseif ($delivery)
                                        {{-- Local Courier Delivery --}}
                                        <span class="badge bg-secondary mb-1">@lang('Local Courier')</span>
                                        <br>
                                        <small>{{ $delivery->courier->name ?? 'N/A' }}</small>
                                        <br>
                                        <span class="badge
                                            @if($delivery->status == 'delivered') bg-success
                                            @elseif($delivery->status == 'accepted') bg-primary
                                            @elseif($delivery->status == 'rejected') bg-danger
                                            @else bg-warning
                                            @endif">
                                            {{ ucfirst($delivery->status) }}
                                        </span>
                                    @else
                                        {{-- Not Assigned --}}
                                        <span class="badge bg-danger">@lang('Not Assigned')</span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td>
                                    @if ($shipment && !in_array($shipment->status, ['cancelled', 'returned', 'delivered']))
                                        <button type="button" class="btn btn-sm btn-info mb-1 trackShipmentBtn"
                                            data-tracking="{{ $shipment->tracking_number }}"
                                            data-bs-toggle="modal" data-bs-target="#trackingModal">
                                            <i class="fas fa-map-marker-alt"></i> @lang('Track')
                                        </button>
                                        @if(!in_array($shipment->status, ['out_for_delivery', 'delivered']))
                                        <br>
                                        <button type="button" class="btn btn-sm btn-danger cancelShipmentBtn"
                                            data-tracking="{{ $shipment->tracking_number }}"
                                            data-bs-toggle="modal" data-bs-target="#cancelModal">
                                            <i class="fas fa-times"></i> @lang('Cancel')
                                        </button>
                                        @endif
                                    @elseif ($shipment && $shipment->status == 'delivered')
                                        <a href="{{ route('merchant-purchase-show', $data->purchase_number) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-eye"></i> @lang('View')
                                        </a>
                                    @elseif ($delivery && $delivery->status == 'delivered')
                                        <a href="{{ route('merchant-purchase-show', $data->purchase_number) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-eye"></i> @lang('View')
                                        </a>
                                    @elseif ($delivery && $delivery->status == 'pending')
                                        {{-- ✅ Local Courier assigned - merchant needs to mark ready --}}
                                        <form action="{{ route('merchant.ready.pickup') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="purchase_id" value="{{ $data->id }}">
                                            <button type="submit" class="btn btn-sm btn-success mb-1">
                                                <i class="fas fa-check-circle"></i> @lang('Ready for Pickup')
                                            </button>
                                        </form>
                                        <br>
                                        <a href="{{ route('merchant-purchase-show', $data->purchase_number) }}" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-eye"></i> @lang('View')
                                        </a>
                                    @elseif ($delivery && in_array($delivery->status, ['ready_for_pickup', 'accepted']))
                                        {{-- Waiting for courier to pick up or deliver --}}
                                        <span class="badge bg-info mb-1">
                                            <i class="fas fa-clock"></i> @lang('Waiting for Courier')
                                        </span>
                                        <br>
                                        <a href="{{ route('merchant-purchase-show', $data->purchase_number) }}" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-eye"></i> @lang('View')
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-sm btn-primary mb-1 assignShippingBtn"
                                            data-purchase-id="{{ $data->id }}"
                                            data-customer-city="{{ $data->customer_city }}"
                                            data-customer-choice='@json($customerChoice)'
                                            data-bs-toggle="modal" data-bs-target="#shippingModal">
                                            <i class="fas fa-shipping-fast"></i> @lang('Assign')
                                        </button>
                                        <br>
                                        <a href="{{ route('merchant-purchase-show', $data->purchase_number) }}" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-eye"></i> @lang('View')
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Table area end  -->
    </div>
    <!-- outlet end  -->

    {{-- Shipping Assignment Modal --}}
    <div class="modal gs-modal fade" id="shippingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Assign Shipping Method')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modal_order_id" value="">

                    {{-- ✅ Customer Choice Alert --}}
                    <div id="customerChoiceAlert" class="alert alert-info d-none mb-3">
                        <i class="fas fa-user-check me-2"></i>
                        <strong>@lang('Customer Preference:')</strong>
                        <span id="customerChoiceText"></span>
                        <br>
                        <small class="text-muted">@lang('You can use the customer\'s choice or select a different option.')</small>
                    </div>

                    {{-- ✅ Free Shipping Warning for Merchant --}}
                    <div id="freeShippingWarning" class="alert alert-warning d-none mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>@lang('Free Shipping Purchase!')</strong>
                        <br>
                        <span>@lang('The customer received free shipping on this purchase. You are responsible for paying the shipping cost.')</span>
                        <br>
                        <small class="text-muted">
                            @lang('Original shipping price:'): <strong id="originalShippingPrice"></strong>
                        </small>
                    </div>

                    {{-- Shipping Method Tabs --}}
                    <ul class="nav nav-tabs mb-3" id="shippingTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tryoto-tab" data-bs-toggle="tab" data-bs-target="#tryoto-content" type="button" role="tab">
                                <i class="fas fa-truck"></i> @lang('Shipping Company')
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="courier-tab" data-bs-toggle="tab" data-bs-target="#courier-content" type="button" role="tab">
                                <i class="fas fa-motorcycle"></i> @lang('Local Courier')
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="shippingTabsContent">
                        {{-- Tryoto Tab --}}
                        <div class="tab-pane fade show active" id="tryoto-content" role="tabpanel">
                            <form id="tryotoForm" action="{{ route('merchant.send.tryoto') }}" method="POST">
                                @csrf
                                <input type="hidden" name="order_id" id="tryoto_order_id">
                                <input type="hidden" name="delivery_option_id" id="delivery_option_id">
                                <input type="hidden" name="company" id="selected_company">
                                <input type="hidden" name="price" id="selected_price">
                                <input type="hidden" name="service_type" id="selected_service_type">

                                <div class="mb-3">
                                    <label class="form-label">@lang('Select Shipping Company')</label>
                                    <select class="form-select" id="shippingCompanySelect" required>
                                        <option value="">@lang('Loading shipping options...')</option>
                                    </select>
                                </div>

                                <div id="shippingDetails" class="d-none mb-3 p-3 bg-light rounded">
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>@lang('Company:')</strong>
                                            <span id="detail_company"></span>
                                        </div>
                                        <div class="col-6">
                                            <strong>@lang('Price:')</strong>
                                            <span id="detail_price"></span>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <strong>@lang('Estimated Delivery:')</strong>
                                            <span id="detail_days"></span> @lang('days')
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    @lang('The shipment will be created with Tryoto and you will receive a tracking number.')
                                </div>

                                <button type="submit" class="template-btn w-100" id="submitTryotoBtn" disabled>
                                    <i class="fas fa-paper-plane"></i> @lang('Create Shipment')
                                </button>
                            </form>
                        </div>

                        {{-- Local Courier Tab --}}
                        <div class="tab-pane fade" id="courier-content" role="tabpanel">
                            <form action="{{ route('merchant-courier-search-submit') }}" method="POST">
                                @csrf
                                <input type="hidden" name="order_id" id="courier_order_id">

                                <div class="mb-3">
                                    <label class="form-label">@lang('Select Courier')</label>
                                    <select class="form-select" name="courier_id" id="courierSelect" required>
                                        <option value="">@lang('Select Courier')</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">@lang('Select Pickup Point')</label>
                                    <select class="form-select" name="pickup_point_id" id="pickupPointSelect" required>
                                        <option value="">@lang('Select Pickup Point')</option>
                                        @foreach (App\Models\PickupPoint::where('user_id', auth()->id())->whereStatus(1)->get() as $pickup)
                                            <option value="{{ $pickup->id }}">{{ $pickup->location }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="courierDetails" class="d-none mb-3 p-3 bg-light rounded">
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>@lang('Courier:')</strong>
                                            <span id="courier_name"></span>
                                        </div>
                                        <div class="col-6">
                                            <strong>@lang('Cost:')</strong>
                                            <span id="courier_cost"></span>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <strong>@lang('Service Area:')</strong>
                                            <span id="courier_area"></span>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="template-btn w-100">
                                    <i class="fas fa-user-check"></i> @lang('Assign Courier')
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tracking Modal --}}
    <div class="modal gs-modal fade" id="trackingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Shipment Tracking')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="trackingContent">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">@lang('Loading tracking information...')</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cancel Shipment Modal --}}
    <div class="modal gs-modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('merchant.cancel.shipment') }}" method="POST">
                    @csrf
                    <input type="hidden" name="tracking_number" id="cancel_tracking_number">

                    <div class="modal-header">
                        <h5 class="modal-title">@lang('Cancel Shipment')</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            @lang('Are you sure you want to cancel this shipment?')
                        </div>

                        <div class="mb-3">
                            <label class="form-label">@lang('Cancellation Reason')</label>
                            <textarea class="form-control" name="reason" rows="3" placeholder="@lang('Enter reason for cancellation...')"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn-danger">@lang('Cancel Shipment')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
(function($) {
    "use strict";

    // Store customer choice globally
    let currentCustomerChoice = null;

    // Open shipping modal
    $(document).on('click', '.assignShippingBtn', function() {
        const orderId = $(this).data('purchase-id');
        const customerCity = $(this).data('customer-city');
        currentCustomerChoice = $(this).data('customer-choice');

        $('#modal_order_id').val(orderId);
        $('#tryoto_order_id').val(orderId);
        $('#courier_order_id').val(orderId);

        // ✅ Show customer choice alert if exists
        if (currentCustomerChoice && currentCustomerChoice.provider === 'tryoto') {
            $('#customerChoiceAlert').removeClass('d-none');
            $('#customerChoiceText').html(
                '<span class="badge bg-primary">' + (currentCustomerChoice.company_name || 'Tryoto') + '</span> - ' +
                '@lang("Price"): ' + (currentCustomerChoice.price || 0) + ' @lang("SAR")'
            );
        } else {
            $('#customerChoiceAlert').addClass('d-none');
        }

        // ✅ Show free shipping warning if applicable
        if (currentCustomerChoice && currentCustomerChoice.is_free_shipping) {
            $('#freeShippingWarning').removeClass('d-none');
            $('#originalShippingPrice').text((currentCustomerChoice.original_price || 0) + ' @lang("SAR")');
        } else {
            $('#freeShippingWarning').addClass('d-none');
        }

        // Reset forms
        $('#shippingCompanySelect').html('<option value="">@lang("Loading shipping options...")</option>');
        $('#shippingDetails').addClass('d-none');
        $('#submitTryotoBtn').prop('disabled', true);

        // Load Tryoto options
        $.get("{{ route('merchant.shipping.options') }}", { order_id: orderId }, function(response) {
            if (response.success) {
                $('#shippingCompanySelect').html(response.options);

                // ✅ Auto-select customer's choice if available
                if (currentCustomerChoice && currentCustomerChoice.provider === 'tryoto' && currentCustomerChoice.delivery_option_id) {
                    const optionToSelect = $('#shippingCompanySelect option[value="' + currentCustomerChoice.delivery_option_id + '"]');
                    if (optionToSelect.length) {
                        optionToSelect.prop('selected', true);
                        $('#shippingCompanySelect').trigger('change');
                        toastr.info('@lang("Customer\'s preferred shipping company selected automatically")');
                    }
                }
            } else {
                // ✅ تحسين معالجة الأخطاء
                let errorHtml = '<option value="">@lang("Shipping temporarily unavailable")</option>';
                $('#shippingCompanySelect').html(errorHtml);

                // عرض رسالة خطأ مناسبة
                if (response.error_code === 'MERCHANT_CITY_MISSING') {
                    toastr.warning('@lang("Please configure your city in merchant settings")');
                    if (response.show_settings_link) {
                        $('#shippingCompanySelect').after('<a href="{{ route("merchant-profile") }}" class="btn btn-sm btn-link">@lang("Go to Settings")</a>');
                    }
                } else if (response.error_code === 'CUSTOMER_CITY_MISSING') {
                    toastr.warning('@lang("Customer city not specified in purchase")');
                } else if (response.error_code === 'TRYOTO_NOT_CONFIGURED') {
                    toastr.error('@lang("Smart Shipping is not configured. Contact admin.")');
                } else if (response.error) {
                    toastr.error(response.error);
                }

                // ✅ عرض تفاصيل تقنية في الـ console للتشخيص
                if (response.technical_error) {
                    console.warn('Shipping Error Details:', response);
                }
            }
        }).fail(function(xhr) {
            $('#shippingCompanySelect').html('<option value="">@lang("Connection error - Please try again")</option>');
            toastr.error('@lang("Failed to connect to shipping service")');
            console.error('Shipping API Error:', xhr);
        });

        // Load couriers
        $.get("{{ route('merchant.find.courier') }}", { city: customerCity }, function(response) {
            $('#courierSelect').html(response.couriers);
        });
    });

    // Shipping company selection
    $(document).on('change', '#shippingCompanySelect', function() {
        const selected = $(this).find('option:selected');
        const deliveryOptionId = $(this).val();

        if (deliveryOptionId) {
            $('#delivery_option_id').val(deliveryOptionId);
            $('#selected_company').val(selected.data('company'));
            $('#selected_price').val(selected.data('price'));
            $('#selected_service_type').val(selected.data('service-type') || 'express');

            $('#detail_company').text(selected.data('company'));
            $('#detail_price').text(selected.data('display-price'));
            $('#detail_days').text(selected.data('days') || 'N/A');

            $('#shippingDetails').removeClass('d-none');
            $('#submitTryotoBtn').prop('disabled', false);
        } else {
            $('#shippingDetails').addClass('d-none');
            $('#submitTryotoBtn').prop('disabled', true);
        }
    });

    // Courier selection
    $(document).on('change', '#courierSelect', function() {
        const selected = $(this).find('option:selected');

        if ($(this).val()) {
            $('#courier_name').text(selected.attr('courierName'));
            $('#courier_cost').text(selected.attr('courierCost'));
            $('#courier_area').text(selected.attr('area'));
            $('#courierDetails').removeClass('d-none');
        } else {
            $('#courierDetails').addClass('d-none');
        }
    });

    // Track shipment
    $(document).on('click', '.trackShipmentBtn', function() {
        const trackingNumber = $(this).data('tracking');

        $('#trackingContent').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">@lang('Loading tracking information...')</p>
            </div>
        `);

        $.get("{{ route('merchant.track.shipment') }}", { tracking_number: trackingNumber }, function(response) {
            if (response.success) {
                let statusClass = 'secondary';
                if (response.status === 'delivered') statusClass = 'success';
                else if (response.status === 'in_transit') statusClass = 'primary';
                else if (response.status === 'out_for_delivery') statusClass = 'info';
                else if (['failed', 'returned'].includes(response.status)) statusClass = 'danger';

                let html = `
                    <div class="tracking-info">
                        <div class="text-center mb-4">
                            <span class="badge bg-${statusClass} fs-5 p-2">${response.status_ar || response.status}</span>
                        </div>
                        <div class="mb-3">
                            <strong>@lang('Tracking Number:')</strong> ${trackingNumber}
                        </div>
                `;

                if (response.location) {
                    html += `
                        <div class="mb-3">
                            <strong>@lang('Current Location:')</strong> ${response.location}
                        </div>
                    `;
                }

                if (response.estimated_delivery) {
                    html += `
                        <div class="mb-3">
                            <strong>@lang('Estimated Delivery:')</strong> ${response.estimated_delivery}
                        </div>
                    `;
                }

                if (response.events && response.events.length > 0) {
                    html += `<hr><h6>@lang('Shipment History')</h6><ul class="list-group">`;
                    response.events.forEach(function(event) {
                        html += `
                            <li class="list-group-item">
                                <small class="text-muted">${event.date || ''}</small>
                                <br>${event.description || event.status}
                            </li>
                        `;
                    });
                    html += `</ul>`;
                }

                html += `</div>`;
                $('#trackingContent').html(html);
            } else {
                $('#trackingContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        ${response.error || '@lang("Failed to get tracking information")'}
                    </div>
                `);
            }
        }).fail(function() {
            $('#trackingContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    @lang('Failed to get tracking information')
                </div>
            `);
        });
    });

    // Cancel shipment
    $(document).on('click', '.cancelShipmentBtn', function() {
        $('#cancel_tracking_number').val($(this).data('tracking'));
    });

})(jQuery);
</script>
@endsection
