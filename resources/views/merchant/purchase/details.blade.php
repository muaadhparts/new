@extends('layouts.merchant')


@section('content')
    <!-- outlet start  -->
    <div class="gs-merchant-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-merchant-breadcrumb has-mb">
            <div class="d-flex align-items-center flex-wrap gap-4">
                <a class="back-btn" href="{{route("merchant-purchase-index")}}">
                    <i class="fa-solid fa-arrow-left-long"></i>
                    </a>
                <h4 class="text-capitalize">@lang('Purchase Details')</h4>
            </div>
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
                    <a href="{{ route('merchant-purchase-index') }}" class="text-capitalize"> @lang('Purchase') </a>
                </li>
                <li>
                    <a href="#" class="text-capitalize">@lang('Purchase Details') </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Purchase info cards start  -->
        <div class="gs-purchase-info-cards-wrapper row gy-4 row-cols-1 row-cols-md-2 row-cols-xxl-3">
            <!-- Purchase Details Card  -->
            <div class="col">
                <div class="purchase-info-card purchase-details-card ">
                    <div class="d-flex justify-content-between gap-4">

                        <h5 class="title">@lang('Purchase Details')
                        </h5>
                        @if (@App\Models\DeliveryCourier::where('merchant_id', auth()->id())->where('purchase_id', $purchase->id)->first()->status == 'delivered' && $purchase->merchantPurchases()->where('status', 'completed')->count() == 0)
                            <a href="{{ route('merchant-purchase-status', ['id1' => $purchase->purchase_number, 'status' => 'completed']) }}"
                                class="m-btn m-btn--success m-btn--sm">@lang('Make Complete')</a>
                        @endif
                    </div>
                    <ul class="info-list">
                        <li class="info-list-item">
                            <span class="info-type">@lang('Purchase ID')</span> <span class="info">{{ $purchase->purchase_number }}</span>
                        </li>
                        <li class="info-list-item">
                            <span class="info-type">@lang('Total CatalogItem')</span> <span
                                class="info">{{ $purchase->merchantPurchases()->where('user_id', '=', $user->id)->sum('qty') }}</span>
                        </li>

                        @php

                            $price = $purchase
                                ->merchantPurchases()
                                ->where('user_id', '=', $user->id)
                                ->sum('price');
                        @endphp

                        <li class="info-list-item">
                            <span class="info-type">@lang('Total Cost')</span> <span
                                class="info">{{ \PriceHelper::showOrderCurrencyPrice($price * $purchase->currency_value, $purchase->currency_sign) }}</span>
                        </li>

                        <li class="info-list-item">
                            <span class="info-type">@lang('Purchase Date')</span> <span
                                class="info">{{ date('d-M-Y H:i:s a', strtotime($purchase->created_at)) }}</span>
                        </li>


                        <li class="info-list-item">
                            <span class="info-type">@lang('Payment Method')</span> <span class="info">{{ $purchase->method }}</span>
                        </li>

                        <li class="info-list-item">
                            <span class="info-type">@lang('Transaction ID')</span> <span
                                class="info">{{ $purchase->txnid ?? '--' }}</span>
                        </li>


                        <li class="info-list-item">
                                <span class="info-type">@lang('Payment Status')</span>
                                @if ($purchase->payment_status == 'Pending')
                                    <span class="m-badge m-badge--danger">@lang('Unpaid')</span>
                                @else
                                    <span class="m-badge m-badge--paid">@lang('Paid')</span>
                                @endif
                        </li>

                        @if (!empty($purchase->purchase_note))
                            <li class="info-list-item">
                                <span class="info-type">@lang('Purchase Note')</span> <span
                                    class="info">{{ $purchase->purchase_note }}</span>
                            </li>
                        @endif


                    </ul>
                    <a href="{{ route('merchant-purchase-invoice', $purchase->purchase_number) }}"
                        class="m-btn m-btn--secondary m-btn--lg">@lang('View Invoice')</a>
                </div>
            </div>
            <!-- Billing Details Card  -->
            <div class="col">
                <div class="purchase-info-card billing-details-card">
                    <h5 class="title">@lang('Billing Details')</h5>
                    <ul class="info-list">
                        <li class="info-list-item">
                            <span class="info-type">@lang('Name')</span> <span class="info">{{ $purchase->customer_name }}</span>
                        </li>
                        <li class="info-list-item">
                            <span class="info-type">@lang('Email')</span> <span class="info">{{ $purchase->customer_email }}</span>
                        </li>
                        <li class="info-list-item">
                            <span class="info-type">@lang('Phone')</span> <span class="info">{{ $purchase->customer_phone }}</span>
                        </li>
                        <li class="info-list-item">
                            <span class="info-type">@lang('Address')</span> <span
                                class="info">{{ $purchase->customer_address }}</span>
                        </li>
                        <li class="info-list-item">
                            <span class="info-type">@lang('Country')</span> <span
                                class="info">{{ $purchase->customer_country }}</span>
                        </li>
                        <li class="info-list-item">
                            <span class="info-type">@lang('City')</span> <span class="info">{{ $purchase->customer_city }}</span>
                        </li>
                        <li class="info-list-item">
                            <span class="info-type">@lang('Postal Code')</span> <span
                                class="info">{{ $purchase->customer_zip }}</span>
                        </li>
                    </ul>
                </div>
            </div>



                <!-- Shipping Address Card  -->
                <div class="col">
                    <div class="purchase-info-card shipping-address-card">
                        <h5 class="title">@lang('Shipping Address')</h5>
                        <ul class="info-list">

                                <li class="info-list-item">
                                    <span class="info-type">@lang('Name')</span> <span
                                        class="info">{{ $purchase->customer_name }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Email')</span> <span
                                        class="info">{{ $purchase->customer_email }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Phone')</span> <span
                                        class="info">{{ $purchase->customer_phone }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Address')</span> <span
                                        class="info">{{ $purchase->customer_address }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Country')</span> <span
                                        class="info">{{ $purchase->customer_country }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('City')</span> <span
                                        class="info">{{ $purchase->customer_city }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Postal Code')</span> <span
                                        class="info">{{ $purchase->customer_zip }}</span>
                                </li>
                        </ul>
                    </div>
                </div>

            {{-- Shipment Status Card (Data from Controller - No Logic Here) --}}
            @if ($trackingData['hasTracking'])
                <div class="col">
                    <div class="purchase-info-card">
                        <h5 class="title">
                            <i class="fas fa-truck"></i> @lang('Shipping Status')
                        </h5>
                        <ul class="info-list">
                            @if ($trackingData['hasShipment'])
                                {{-- API/Manual Shipment Info --}}
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Shipping Company')</span>
                                    <span class="info">
                                        <span class="badge bg-info">{{ $trackingData['companyName'] }}</span>
                                    </span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Tracking Number')</span>
                                    <span class="info text-primary fw-bold">{{ $trackingData['trackingNumber'] }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Status')</span>
                                    <span class="info">
                                        <span class="badge bg-{{ $trackingData['statusColor'] }}">
                                            {{ $trackingData['statusDisplay'] }}
                                        </span>
                                    </span>
                                </li>
                                @if($trackingData['hasMessage'])
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Message')</span>
                                    <span class="info">{{ $trackingData['messageDisplay'] }}</span>
                                </li>
                                @endif
                                @if($trackingData['location'])
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Current Location')</span>
                                    <span class="info">{{ $trackingData['location'] }}</span>
                                </li>
                                @endif
                                @if($trackingData['occurredAt'])
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Last Update')</span>
                                    <span class="info">{{ $trackingData['occurredAt'] }}</span>
                                </li>
                                @endif
                                <li class="info-list-item">
                                    <a href="{{ route('merchant.shipment-tracking.show', $purchase->id) }}"
                                       class="m-btn m-btn--primary m-btn--sm">
                                        <i class="fas fa-map-marker-alt"></i> @lang('Track Shipment')
                                    </a>
                                </li>
                            @elseif ($trackingData['hasDelivery'])
                                {{-- Local Courier Info --}}
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Delivery Type')</span>
                                    <span class="info"><span class="badge bg-secondary">@lang('Local Courier')</span></span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Courier Name')</span>
                                    <span class="info">{{ $trackingData['courierName'] ?? 'N/A' }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Delivery Cost')</span>
                                    <span class="info">{{ PriceHelper::showAdminCurrencyPrice($trackingData['deliveryFee']) }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Status')</span>
                                    <span class="info">
                                        <span class="badge bg-{{ $trackingData['deliveryStatusBadgeColor'] }}">
                                            {{ $trackingData['deliveryStatusLabel'] }}
                                        </span>
                                    </span>
                                </li>
                            @elseif ($trackingData['hasCustomerChoice'])
                                {{-- Customer Choice (Not Yet Assigned) --}}
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Status')</span>
                                    <span class="info"><span class="badge bg-warning">@lang('Not Assigned')</span></span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Customer Selected')</span>
                                    <span class="info">
                                        @if ($trackingData['customerChoiceIsTryoto'])
                                            <span class="badge bg-primary">{{ $trackingData['customerChoiceCompany'] ?? 'Tryoto' }}</span>
                                            - {{ $purchase->currency_sign }}{{ $trackingData['customerChoicePriceFormatted'] }}
                                        @else
                                            {{ $trackingData['customerChoiceTitleDisplay'] }}
                                        @endif
                                    </span>
                                </li>
                                <li class="info-list-item">
                                    <a href="{{ route('merchant.delivery.index') }}" class="m-btn m-btn--primary m-btn--sm">
                                        <i class="fas fa-shipping-fast"></i> @lang('Assign Shipping')
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            @endif
        </div>
        <!-- Purchase info cards end  -->

        <!-- Table area start  -->
        <div class="merchant-table-wrapper purchase-details-table-wrapper">
            <h4 class="table-title">@lang('Items Purchased')</h4>
            <div class="user-table table-responsive  position-relative">
                <table  class="gs-data-table w-100">
                    <thead>
                        <tr>
                            <th><span class="header-title">@lang('CatalogItem ID#')</span></th>
                            <th><span class="header-title">@lang('Shop Name')</span></th>
                            <th><span class="header-title">@lang('Status')</span></th>
                            <th><span class="header-title">@lang('CatalogItem Title')</span></th>
                            <th><span class="header-title">@lang('Details')</span></th>
                            <th><span class="header-title">@lang('Total Price')</span></th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach ($cart['items'] as $key => $catalogItem)
                            @if ($catalogItem['item']['user_id'] != 0)
                                @if ($catalogItem['item']['user_id'] == $user->id)
                                    <tr>
                                        <!-- CatalogItem ID# -->
                                        <td class="text-start"><span class="content ">{{ $catalogItem['item']['id'] }}</span>
                                        </td>
                                        <!-- Shop Name -->
                                        <td class="text-start">
                                                @if ($catalogItem['item']['user_id'] != 0)
                                                    @php
                                                        $user = App\Models\User::find($catalogItem['item']['user_id']);
                                                    @endphp
                                                    @if (isset($user))
                                                        <a class="title-hover-color content" target="_blank"
                                                            href="{{ route('operator-merchant-show', $user->id) }}">{{ $user->shop_name }}</a>
                                                    @else
                                                        {{ __('Merchant Removed') }}
                                                    @endif
                                                @endif
                                        </td>
                                        <!-- Status -->
                                        <td>
                                            @if ($catalogItem['item']['user_id'] != 0)
                                                @php
                                                    $merchantPurchase = App\Models\MerchantPurchase::where('purchase_id', '=', $purchase->id)
                                                        ->where('user_id', '=', $catalogItem['item']['user_id'])
                                                        ->first();
                                                @endphp

                                                    @if ($merchantPurchase->status == 'pending')
                                                        <span class="m-badge m-badge--pending">{{ ucwords($merchantPurchase->status) }}</span>
                                                    @elseif($merchantPurchase->status == 'processing')
                                                        <span class="m-badge m-badge--processing">{{ ucwords($merchantPurchase->status) }}</span>
                                                    @elseif($merchantPurchase->status == 'on delivery')
                                                        <span class="m-badge m-badge--shipped">{{ ucwords($merchantPurchase->status) }}</span>
                                                    @elseif($merchantPurchase->status == 'completed')
                                                        <span class="m-badge m-badge--completed">{{ ucwords($merchantPurchase->status) }}</span>
                                                    @elseif($merchantPurchase->status == 'declined')
                                                        <span class="m-badge m-badge--cancelled">{{ ucwords($merchantPurchase->status) }}</span>
                                                    @endif
                                            @endif
                                        </td>

                                        <!-- CatalogItem Title -->
                                        <td>

                                            @if ($catalogItem['item']['user_id'] != 0)
                                            @php
                                                $user = App\Models\User::find(
                                                    $catalogItem['item']['user_id'],
                                                );
                                                $merchantOrderProductUrl = '#';
                                                if (isset($catalogItem['item']['slug']) && isset($catalogItem['user_id']) && isset($catalogItem['merchant_item_id'])) {
                                                    $merchantOrderProductUrl = route('front.catalog-item', [
                                                        'slug' => $catalogItem['item']['slug'],
                                                        'merchant_id' => $catalogItem['user_id'],
                                                        'merchant_item_id' => $catalogItem['merchant_item_id']
                                                    ]);
                                                } elseif (isset($catalogItem['item']['slug'])) {
                                                    $merchantOrderProductUrl = route('front.catalog-item.legacy', $catalogItem['item']['slug']);
                                                }
                                            @endphp
                                            <a class="title-hover-color content catalogItem-title d-inline-block" target="_blank"
                                                href="{{ $merchantOrderProductUrl }}">
                                                {{ getLocalizedCatalogItemName($catalogItem['item'], 30) }}
                                            </a>
                                            <br><small class="text-muted">PART_NUMBER: {{ $catalogItem['item']['part_number'] ?? 'N/A' }}</small>
                                        @endif





                                        </td>
                                        <!-- Details -->
                                        <td class="text-start">
                                            <div class="courier">

                                                @if ($catalogItem['size'])
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">@lang('Size :')</span>
                                                    <span class="value">{{ str_replace('-','',$catalogItem['size'],) }}</span>
                                                </div>
                                                @endif

                                                @if ($catalogItem['color'])
                                                @php
                                                    $clr = $catalogItem['color'];
                                                    $colorHex = is_array($clr) ? ($clr['code'] ?? $clr['color'] ?? '') : $clr;
                                                @endphp
                                                @if($colorHex)
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">{{ __('Color') }} :</span>
                                                    <span style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; border-radius: 50%; background: #{{ $colorHex }};" class="value"></span>
                                                </div>
                                                @endif
                                                @endif

                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">@lang('Price :')</span>
                                                    <span class="value">{{ \PriceHelper::showOrderCurrencyPrice($catalogItem['item_price'] * $purchase->currency_value, $purchase->currency_sign) }}</span>
                                                </div>



                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">@lang('Qty :')</span>
                                                    <span class="value">{{ $catalogItem['qty'] }}
                                                        {{ $catalogItem['item']['measure'] }}</span>
                                                </div>

                                                @if (!empty($catalogItem['keys']))
                                                            @foreach (array_combine(explode(',', $catalogItem['keys']), explode(',', $catalogItem['values'])) as $key => $value)
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">{{ ucwords(str_replace('_', ' ', $key)) }} :</span>
                                                    <span class="value">{{ $value }}</span>
                                                </div>
                                                @endforeach
                                                @endif

                                            </div>
                                        </td>
                                        <!-- Total Price -->
                                        <td class="text-start">
                                            <span class="content ">
                                                {{ \PriceHelper::showOrderCurrencyPrice($catalogItem['price'] * $purchase->currency_value, $purchase->currency_sign) }}
                                                        <small>{{ $catalogItem['discount'] == 0 ? '' : '(' . $catalogItem['discount'] . '% ' . __('Off') . ')' }}</small>
                                            </span>
                                        </td>
                                    </tr>
                                @endif
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Table area end  -->
    </div>
    <!-- outlet end  -->
@endsection
