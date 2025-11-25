@extends('layouts.vendor')

@section('content')
    <!-- outlet start  -->
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <h4 class="text-capitalize">@lang('Order Delivery')</h4>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="#4C3533" class="home-icon-vendor-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('vendor.dashboard') }}" class="text-capitalize">
                        @lang('Dashboard')
                    </a>
                </li>

                <li>
                    <a href="javascript:;" class="text-capitalize">
                        @lang('Order Delivery')
                    </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Table area start  -->
        <div class="vendor-table-wrapper all-orders-table-wrapper">
            <div class="user-table table-responsive position-relative">
                <table class="gs-data-table w-100">
                    <thead>
                        <tr>
                            <th>{{ __('Order Number') }}</th>
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
                                $vendorId = auth()->id();

                                // Check for local rider delivery
                                $delivery = App\Models\DeliveryRider::where('order_id', $data->id)
                                    ->where('vendor_id', $vendorId)
                                    ->first();

                                // Check for Tryoto shipment
                                $shipment = App\Models\ShipmentStatusLog::where('order_id', $data->id)
                                    ->where('vendor_id', $vendorId)
                                    ->orderBy('status_date', 'desc')
                                    ->orderBy('created_at', 'desc')
                                    ->first();

                                // Calculate price
                                $order = $data;
                                $price = $order->vendororders()->where('user_id', $vendorId)->sum('price');

                                if ($order->is_shipping == 1) {
                                    $vendor_shipping = json_decode($order->vendor_shipping_id);
                                    $vendor_packing_id = json_decode($order->vendor_packing_id);

                                    $shipping_id = optional($vendor_shipping)->$vendorId ?? null;
                                    if ($shipping_id && is_numeric($shipping_id)) {
                                        $shipping = App\Models\Shipping::find($shipping_id);
                                        if ($shipping) {
                                            $price += round($shipping->price * $order->currency_value, 2);
                                        }
                                    }

                                    $packing_id = optional($vendor_packing_id)->$vendorId ?? null;
                                    if ($packing_id && is_numeric($packing_id)) {
                                        $packaging = App\Models\Package::find($packing_id);
                                        if ($packaging) {
                                            $price += round($packaging->price * $order->currency_value, 2);
                                        }
                                    }
                                }
                            @endphp
                            <tr>
                                <!-- Order Number -->
                                <td>
                                    <span class="content">{{ $data->order_number }}</span>
                                    <br>
                                    <small class="text-muted">{{ $data->created_at->format('Y-m-d') }}</small>
                                </td>

                                <!-- Customer Info -->
                                <td class="text-start">
                                    <div class="customer">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="key">@lang('Name:')</span>
                                            <span class="value">{{ $data->customer_name }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="key">@lang('Phone:')</span>
                                            <span class="value">{{ $data->customer_phone }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="key">@lang('City:')</span>
                                            <span class="value">{{ $data->customer_city }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="key">@lang('Address:')</span>
                                            <span class="value">{{ Str::limit($data->customer_address, 30) }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Total Cost -->
                                <td>
                                    <span class="content">{{ PriceHelper::showOrderCurrencyPrice($price, $data->currency_sign) }}</span>
                                </td>

                                <!-- Payment Method -->
                                <td>
                                    <span class="content">{{ $data->method }}</span>
                                    <br>
                                    <span class="badge {{ $data->payment_status == 'Completed' ? 'bg-success' : 'bg-warning' }}">
                                        {{ $data->payment_status }}
                                    </span>
                                </td>

                                <!-- Shipping Status -->
                                <td class="text-start">
                                    <div class="shipping-status">
                                        @if ($shipment)
                                            {{-- Tryoto Shipment --}}
                                            <div class="tryoto-shipment">
                                                <span class="badge bg-info mb-1">
                                                    <i class="fas fa-truck"></i> {{ $shipment->company_name }}
                                                </span>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">@lang('Tracking:')</span>
                                                    <span class="value text-primary">{{ $shipment->tracking_number }}</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">@lang('Status:')</span>
                                                    <span class="badge
                                                        @if($shipment->status == 'delivered') bg-success
                                                        @elseif($shipment->status == 'in_transit') bg-primary
                                                        @elseif($shipment->status == 'out_for_delivery') bg-info
                                                        @elseif(in_array($shipment->status, ['failed', 'returned', 'cancelled'])) bg-danger
                                                        @else bg-secondary
                                                        @endif">
                                                        {{ $shipment->status_ar ?? $shipment->status }}
                                                    </span>
                                                </div>
                                                @if($shipment->status_date)
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">@lang('Updated:')</span>
                                                    <span class="value">{{ $shipment->status_date->diffForHumans() }}</span>
                                                </div>
                                                @endif
                                            </div>
                                        @elseif ($delivery)
                                            {{-- Local Rider Delivery --}}
                                            <div class="rider-delivery">
                                                <span class="badge bg-secondary mb-1">
                                                    <i class="fas fa-motorcycle"></i> @lang('Local Rider')
                                                </span>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">@lang('Rider:')</span>
                                                    <span class="value">{{ $delivery->rider->name ?? 'N/A' }}</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">@lang('Cost:')</span>
                                                    <span class="value">{{ PriceHelper::showAdminCurrencyPrice($delivery->servicearea->price ?? 0) }}</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">@lang('Status:')</span>
                                                    <span class="badge
                                                        @if($delivery->status == 'delivered') bg-success
                                                        @elseif($delivery->status == 'accepted') bg-primary
                                                        @elseif($delivery->status == 'rejected') bg-danger
                                                        @else bg-warning
                                                        @endif">
                                                        {{ ucfirst($delivery->status) }}
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            {{-- Not Assigned --}}
                                            <span class="badge bg-danger">
                                                <i class="fas fa-exclamation-circle"></i> @lang('Not Assigned')
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td>
                                    <div class="action-buttons d-flex flex-column gap-2">
                                        @if ($shipment && !in_array($shipment->status, ['cancelled', 'returned', 'delivered']))
                                            {{-- Track & Cancel buttons for active shipment --}}
                                            <button type="button" class="template-btn sm-btn info-btn trackShipmentBtn"
                                                data-tracking="{{ $shipment->tracking_number }}"
                                                data-bs-toggle="modal" data-bs-target="#trackingModal">
                                                <i class="fas fa-map-marker-alt"></i> @lang('Track')
                                            </button>
                                            @if(!in_array($shipment->status, ['out_for_delivery', 'delivered']))
                                            <button type="button" class="template-btn sm-btn danger-btn cancelShipmentBtn"
                                                data-tracking="{{ $shipment->tracking_number }}"
                                                data-bs-toggle="modal" data-bs-target="#cancelModal">
                                                <i class="fas fa-times"></i> @lang('Cancel')
                                            </button>
                                            @endif
                                        @elseif ($shipment && $shipment->status == 'delivered')
                                            {{-- Delivered --}}
                                            <a href="{{ route('vendor-order-show', $data->order_number) }}"
                                                class="template-btn sm-btn success-btn">
                                                <i class="fas fa-check"></i> @lang('View Order')
                                            </a>
                                        @elseif ($delivery && $delivery->status == 'delivered')
                                            {{-- Rider delivered --}}
                                            <a href="{{ route('vendor-order-show', $data->order_number) }}"
                                                class="template-btn sm-btn success-btn">
                                                <i class="fas fa-check"></i> @lang('View Order')
                                            </a>
                                        @else
                                            {{-- Assign shipping --}}
                                            <button type="button" class="template-btn sm-btn primary-btn assignShippingBtn"
                                                data-order-id="{{ $data->id }}"
                                                data-customer-city="{{ $data->customer_city }}"
                                                data-bs-toggle="modal" data-bs-target="#shippingModal">
                                                <i class="fas fa-shipping-fast"></i> @lang('Assign Shipping')
                                            </button>
                                            <a href="{{ route('vendor-order-show', $data->order_number) }}"
                                                class="template-btn sm-btn secondary-btn">
                                                <i class="fas fa-eye"></i> @lang('View')
                                            </a>
                                        @endif
                                    </div>
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

                    {{-- Shipping Method Tabs --}}
                    <ul class="nav nav-tabs mb-3" id="shippingTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tryoto-tab" data-bs-toggle="tab" data-bs-target="#tryoto-content" type="button" role="tab">
                                <i class="fas fa-truck"></i> @lang('Shipping Company')
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="rider-tab" data-bs-toggle="tab" data-bs-target="#rider-content" type="button" role="tab">
                                <i class="fas fa-motorcycle"></i> @lang('Local Rider')
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="shippingTabsContent">
                        {{-- Tryoto Tab --}}
                        <div class="tab-pane fade show active" id="tryoto-content" role="tabpanel">
                            <form id="tryotoForm" action="{{ route('vendor.send.tryoto') }}" method="POST">
                                @csrf
                                <input type="hidden" name="order_id" id="tryoto_order_id">
                                <input type="hidden" name="delivery_option_id" id="delivery_option_id">
                                <input type="hidden" name="company" id="selected_company">
                                <input type="hidden" name="price" id="selected_price">

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

                        {{-- Local Rider Tab --}}
                        <div class="tab-pane fade" id="rider-content" role="tabpanel">
                            <form action="{{ route('vendor-rider-search-submit') }}" method="POST">
                                @csrf
                                <input type="hidden" name="order_id" id="rider_order_id">

                                <div class="mb-3">
                                    <label class="form-label">@lang('Select Rider')</label>
                                    <select class="form-select" name="rider_id" id="riderSelect" required>
                                        <option value="">@lang('Select Rider')</option>
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

                                <div id="riderDetails" class="d-none mb-3 p-3 bg-light rounded">
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>@lang('Rider:')</strong>
                                            <span id="rider_name"></span>
                                        </div>
                                        <div class="col-6">
                                            <strong>@lang('Cost:')</strong>
                                            <span id="rider_cost"></span>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <strong>@lang('Service Area:')</strong>
                                            <span id="rider_area"></span>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="template-btn w-100">
                                    <i class="fas fa-user-check"></i> @lang('Assign Rider')
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
                <form action="{{ route('vendor.cancel.shipment') }}" method="POST">
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

    // Open shipping modal
    $(document).on('click', '.assignShippingBtn', function() {
        const orderId = $(this).data('order-id');
        const customerCity = $(this).data('customer-city');

        $('#modal_order_id').val(orderId);
        $('#tryoto_order_id').val(orderId);
        $('#rider_order_id').val(orderId);

        // Reset forms
        $('#shippingCompanySelect').html('<option value="">@lang("Loading shipping options...")</option>');
        $('#shippingDetails').addClass('d-none');
        $('#submitTryotoBtn').prop('disabled', true);

        // Load Tryoto options
        $.get("{{ route('vendor.shipping.options') }}", { order_id: orderId }, function(response) {
            if (response.success) {
                $('#shippingCompanySelect').html(response.options);
            } else {
                $('#shippingCompanySelect').html('<option value="">@lang("No shipping options available")</option>');
                if (response.error) {
                    toastr.error(response.error);
                }
            }
        }).fail(function() {
            $('#shippingCompanySelect').html('<option value="">@lang("Failed to load options")</option>');
        });

        // Load riders
        $.get("{{ route('vendor.find.rider') }}", { city: customerCity }, function(response) {
            $('#riderSelect').html(response.riders);
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

    // Rider selection
    $(document).on('change', '#riderSelect', function() {
        const selected = $(this).find('option:selected');

        if ($(this).val()) {
            $('#rider_name').text(selected.attr('riderName'));
            $('#rider_cost').text(selected.attr('riderCost'));
            $('#rider_area').text(selected.attr('area'));
            $('#riderDetails').removeClass('d-none');
        } else {
            $('#riderDetails').addClass('d-none');
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

        $.get("{{ route('vendor.track.shipment') }}", { tracking_number: trackingNumber }, function(response) {
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
