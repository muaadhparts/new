@extends('layouts.vendor')


@section('content')
    <!-- outlet start  -->
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <div class="d-flex align-items-center flex-wrap gap-4">
                <a class="back-btn" href="{{route("vendor-order-index")}}">
                    <i class="fa-solid fa-arrow-left-long"></i>
                    </a>
                <h4 class="text-capitalize">@lang('Order Details')</h4>
            </div>
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
                    <a href="{{ route('vendor-order-index') }}" class="text-capitalize"> @lang('Order') </a>
                </li>
                <li>
                    <a href="#" class="text-capitalize">@lang('Order Details') </a>
                </li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Order info cards start  -->
        <div class="gs-order-info-cards-wrapper row gy-4 row-cols-1 row-cols-md-2 row-cols-xxl-3">
            <!-- Order Details Card  -->
            <div class="col">
                <div class="order-info-card order-details-card ">
                    <div class="d-flex justify-content-between gap-4">

                        <h5 class="title">@lang('Order Details')
                        </h5>
                        @if (@App\Models\DeliveryRider::where('vendor_id', auth()->id())->where('order_id', $order->id)->first()->status == 'delivered' && $order->vendororders()->where('status', 'completed')->count() == 0)
                            <a href="{{ route('vendor-order-status', ['id1' => $order->order_number, 'status' => 'completed']) }}"
                                class="template-btn  sm-btn">@lang('Make Complete')</a>
                        @endif
                    </div>
                    <ul class="info-list">
                        <li class="info-list-item">
                            <span class="info-type">@lang('Order ID')</span> <span class="info">{{ $order->order_number }}</span>
                        </li>
                        <li class="info-list-item">
                            <span class="info-type">@lang('Total Product')</span> <span
                                class="info">{{ $order->vendororders()->where('user_id', '=', $user->id)->sum('qty') }}</span>
                        </li>

                        @php

                            $price = $order
                                ->vendororders()
                                ->where('user_id', '=', $user->id)
                                ->sum('price');
                            if ($order->is_shipping == 1) {
                                $vendor_shipping = json_decode($order->vendor_shipping_id);
                                $user_id = auth()->id();
                                // shipping cost
                                $shipping_id = $vendor_shipping->$user_id;
                                $shipping = App\Models\Shipping::findOrFail($shipping_id);
                                if ($shipping) {
                                    $price = $price + round($shipping->price * $order->currency_value, 2);
                                }

                                // packaging cost
                                $vendor_packing_id = json_decode($order->vendor_packing_id);
                                $packing_id = $vendor_packing_id->$user_id;
                                $packaging = App\Models\Package::findOrFail($packing_id);
                                if ($packaging) {
                                    $price = $price + round($packaging->price * $order->currency_value, 2);
                                }
                            }

                        @endphp


                        <li class="info-list-item">
                            <span class="info-type">@lang('Total Cost')</span> <span
                                class="info">{{ \PriceHelper::showOrderCurrencyPrice($price * $order->currency_value, $order->currency_sign) }}</span>
                        </li>


                        @if (isset($shipping))
                            <li class="info-list-item">
                                <span class="info-type">@lang('Shipping Method')</span> <span class="info">{{ $shipping->title }} |
                                    {{ \PriceHelper::showOrderCurrencyPrice($shipping->price * $order->currency_value, $order->currency_sign) }}</span>
                            </li>
                        @endif

                        @if (isset($packaging))
                            <li class="info-list-item">
                                <span class="info-type">@lang('Packaging Method')</span> <span class="info">{{ $packaging->title }}
                                    |
                                    {{ \PriceHelper::showOrderCurrencyPrice($packaging->price * $order->currency_value, $order->currency_sign) }}</span>
                            </li>
                        @endif


                        <li class="info-list-item">
                            <span class="info-type">@lang('Ordered Date')</span> <span
                                class="info">{{ date('d-M-Y H:i:s a', strtotime($order->created_at)) }}</span>
                        </li>


                        <li class="info-list-item">
                            <span class="info-type">@lang('Payment Method')</span> <span class="info">{{ $order->method }}</span>
                        </li>

                        <li class="info-list-item">
                            <span class="info-type">@lang('Transaction ID')</span> <span
                                class="info">{{ $order->txnid ?? '--' }}</span>
                        </li>


                        <li class="info-list-item">

                                @if ($order->payment_status == 'Pending')
                                <span class="info-type">@lang('Payment Status')</span> <span class="template-btn danger-btn sm-btn">
                                    @lang('Unpaid')
                                </span>
                                @else
                                <span class="info-type">@lang('Payment Status')</span> <span class="template-btn green-btn sm-btn">
                                    @lang('Paid')
                                </span>
                                @endif

                        </li>

                        @if (!empty($order->order_note))
                            <li class="info-list-item">
                                <span class="info-type">@lang('Order Note')</span> <span
                                    class="info">{{ $order->order_note }}</span>
                            </li>
                        @endif


                    </ul>
                    <a href="{{ route('vendor-order-invoice', $order->order_number) }}"
                        class="template-btn black-btn lg-btn">@lang('View Invoice')</a>
                </div>
            </div>
            <!-- Billing Details Card  -->
            <div class="col">
                <div class="order-info-card billing-details-card">
                    <h5 class="title">@lang('Billing Details')</h5>
                    <ul class="info-list">
                        <li class="info-list-item">
                            <span class="info-type">@lang('Name')</span> <span class="info">{{ $order->customer_name }}</span>
                        </li>
                        <li class="info-list-item">
                            <span class="info-type">@lang('Email')</span> <span class="info">{{ $order->customer_email }}</span>
                        </li>
                        <li class="info-list-item">
                            <span class="info-type">@lang('Phone')</span> <span class="info">{{ $order->customer_phone }}</span>
                        </li>
                        <li class="info-list-item">
                            <span class="info-type">@lang('Address')</span> <span
                                class="info">{{ $order->customer_address }}</span>
                        </li>
                        <li class="info-list-item">
                            <span class="info-type">@lang('Country')</span> <span
                                class="info">{{ $order->customer_country }}</span>
                        </li>
                        <li class="info-list-item">
                            <span class="info-type">@lang('City')</span> <span class="info">{{ $order->customer_city }}</span>
                        </li>
                        <li class="info-list-item">
                            <span class="info-type">@lang('Postal Code')</span> <span
                                class="info">{{ $order->customer_zip }}</span>
                        </li>
                    </ul>
                </div>
            </div>



            @if ($order->dp == 0)
                <!-- Shipping Address Card  -->
                <div class="col">
                    <div class="order-info-card shipping-address-card">
                        <h5 class="title">@lang('Shipping Address')</h5>
                        <ul class="info-list">

                            @if ($order->shipping == 'pickup')
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Pickup Location')</span> <span
                                        class="info">{{ $order->pickup_location }}</span>
                                </li>
                            @else
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Name')</span> <span
                                        class="info">{{ $order->shipping_name == null ? $order->customer_name : $order->shipping_name }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Email')</span> <span
                                        class="info">{{ $order->shipping_email == null ? $order->customer_email : $order->shipping_email }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Phone')</span> <span
                                        class="info">{{ $order->shipping_phone == null ? $order->customer_phone : $order->shipping_phone }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Address')</span> <span
                                        class="info">{{ $order->shipping_address == null ? $order->customer_address : $order->shipping_address }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Country')</span> <span
                                        class="info">{{ $order->shipping_country == null ? $order->customer_country : $order->shipping_country }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('City')</span> <span
                                        class="info">{{ $order->shipping_city == null ? $order->customer_city : $order->shipping_city }}</span>
                                </li>
                                <li class="info-list-item">
                                    <span class="info-type">@lang('Postal Code')</span> <span
                                        class="info">{{ $order->shipping_zip == null ? $order->customer_zip : $order->shipping_zip }}</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            @endif
        </div>
        <!-- Order info cards end  -->

        <!-- Table area start  -->
        <div class="vendor-table-wrapper order-details-table-wrapper">
            <h4 class="table-title">@lang('Products Ordered')</h4>
            <div class="user-table table-responsive  position-relative">
                <table  class="gs-data-table w-100">
                    <thead>
                        <tr>
                            <th><span class="header-title">@lang('Product ID#')</span></th>
                            <th><span class="header-title">@lang('Shop Name')</span></th>
                            <th><span class="header-title">@lang('Status')</span></th>
                            <th><span class="header-title">@lang('Product Title')</span></th>
                            <th><span class="header-title">@lang('Details')</span></th>
                            <th><span class="header-title">@lang('Total Price')</span></th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach ($cart['items'] as $key => $product)
                            @if ($product['item']['user_id'] != 0)
                                @if ($product['item']['user_id'] == $user->id)
                                    <tr>
                                        <!-- Product ID# -->
                                        <td class="text-start"><span class="content ">{{ $product['item']['id'] }}</span>
                                        </td>
                                        <!-- Shop Name -->
                                        <td class="text-start">
                                                @if ($product['item']['user_id'] != 0)
                                                    @php
                                                        $user = App\Models\User::find($product['item']['user_id']);
                                                    @endphp
                                                    @if (isset($user))
                                                        <a class="title-hover-color content" target="_blank"
                                                            href="{{ route('admin-vendor-show', $user->id) }}">{{ $user->shop_name }}</a>
                                                    @else
                                                        {{ __('Vendor Removed') }}
                                                    @endif
                                                @endif
                                        </td>
                                        <!-- Status -->
                                        <td>
                                            @if ($product['item']['user_id'] != 0)
                                                @php
                                                    $user = App\Models\VendorOrder::where('order_id', '=', $order->id)
                                                        ->where('user_id', '=', $product['item']['user_id'])
                                                        ->first();
                                                @endphp

                                                @if ($order->dp == 1 && $order->payment_status == 'Completed')
                                                    <span
                                                        class="template-btn bg-success sm-btn">{{ __('Completed') }}</span>
                                                @else
                                                    @if ($user->status == 'pending')
                                                        <span
                                                            class="template-btn bg-warning sm-btn">{{ ucwords($user->status) }}</span>
                                                    @elseif($user->status == 'processing')
                                                        <span
                                                            class="template-btn bg-info sm-btn">{{ ucwords($user->status) }}</span>
                                                    @elseif($user->status == 'on delivery')
                                                        <span
                                                            class="template-btn bg-info sm-btn">{{ ucwords($user->status) }}</span>
                                                    @elseif($user->status == 'completed')
                                                        <span
                                                            class="template-btn bg-success sm-btn">{{ ucwords($user->status) }}</span>
                                                    @elseif($user->status == 'declined')
                                                        <span
                                                            class="template-btn bg-danger sm-btn">{{ ucwords($user->status) }}</span>
                                                    @endif
                                                @endif
                                            @endif
                                        </td>

                                        <!-- Product Title -->
                                        <td>

                                            @if ($product['item']['user_id'] != 0)
                                            @php
                                                $user = App\Models\User::find(
                                                    $product['item']['user_id'],
                                                );
                                            @endphp
                                            @if (isset($user))
                                            <a class="title-hover-color content product-title d-inline-block" target="_blank"
                                                href="{{ route('front.product', $product['item']['slug']) }}">
                                                {{ mb_strlen($product['item']['name'], 'UTF-8') > 30
                                                    ? mb_substr($product['item']['name'], 0, 30, 'UTF-8') . '...'
                                                    : $product['item']['name'] }}
                                            </a>

                                            @else
                                            <a class="title-hover-color content product-title d-inline-block" href="javascript:;">
                                                {{ mb_strlen($product['item']['name'], 'UTF-8') > 30
                                                    ? mb_substr($product['item']['name'], 0, 30, 'UTF-8') . '...'
                                                    : $product['item']['name'] }}
                                            </a>

                                            @endif
                                        @endif


                                        @if ($product['license'] != '')
                                            <a href="javascript:;" data-toggle="modal"
                                                data-target="#confirm-delete"
                                                class="btn btn-info product-btn" id="license"
                                                style="padding: 5px 12px;"><i class="fa fa-eye"></i>
                                                {{ __('View License') }}</a>
                                        @endif




                                        </td>
                                        <!-- Details -->
                                        <td class="text-start">
                                            <div class="rider">

                                                @if ($product['size'])
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">@lang('Size :')</span>
                                                    <span class="value">{{ str_replace('-','',$product['size'],) }}</span>
                                                </div>
                                                @endif

                                                @if ($product['color'])
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">{{ __('Color') }} :</span>
                                                    <span style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; border-radius: 50%; background: #{{ $product['color'] }};" class="value"></span>
                                                </div>
                                                @endif

                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">@lang('Price :')</span>
                                                    <span class="value">{{ \PriceHelper::showOrderCurrencyPrice($product['item_price'] * $order->currency_value, $order->currency_sign) }}</span>
                                                </div>



                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="key">@lang('Qty :')</span>
                                                    <span class="value">{{ $product['qty'] }}
                                                        {{ $product['item']['measure'] }}</span>
                                                </div>

                                                @if (!empty($product['keys']))
                                                            @foreach (array_combine(explode(',', $product['keys']), explode(',', $product['values'])) as $key => $value)
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
                                                {{ \PriceHelper::showOrderCurrencyPrice($product['price'] * $order->currency_value, $order->currency_sign) }}
                                                        <small>{{ $product['discount'] == 0 ? '' : '(' . $product['discount'] . '% ' . __('Off') . ')' }}</small>
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
