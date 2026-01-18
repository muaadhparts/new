@extends('layouts.front')
@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.courier.sidebar')
                @php
                    $purchase = $data->purchase;
                @endphp
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <div class="ud-page-name-box gap-4">
                        <a href="{{ url()->previous() }}" class="back-btn">
                            <i class="fa-solid fa-arrow-left-long"></i>
                        </a>
                        <h3 class="ud-page-name">@lang('Delivery Details')</h3>
                    </div>

                    {{-- ✅ Delivery Workflow Progress Indicator (NEW WORKFLOW) --}}
                    @include('includes.delivery-workflow', ['delivery' => $data])

                    {{-- ✅ Action Buttons (NEW WORKFLOW) --}}
                    <div class="my-4">
                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            @if($data->isPendingApproval())
                                {{-- STEP 1: Courier Approve/Reject --}}
                                <a class="btn btn-lg btn-success"
                                    href="{{ route('courier-purchase-delivery-accept', $data->id) }}">
                                    <i class="fas fa-check me-2"></i> @lang('Approve Delivery')
                                </a>
                                <a class="btn btn-lg btn-outline-danger"
                                    href="{{ route('courier-purchase-delivery-reject', $data->id) }}">
                                    <i class="fas fa-times me-2"></i> @lang('Reject')
                                </a>
                            @elseif($data->isApproved())
                                {{-- STEP 2: Waiting for merchant --}}
                                <div class="alert alert-info w-100 text-center">
                                    <i class="fas fa-hourglass-half me-2"></i>
                                    <strong>@lang('Waiting for Merchant')</strong>
                                    <br>
                                    <small>@lang('The merchant is preparing the order. Please wait.')</small>
                                </div>
                            @elseif($data->isReadyForPickup())
                                {{-- STEP 3: Waiting for merchant to hand over --}}
                                <div class="alert alert-success w-100 text-center">
                                    <i class="fas fa-box me-2"></i>
                                    <strong>@lang('Order is Ready!')</strong>
                                    <br>
                                    <small>@lang('Go to the merchant location to pick up the order.')</small>
                                </div>
                            @elseif($data->isPickedUp())
                                {{-- STEP 4: Courier delivers to customer --}}
                                <a class="btn btn-lg btn-success"
                                    href="{{ route('courier-purchase-delivery-complete', $data->id) }}">
                                    <i class="fas fa-check-double me-2"></i> @lang('Mark as Delivered to Customer')
                                </a>
                            @elseif($data->isDelivered() || $data->isConfirmed())
                                {{-- Completed --}}
                                <div class="alert alert-success w-100 text-center">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>@lang('Delivery Completed!')</strong>
                                    @if($data->delivered_at)
                                        <br><small>@lang('Delivered on'): {{ $data->delivered_at->format('Y-m-d H:i') }}</small>
                                    @endif
                                    @if($data->isConfirmed())
                                        <br><span class="badge bg-success">@lang('Customer Confirmed')</span>
                                    @endif
                                </div>
                            @elseif($data->isRejected())
                                {{-- Rejected --}}
                                <div class="alert alert-danger w-100 text-center">
                                    <i class="fas fa-times-circle me-2"></i>
                                    <strong>@lang('You Rejected This Delivery')</strong>
                                    @if($data->rejection_reason)
                                        <br><small>@lang('Reason'): {{ $data->rejection_reason }}</small>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="delivery-details">
                        <div class="row g-4 my-3">
                            {{-- Customer Info --}}
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-primary text-white">
                                        <i class="fas fa-user me-2"></i> @lang('Customer - Deliver To')
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <strong><i class="fas fa-user"></i> @lang('Name'):</strong>
                                            {{ $purchase->customer_name }}
                                        </div>
                                        <div class="mb-2">
                                            <strong><i class="fas fa-phone"></i> @lang('Phone'):</strong>
                                            <a href="tel:{{ $purchase->customer_phone }}" class="text-primary">
                                                {{ $purchase->customer_phone }}
                                            </a>
                                        </div>
                                        <div class="mb-2">
                                            <strong><i class="fas fa-city"></i> @lang('City'):</strong>
                                            {{ $purchase->customer_city }}
                                        </div>
                                        <div class="mb-2">
                                            <strong><i class="fas fa-map-marker-alt"></i> @lang('Address'):</strong>
                                            {{ $purchase->customer_address }}
                                        </div>
                                        @if($purchase->customer_zip)
                                            <div class="mb-2">
                                                <strong><i class="fas fa-mail-bulk"></i> @lang('ZIP'):</strong>
                                                {{ $purchase->customer_zip }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Merchant Info --}}
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-header bg-info text-white">
                                        <i class="fas fa-store me-2"></i> @lang('Merchant - Pick Up From')
                                    </div>
                                    <div class="card-body">
                                        @if($data->merchant)
                                            <div class="mb-2">
                                                <strong><i class="fas fa-store"></i> @lang('Shop'):</strong>
                                                {{ $data->merchant->shop_name ?? $data->merchant->name ?? 'N/A' }}
                                            </div>
                                            @if($data->merchant->phone)
                                                <div class="mb-2">
                                                    <strong><i class="fas fa-phone"></i> @lang('Phone'):</strong>
                                                    <a href="tel:{{ $data->merchant->phone }}" class="text-primary">
                                                        {{ $data->merchant->phone }}
                                                    </a>
                                                </div>
                                            @endif
                                            @if($data->merchantBranch && $data->merchantBranch->location)
                                                <div class="mb-2 p-2 bg-light rounded">
                                                    <strong><i class="fas fa-warehouse text-success"></i> @lang('Pickup Location'):</strong>
                                                    <br>
                                                    <span class="text-success">{{ $data->merchantBranch->location }}</span>
                                                </div>
                                            @endif
                                            @if($data->merchant->address)
                                                <div class="mb-2">
                                                    <strong><i class="fas fa-map-marker-alt"></i> @lang('Address'):</strong>
                                                    {{ $data->merchant->address }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">@lang('Merchant information not available')</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payment Info - All values from database only, no calculations --}}
                    <div class="my-4">
                        @if ($data->isCod())
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-dark">
                                    <i class="fas fa-money-bill-wave me-2"></i>
                                    <strong>@lang('Cash on Delivery - Collect from Customer')</strong>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm mb-0">
                                        <tr class="table-warning">
                                            <td><strong>@lang('TOTAL TO COLLECT'):</strong></td>
                                            <td class="text-end">
                                                <strong class="fs-4 text-danger">
                                                    {{ \PriceHelper::showAdminCurrencyPrice($data->cod_amount, $purchase->currency_sign) }}
                                                </strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>@lang('Your Delivery Fee'):</td>
                                            <td class="text-end text-success">
                                                {{ \PriceHelper::showAdminCurrencyPrice($data->delivery_fee, $purchase->currency_sign) }}
                                            </td>
                                        </tr>
                                    </table>
                                    <div class="alert alert-info mt-3 mb-0 py-2">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <small>@lang('Collect the total amount from customer. Your delivery fee is included in this amount.')</small>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-credit-card me-2"></i>
                                    <strong>@lang('Online Payment - Already Paid')</strong>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2">
                                        <i class="fas fa-check-circle text-success"></i>
                                        @lang('Customer already paid online. Just deliver the order.')
                                    </p>
                                    <p class="mb-0">
                                        <strong>@lang('Your Delivery Fee'):</strong>
                                        <span class="text-success fs-5">
                                            {{ \PriceHelper::showAdminCurrencyPrice($data->delivery_fee, $purchase->currency_sign) }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Ordered Items --}}
                    <div class="ordered-catalogItems">
                        <h5 class="mb-3"><i class="fas fa-box-open me-2"></i> @lang('Items to Deliver')</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>@lang('ID')</th>
                                        <th>@lang('Item Name')</th>
                                        <th>@lang('Details')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($purchase->getCartItems() as $catalogItem)
                                        @if ($catalogItem['user_id'] == $data->merchant_id)
                                            <tr>
                                                <td>{{ $catalogItem['item']['id'] }}</td>
                                                <td>{{ getLocalizedCatalogItemName($catalogItem['item'], 50) }}</td>
                                                <td>
                                                    <strong>@lang('Qty'):</strong> {{ $catalogItem['qty'] }}
                                                    @if (!empty($catalogItem['size']))
                                                        <br><strong>@lang('Size'):</strong> {{ $catalogItem['item']['measure'] }}{{ str_replace('-', ' ', $catalogItem['size']) }}
                                                    @endif
                                                    @if (!empty($catalogItem['color']))
                                                        @php
                                                            $clr = $catalogItem['color'];
                                                            $colorHex = is_array($clr) ? ($clr['code'] ?? $clr['color'] ?? '') : $clr;
                                                        @endphp
                                                        @if($colorHex)
                                                        <br><strong>@lang('Color'):</strong>
                                                        <span style="width: 15px; height: 15px; display: inline-block; vertical-align: middle; border-radius: 50%; background: #{{ $colorHex }};"></span>
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
