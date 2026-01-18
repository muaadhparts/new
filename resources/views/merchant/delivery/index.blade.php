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
                                // ✅ استخدام البيانات المحملة مسبقاً من الـ Controller
                                // لا استعلامات قاعدة بيانات في الـ View
                                $pData = $purchaseData[$data->id] ?? [];
                                $delivery = $pData['delivery'] ?? null;
                                $shipment = $pData['shipment'] ?? null;
                                $customerChoice = $pData['customerChoice'] ?? null;
                                $price = $pData['price'] ?? 0;
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
                                    <small name="{{ $data->customer_address }}"><i class="fas fa-map-marker-alt"></i> {{ Str::limit($data->customer_address, 25) }}</small>
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
                                            {{-- ✅ عرض اسم الشركة حسب نوع الشحن --}}
                                            @php
                                                $shippingName = $customerChoice['company_name']
                                                    ?? $customerChoice['name']
                                                    ?? $customerChoice['courier_name']
                                                    ?? __('N/A');
                                            @endphp
                                            <span class="badge bg-primary">{{ $shippingName }}</span>
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
                                        {{-- ✅ Local Courier Delivery - NEW WORKFLOW --}}
                                        <span class="badge bg-secondary mb-1">
                                            <i class="fas fa-motorcycle"></i> @lang('Local Courier')
                                        </span>
                                        <br>
                                        <small><i class="fas fa-user"></i> {{ $delivery->courier->name ?? __('Awaiting Courier') }}</small>
                                        <br>
                                        {{-- ✅ Status Badge - NEW WORKFLOW --}}
                                        @if($delivery->isPendingApproval())
                                            <span class="badge bg-warning text-dark">
                                                <i class="fas fa-clock"></i> @lang('Waiting Approval')
                                            </span>
                                        @elseif($delivery->isApproved())
                                            <span class="badge bg-info">
                                                <i class="fas fa-box-open"></i> @lang('Courier Approved')
                                            </span>
                                        @elseif($delivery->isReadyForPickup())
                                            <span class="badge bg-success">
                                                <i class="fas fa-box"></i> @lang('Ready for Pickup')
                                            </span>
                                        @elseif($delivery->isPickedUp())
                                            <span class="badge bg-primary">
                                                <i class="fas fa-truck"></i> @lang('In Transit')
                                            </span>
                                        @elseif($delivery->isDelivered() || $delivery->isConfirmed())
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-double"></i> @lang('Delivered')
                                            </span>
                                        @elseif($delivery->isRejected())
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times"></i> @lang('Courier Rejected')
                                            </span>
                                        @endif
                                        {{-- ✅ Payment Method Indicator --}}
                                        @if($delivery->isCod())
                                            <br><small class="text-success"><i class="fas fa-money-bill"></i> @lang('COD'): {{ $data->currency_sign }}{{ number_format($delivery->cod_amount, 2) }}</small>
                                        @else
                                            <br><small class="text-info"><i class="fas fa-credit-card"></i> @lang('Paid Online')</small>
                                        @endif
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
                                    @elseif ($delivery && ($delivery->isDelivered() || $delivery->isConfirmed()))
                                        {{-- ✅ COMPLETED --}}
                                        <span class="badge bg-success mb-1"><i class="fas fa-check"></i> @lang('Completed')</span>
                                        <br>
                                        <a href="{{ route('merchant-purchase-show', $data->purchase_number) }}" class="btn btn-sm btn-success">
                                            <i class="fas fa-eye"></i> @lang('View')
                                        </a>
                                    @elseif ($delivery && $delivery->isPendingApproval())
                                        {{-- ✅ STEP 1: Waiting for courier approval --}}
                                        <div class="alert alert-warning py-1 px-2 mb-1" style="font-size: 0.75rem;">
                                            <i class="fas fa-clock"></i>
                                            @lang('Waiting courier approval')
                                        </div>
                                        <a href="{{ route('merchant-purchase-show', $data->purchase_number) }}" class="btn btn-sm btn-secondary w-100">
                                            <i class="fas fa-eye"></i> @lang('View')
                                        </a>
                                    @elseif ($delivery && $delivery->isApproved())
                                        {{-- ✅ STEP 2: Courier approved, merchant prepares --}}
                                        <div class="d-flex flex-column gap-1">
                                            <div class="alert alert-info py-1 px-2 mb-1" style="font-size: 0.75rem;">
                                                <i class="fas fa-box-open"></i>
                                                @lang('Courier approved! Prepare order')
                                            </div>
                                            <form action="{{ route('merchant.ready.courier') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="purchase_id" value="{{ $data->id }}">
                                                <button type="submit" class="btn btn-sm btn-success mb-1 w-100">
                                                    <i class="fas fa-box"></i> @lang('Mark Ready for Pickup')
                                                </button>
                                            </form>
                                            <a href="{{ route('merchant-purchase-show', $data->purchase_number) }}" class="btn btn-sm btn-secondary w-100">
                                                <i class="fas fa-eye"></i> @lang('View')
                                            </a>
                                        </div>
                                    @elseif ($delivery && $delivery->isReadyForPickup())
                                        {{-- ✅ STEP 3: Ready, waiting for courier to arrive --}}
                                        <div class="d-flex flex-column gap-1">
                                            <div class="alert alert-success py-1 px-2 mb-1" style="font-size: 0.75rem;">
                                                <i class="fas fa-box"></i>
                                                @lang('Ready! Courier coming to pick up')
                                            </div>
                                            <form action="{{ route('merchant.handover.courier') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="purchase_id" value="{{ $data->id }}">
                                                <button type="submit" class="btn btn-sm btn-primary mb-1 w-100">
                                                    <i class="fas fa-handshake"></i> @lang('Confirm Handover')
                                                </button>
                                            </form>
                                            <a href="{{ route('merchant-purchase-show', $data->purchase_number) }}" class="btn btn-sm btn-secondary w-100">
                                                <i class="fas fa-eye"></i> @lang('View')
                                            </a>
                                        </div>
                                    @elseif ($delivery && $delivery->isPickedUp())
                                        {{-- ✅ STEP 4: Courier picked up, delivering --}}
                                        <div class="alert alert-primary py-1 px-2 mb-1" style="font-size: 0.75rem;">
                                            <i class="fas fa-truck"></i>
                                            @lang('Courier delivering to customer')
                                        </div>
                                        <a href="{{ route('merchant-purchase-show', $data->purchase_number) }}" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-eye"></i> @lang('View')
                                        </a>
                                    @elseif ($delivery && $delivery->isRejected())
                                        {{-- ✅ Rejected - Needs reassignment --}}
                                        <div class="alert alert-danger py-1 px-2 mb-1" style="font-size: 0.75rem;">
                                            <i class="fas fa-exclamation-circle"></i>
                                            @lang('Courier rejected - Reassign')
                                        </div>
                                        <button type="button" class="btn btn-sm btn-primary mb-1 assignShippingBtn"
                                            data-purchase-id="{{ $data->id }}"
                                            data-customer-city="{{ $data->customer_city }}"
                                            data-customer-choice='@json($customerChoice)'
                                            data-bs-toggle="modal" data-bs-target="#shippingModal">
                                            <i class="fas fa-redo"></i> @lang('Reassign')
                                        </button>
                                        <br>
                                        <a href="{{ route('merchant-purchase-show', $data->purchase_number) }}" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-eye"></i> @lang('View')
                                        </a>
                                    @else
                                        {{-- ✅ No delivery assigned - Assign shipping --}}
                                        <button type="button" class="btn btn-sm btn-primary mb-1 assignShippingBtn"
                                            data-purchase-id="{{ $data->id }}"
                                            data-customer-city="{{ $data->customer_city }}"
                                            data-customer-choice='@json($customerChoice)'
                                            data-bs-toggle="modal" data-bs-target="#shippingModal">
                                            <i class="fas fa-shipping-fast"></i> @lang('Assign Shipping')
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
                    <h5 class="modal-name">@lang('Assign Shipping Method')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="modal_purchase_id" value="">

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

                    {{-- ✅ Dynamic Provider Tabs (Built from database) --}}
                    <ul class="nav nav-tabs mb-3" id="shippingProviderTabs" role="tablist">
                        {{-- Tabs will be built dynamically via JavaScript --}}
                        <li class="nav-item" role="presentation">
                            <span class="nav-link disabled">
                                <i class="fas fa-spinner fa-spin"></i> @lang('Loading providers...')
                            </span>
                        </li>
                    </ul>

                    <div class="tab-content" id="shippingProviderContent">
                        {{-- Tab content will be built dynamically via JavaScript --}}
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
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
                    <h5 class="modal-name">@lang('Shipment Tracking')</h5>
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
                        <h5 class="modal-name">@lang('Cancel Shipment')</h5>
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

    // Store data globally
    let currentCustomerChoice = null;
    let currentPurchaseId = null;
    let currentCustomerCity = null;
    let loadedProviders = [];
    let merchantBranches = [];
    let defaultBranchId = null;

    // Open shipping modal
    $(document).on('click', '.assignShippingBtn', function() {
        currentPurchaseId = $(this).data('purchase-id');
        currentCustomerCity = $(this).data('customer-city');
        currentCustomerChoice = $(this).data('customer-choice');

        // Set purchase ID
        $('#modal_purchase_id').val(currentPurchaseId);

        // ✅ Show customer choice alert
        showCustomerChoiceAlert();

        // ✅ Show free shipping warning if applicable
        if (currentCustomerChoice && currentCustomerChoice.is_free_shipping) {
            $('#freeShippingWarning').removeClass('d-none');
            $('#originalShippingPrice').text((currentCustomerChoice.original_price || 0) + ' @lang("SAR")');
        } else {
            $('#freeShippingWarning').addClass('d-none');
        }

        // ✅ Load merchant branches first, then providers
        loadMerchantBranches(function() {
            // After branches loaded, build provider tabs
            loadShippingProviders();
        });
    });

    // ✅ Load merchant branches (warehouses/pickup points)
    function loadMerchantBranches(callback) {
        $.get("{{ route('merchant.delivery.branches') }}", function(response) {
            if (response.success && response.branches.length > 0) {
                merchantBranches = response.branches;
                defaultBranchId = response.default_id;

                // Call callback to continue loading providers
                if (callback) callback();
            } else {
                // No branches configured - show error
                merchantBranches = [];
                defaultBranchId = null;
                showNoBranchesError(response.error);
            }
        }).fail(function() {
            merchantBranches = [];
            defaultBranchId = null;
            showNoBranchesError('@lang("Failed to load warehouse branches")');
        });
    }

    // ✅ Show error when no branches configured
    function showNoBranchesError(errorMessage) {
        $('#shippingProviderTabs').html(`
            <li class="nav-item">
                <span class="nav-link text-danger">
                    <i class="fas fa-exclamation-triangle"></i> @lang('Configuration Required')
                </span>
            </li>
        `);
        $('#shippingProviderContent').html(`
            <div class="alert alert-danger">
                <i class="fas fa-warehouse me-2"></i>
                <strong>@lang('Warehouse Branch Required')</strong>
                <br>
                ${errorMessage || '@lang("No warehouse branches configured.")'}
                <br><br>
                <a href="{{ route('merchant-branch-index') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> @lang('Add Warehouse Branch')
                </a>
            </div>
        `);
    }

    // ✅ Build merchant branch dropdown HTML
    function buildBranchDropdownHtml(idPrefix) {
        if (!merchantBranches || merchantBranches.length === 0) {
            return `
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    @lang('No warehouse branches configured.')
                    <a href="{{ route('merchant-branch-index') }}" class="btn btn-sm btn-outline-primary ms-2">
                        <i class="fas fa-plus"></i> @lang('Add Branch')
                    </a>
                </div>
            `;
        }

        // If only one branch, auto-select and show info (no dropdown needed)
        if (merchantBranches.length === 1) {
            const branch = merchantBranches[0];
            const warningHtml = !branch.has_tryoto_code ? `
                <div class="alert alert-warning py-1 px-2 mt-2 mb-0 small">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    @lang('Tryoto Warehouse Code not configured.')
                    <a href="{{ route('merchant-branch-index') }}">@lang('Configure')</a>
                </div>
            ` : '';

            return `
                <input type="hidden" name="merchant_branch_id" id="${idPrefix}_merchant_branch_id" value="${branch.id}">
                <div class="card mb-3 ${branch.has_tryoto_code ? 'border-success' : 'border-warning'}">
                    <div class="card-body py-2">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-warehouse ${branch.has_tryoto_code ? 'text-success' : 'text-warning'} me-2"></i>
                            <div>
                                <small class="text-muted d-block">@lang('Pickup Branch')</small>
                                <strong>${escapeHtml(branch.display_name)}</strong>
                            </div>
                        </div>
                        ${warningHtml}
                    </div>
                </div>
            `;
        }

        // Multiple branches - show dropdown
        let optionsHtml = `<option value="">@lang('Select Pickup Branch')</option>`;
        merchantBranches.forEach(function(branch) {
            const selected = branch.id === defaultBranchId ? 'selected' : '';
            optionsHtml += `<option value="${branch.id}" ${selected}>${escapeHtml(branch.display_name)}</option>`;
        });

        return `
            <div class="mb-3">
                <label class="form-label">
                    <i class="fas fa-warehouse me-1"></i>
                    @lang('Pickup Branch') <span class="text-danger">*</span>
                </label>
                <select class="form-select merchant-branch-select" id="${idPrefix}_branch_select" required>
                    ${optionsHtml}
                </select>
                <input type="hidden" name="merchant_branch_id" id="${idPrefix}_merchant_branch_id" value="${defaultBranchId || ''}">
                <small class="text-muted">@lang('Select the warehouse from which this shipment will be picked up')</small>
            </div>
        `;
    }

    // ✅ Show customer choice alert based on provider
    function showCustomerChoiceAlert() {
        if (!currentCustomerChoice || !currentCustomerChoice.provider) {
            $('#customerChoiceAlert').addClass('d-none');
            return;
        }

        let providerText = '<span class="badge bg-secondary">' +
            (currentCustomerChoice.company_name || currentCustomerChoice.name || currentCustomerChoice.provider) +
            '</span>';

        $('#customerChoiceAlert').removeClass('d-none');
        $('#customerChoiceText').html(
            providerText + ' - @lang("Price"): ' + (currentCustomerChoice.price || 0) + ' @lang("SAR")'
        );
    }

    // ✅ Load shipping providers and build tabs
    function loadShippingProviders() {
        $.get("{{ route('merchant.shipping.providers') }}", function(response) {
            if (response.success && response.providers.length > 0) {
                loadedProviders = response.providers;
                buildProviderTabs(response.providers);
            } else {
                $('#shippingProviderTabs').html(`
                    <li class="nav-item">
                        <span class="nav-link text-danger">
                            <i class="fas fa-exclamation-triangle"></i> @lang('No shipping providers available')
                        </span>
                    </li>
                `);
                $('#shippingProviderContent').html(`
                    <div class="alert alert-warning">
                        @lang('No shipping providers configured. Please contact administrator.')
                    </div>
                `);
            }
        }).fail(function() {
            $('#shippingProviderTabs').html(`
                <li class="nav-item">
                    <span class="nav-link text-danger">
                        <i class="fas fa-exclamation-triangle"></i> @lang('Failed to load providers')
                    </span>
                </li>
            `);
        });
    }

    // ✅ Build tabs dynamically from providers
    function buildProviderTabs(providers) {
        let tabsHtml = '';
        let contentHtml = '';
        let firstTab = true;

        // Determine which tab to show first based on customer choice
        let defaultProvider = providers[0]?.key || 'tryoto';
        if (currentCustomerChoice && currentCustomerChoice.provider) {
            const matchingProvider = providers.find(p => p.key === currentCustomerChoice.provider);
            if (matchingProvider) {
                defaultProvider = matchingProvider.key;
            }
        }

        providers.forEach(function(provider, index) {
            const isActive = provider.key === defaultProvider;
            const tabId = 'provider-' + provider.key;

            // Build tab button
            tabsHtml += `
                <li class="nav-item" role="presentation">
                    <button class="nav-link ${isActive ? 'active' : ''}"
                            id="${tabId}-tab"
                            data-bs-toggle="tab"
                            data-bs-target="#${tabId}-content"
                            data-provider="${provider.key}"
                            data-has-api="${provider.has_api}"
                            type="button" role="tab"
                            aria-controls="${tabId}-content"
                            aria-selected="${isActive}">
                        <i class="${provider.icon}"></i> ${provider.label}
                    </button>
                </li>
            `;

            // Build tab content
            if (provider.has_api && provider.key === 'tryoto') {
                // Tryoto has API integration
                contentHtml += buildTryotoTabContent(tabId, isActive);
            } else {
                // Other providers - generic form
                contentHtml += buildGenericProviderTabContent(tabId, provider, isActive);
            }
        });

        $('#shippingProviderTabs').html(tabsHtml);
        $('#shippingProviderContent').html(contentHtml);

        // Load data for the active tab
        loadProviderData(defaultProvider);

        // Handle tab change
        $(document).on('shown.bs.tab', '#shippingProviderTabs button[data-bs-toggle="tab"]', function(e) {
            const provider = $(e.target).data('provider');
            loadProviderData(provider);
        });
    }

    // ✅ Build Tryoto tab content (API-based) with weight/dimensions inputs
    function buildTryotoTabContent(tabId, isActive) {
        // Build location dropdown HTML
        const locationHtml = buildBranchDropdownHtml('tryoto');

        return `
            <div class="tab-pane fade ${isActive ? 'show active' : ''}" id="${tabId}-content" role="tabpanel">
                <form id="tryotoForm" action="{{ route('merchant.send.tryoto') }}" method="POST">
                    @csrf
                    <input type="hidden" name="purchase_id" class="provider-purchase-id" value="${currentPurchaseId}">
                    <input type="hidden" name="delivery_option_id" id="delivery_option_id">
                    <input type="hidden" name="company" id="selected_company">
                    <input type="hidden" name="price" id="selected_price">
                    <input type="hidden" name="service_type" id="selected_service_type">

                    {{-- ✅ Pickup Location Selection --}}
                    ${locationHtml}

                    {{-- ✅ عرض مسار الشحن (من → إلى) --}}
                    <div class="card mb-3 border-primary">
                        <div class="card-body py-2">
                            <div class="row text-center">
                                <div class="col-5">
                                    <small class="text-muted d-block">@lang('From')</small>
                                    <strong id="tryoto_origin_city" class="text-primary">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </strong>
                                </div>
                                <div class="col-2 align-self-center">
                                    <i class="fas fa-arrow-right text-muted"></i>
                                </div>
                                <div class="col-5">
                                    <small class="text-muted d-block">@lang('To')</small>
                                    <strong id="tryoto_destination_city" class="text-success">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </strong>
                                </div>
                            </div>
                            <div id="tryoto_nearest_city_notice" class="d-none text-center mt-2">
                                <small class="text-warning">
                                    <i class="fas fa-info-circle"></i>
                                    <span id="tryoto_nearest_city_text"></span>
                                </small>
                            </div>
                        </div>
                    </div>

                    {{-- ✅ مربعات الوزن والأبعاد للبحث مجدداً --}}
                    <div class="card mb-3 border-secondary">
                        <div class="card-header bg-light py-2">
                            <strong><i class="fas fa-box"></i> @lang('Package Dimensions')</strong>
                            <small class="text-muted d-block">@lang('Adjust if package dimensions changed after preparation')</small>
                        </div>
                        <div class="card-body py-2">
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <label class="form-label small mb-1">@lang('Weight') (kg)</label>
                                    <input type="number" step="0.1" min="0.1" class="form-control form-control-sm" id="tryoto_weight" value="1">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small mb-1">@lang('Length') (cm)</label>
                                    <input type="number" step="1" min="1" class="form-control form-control-sm" id="tryoto_length" value="30">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small mb-1">@lang('Width') (cm)</label>
                                    <input type="number" step="1" min="1" class="form-control form-control-sm" id="tryoto_width" value="30">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label small mb-1">@lang('Height') (cm)</label>
                                    <input type="number" step="1" min="1" class="form-control form-control-sm" id="tryoto_height" value="30">
                                </div>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100" id="tryotoResearchBtn">
                                    <i class="fas fa-search"></i> @lang('Search Again with New Dimensions')
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">@lang('Select Shipping Company')</label>
                        <select class="form-select" id="tryotoShippingSelect" required>
                            <option value="">@lang('Loading shipping options...')</option>
                        </select>
                    </div>

                    <div id="tryotoDetails" class="d-none mb-3 p-3 bg-light rounded">
                        <div class="row">
                            <div class="col-6">
                                <strong>@lang('Company:')</strong>
                                <span id="tryoto_detail_company"></span>
                            </div>
                            <div class="col-6">
                                <strong>@lang('Price:')</strong>
                                <span id="tryoto_detail_price"></span>
                            </div>
                            <div class="col-12 mt-2">
                                <strong>@lang('Estimated Delivery:')</strong>
                                <span id="tryoto_detail_days"></span> @lang('days')
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
        `;
    }

    // ✅ Build generic provider tab content (non-API)
    function buildGenericProviderTabContent(tabId, provider, isActive) {
        // Build location dropdown HTML with unique prefix
        const locationHtml = buildBranchDropdownHtml('provider_' + provider.key);

        return `
            <div class="tab-pane fade ${isActive ? 'show active' : ''}" id="${tabId}-content" role="tabpanel">
                <form class="providerShippingForm" action="{{ route('merchant.send.provider.shipping') }}" method="POST">
                    @csrf
                    <input type="hidden" name="purchase_id" class="provider-purchase-id" value="${currentPurchaseId}">
                    <input type="hidden" name="shipping_id" class="provider-shipping-id">

                    {{-- ✅ Pickup Location Selection --}}
                    ${locationHtml}

                    <div class="mb-3">
                        <label class="form-label">@lang('Select Shipping Option')</label>
                        <select class="form-select provider-shipping-select" data-provider="${provider.key}" required>
                            <option value="">@lang('Loading...')</option>
                        </select>
                    </div>

                    <div class="provider-details d-none mb-3 p-3 bg-light rounded">
                        <div class="row">
                            <div class="col-6">
                                <strong>@lang('Method:')</strong>
                                <span class="detail-name"></span>
                            </div>
                            <div class="col-6">
                                <strong>@lang('Price:')</strong>
                                <span class="detail-price"></span>
                            </div>
                            <div class="col-12 mt-2 detail-subname-row d-none">
                                <strong>@lang('Delivery Time:')</strong>
                                <span class="detail-subname"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">@lang('Tracking Number (Optional)')</label>
                        <input type="text" name="tracking_number" class="form-control" placeholder="@lang('Enter tracking number if available')">
                    </div>

                    <div class="alert alert-secondary">
                        <i class="fas fa-info-circle"></i>
                        @lang('Select a shipping option and optionally enter a tracking number.')
                    </div>

                    <button type="submit" class="template-btn w-100 submit-provider-btn" disabled>
                        <i class="fas fa-check"></i> @lang('Assign Shipping')
                    </button>
                </form>
            </div>
        `;
    }

    // ✅ Load provider data
    function loadProviderData(providerKey) {
        const provider = loadedProviders.find(p => p.key === providerKey);
        if (!provider) return;

        if (provider.has_api && providerKey === 'tryoto') {
            loadTryotoOptions();
        } else {
            loadProviderOptions(providerKey);
        }
    }

    // ✅ Load Tryoto options (API) with dimensions support
    function loadTryotoOptions(customDimensions = null) {
        // إعداد البيانات للإرسال
        let requestData = { purchase_id: currentPurchaseId };

        // إذا كان هناك أبعاد مخصصة (بحث مجدداً)
        if (customDimensions) {
            requestData = { ...requestData, ...customDimensions };
        }

        // إظهار حالة التحميل
        $('#tryotoShippingSelect').html('<option value="">@lang("Loading shipping options...")</option>');
        $('#tryotoResearchBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> @lang("Searching...")');

        $.get("{{ route('merchant.shipping.options') }}", requestData, function(response) {
            // إعادة تفعيل زر البحث
            $('#tryotoResearchBtn').prop('disabled', false).html('<i class="fas fa-search"></i> @lang("Search Again with New Dimensions")');

            if (response.success) {
                $('#tryotoShippingSelect').html(response.options);

                // ✅ عرض مسار الشحن (من → إلى)
                if (response.origin) {
                    $('#tryoto_origin_city').text(response.origin);
                }
                if (response.destination) {
                    $('#tryoto_destination_city').text(response.destination);

                    // ✅ إذا تم استخدام أقرب مدينة مختلفة عن مدينة العميل الأصلية
                    if (response.original_city && response.destination !== response.original_city) {
                        $('#tryoto_nearest_city_notice').removeClass('d-none');
                        $('#tryoto_nearest_city_text').text(
                            '@lang("Original city"): ' + response.original_city +
                            ' → @lang("Nearest supported"): ' + response.destination
                        );
                    } else {
                        $('#tryoto_nearest_city_notice').addClass('d-none');
                    }
                }

                // ✅ ملء مربعات الأبعاد بالقيم من الطلب (فقط المرة الأولى)
                if (!customDimensions && response.dimensions) {
                    $('#tryoto_weight').val(response.dimensions.weight || 1);
                    $('#tryoto_length').val(response.dimensions.length || 30);
                    $('#tryoto_width').val(response.dimensions.width || 30);
                    $('#tryoto_height').val(response.dimensions.height || 30);
                }

                // ✅ الاختيار التلقائي لشركة العميل
                let customerChoiceForTryoto = response.customer_choice || currentCustomerChoice;

                // DEBUG: Log customer choice data
                console.log('=== AUTO-SELECT DEBUG ===');
                console.log('response.customer_choice:', response.customer_choice);
                console.log('currentCustomerChoice:', currentCustomerChoice);
                console.log('customerChoiceForTryoto:', customerChoiceForTryoto);

                if (customerChoiceForTryoto && customerChoiceForTryoto.provider === 'tryoto' && customerChoiceForTryoto.delivery_option_id) {
                    console.log('Looking for option with value:', customerChoiceForTryoto.delivery_option_id);

                    const optionToSelect = $('#tryotoShippingSelect option[value="' + customerChoiceForTryoto.delivery_option_id + '"]');
                    console.log('Option found:', optionToSelect.length > 0 ? 'YES' : 'NO');

                    // DEBUG: Log all available options
                    console.log('Available options:');
                    $('#tryotoShippingSelect option').each(function() {
                        console.log('  - value:', $(this).val(), '| text:', $(this).text());
                    });

                    if (optionToSelect.length) {
                        optionToSelect.prop('selected', true);
                        $('#tryotoShippingSelect').trigger('change');
                        if (!customDimensions) {
                            toastr.info('@lang("Customer\'s preferred shipping company selected automatically")');
                        }
                    } else {
                        console.log('❌ Option NOT found in the list!');
                    }
                } else {
                    console.log('❌ No valid customer choice or not tryoto provider');
                }
            } else {
                $('#tryotoShippingSelect').html('<option value="">@lang("Shipping temporarily unavailable")</option>');
                $('#tryoto_origin_city').text('--');
                $('#tryoto_destination_city').text('--');
                if (response.error) {
                    toastr.error(response.error);
                }
            }
        }).fail(function() {
            $('#tryotoResearchBtn').prop('disabled', false).html('<i class="fas fa-search"></i> @lang("Search Again with New Dimensions")');
            $('#tryotoShippingSelect').html('<option value="">@lang("Connection error - Please try again")</option>');
            $('#tryoto_origin_city').text('--');
            $('#tryoto_destination_city').text('--');
        });
    }

    // ✅ البحث مجدداً بأبعاد جديدة
    $(document).on('click', '#tryotoResearchBtn', function() {
        const customDimensions = {
            weight: parseFloat($('#tryoto_weight').val()) || 1,
            length: parseFloat($('#tryoto_length').val()) || 30,
            width: parseFloat($('#tryoto_width').val()) || 30,
            height: parseFloat($('#tryoto_height').val()) || 30
        };

        // التحقق من القيم
        if (customDimensions.weight <= 0) {
            toastr.warning('@lang("Please enter a valid weight")');
            return;
        }

        loadTryotoOptions(customDimensions);
    });

    // ✅ Load provider options (non-API)
    function loadProviderOptions(providerKey) {
        const $select = $(`.provider-shipping-select[data-provider="${providerKey}"]`);

        $.get("{{ route('merchant.provider.shipping.options') }}", { provider: providerKey }, function(response) {
            if (response.success && response.options.length > 0) {
                let html = '<option value="">@lang("Select Shipping Option")</option>';
                response.options.forEach(function(option) {
                    html += `<option value="${option.id}"
                                data-name="${escapeHtml(option.name)}"
                                data-subname="${escapeHtml(option.subname || '')}"
                                data-price="${option.price}"
                                data-display-price="${option.display_price}">
                                ${option.name} - ${option.display_price}
                            </option>`;
                });
                $select.html(html);

                // Auto-select if customer chose this provider
                if (currentCustomerChoice && currentCustomerChoice.provider === providerKey && currentCustomerChoice.shipping_id) {
                    const optionToSelect = $select.find(`option[value="${currentCustomerChoice.shipping_id}"]`);
                    if (optionToSelect.length) {
                        optionToSelect.prop('selected', true);
                        $select.trigger('change');
                    }
                }
            } else {
                $select.html('<option value="">@lang("No options available")</option>');
            }
        }).fail(function() {
            $select.html('<option value="">@lang("Failed to load options")</option>');
        });
    }

    // ✅ Tryoto selection change
    $(document).on('change', '#tryotoShippingSelect', function() {
        const selected = $(this).find('option:selected');
        const deliveryOptionId = $(this).val();

        if (deliveryOptionId) {
            $('#delivery_option_id').val(deliveryOptionId);
            $('#selected_company').val(selected.data('company'));
            $('#selected_price').val(selected.data('price'));
            $('#selected_service_type').val(selected.data('service-type') || 'express');

            $('#tryoto_detail_company').text(selected.data('company'));
            $('#tryoto_detail_price').text(selected.data('display-price'));
            $('#tryoto_detail_days').text(selected.data('days') || 'N/A');

            $('#tryotoDetails').removeClass('d-none');
            $('#submitTryotoBtn').prop('disabled', false);
        } else {
            $('#tryotoDetails').addClass('d-none');
            $('#submitTryotoBtn').prop('disabled', true);
        }
    });

    // ✅ Generic provider selection change
    $(document).on('change', '.provider-shipping-select', function() {
        const $form = $(this).closest('form');
        const selected = $(this).find('option:selected');
        const shippingId = $(this).val();

        if (shippingId) {
            $form.find('.provider-shipping-id').val(shippingId);
            $form.find('.detail-name').text(selected.data('name'));
            $form.find('.detail-price').text(selected.data('display-price'));

            const subname = selected.data('subname');
            if (subname) {
                $form.find('.detail-subname').text(subname);
                $form.find('.detail-subname-row').removeClass('d-none');
            } else {
                $form.find('.detail-subname-row').addClass('d-none');
            }

            $form.find('.provider-details').removeClass('d-none');
            $form.find('.submit-provider-btn').prop('disabled', false);
        } else {
            $form.find('.provider-details').addClass('d-none');
            $form.find('.submit-provider-btn').prop('disabled', true);
        }
    });

    // Handle merchant branch dropdown change
    $(document).on('change', '.merchant-branch-select', function() {
        const selectedId = $(this).val();
        const $form = $(this).closest('form');
        $form.find('input[name="merchant_branch_id"]').val(selectedId);
    });

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/&/g, "&amp;")
                   .replace(/</g, "&lt;")
                   .replace(/>/g, "&gt;")
                   .replace(/"/g, "&quot;")
                   .replace(/'/g, "&#039;");
    }

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
