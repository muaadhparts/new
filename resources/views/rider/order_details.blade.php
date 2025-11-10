@extends('layouts.front')
@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.rider.sidebar')
                @php
                    $order = $data->order;
                @endphp
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <div class="ud-page-title-box gap-4">
                        <!-- mobile sidebar trigger btn -->
                        <a href="{{ url()->previous() }}" class="back-btn">
                            <i class="fa-solid fa-arrow-left-long"></i>
                        </a>

                        <h3 class="ud-page-title">@lang('Delivery Details')</h3>
                    </div>

                    <!-- Accept and reject button -->
                    <div class="accept-reject-btn my-2">


                        @if ($data->status == 'pending')
                            <a class="template-btn green-btn"
                                href="{{ route('rider-order-delivery-accept', $data->id) }}">@lang('Accept')</a>
                            <a class="template-btn red-btn"
                                href="{{ route('rider-order-delivery-reject', $data->id) }}">@lang('Reject')</a>
                        @elseif($data->status == 'accepted')
                            <a class="template-btn green-btn"
                                href="{{ route('rider-order-delivery-complete', $data->id) }}">@lang('Make Delivered')</a>
                        @elseif($data->status == 'rejected')
                            <button class="template-btn red-btn">@lang('Rejected')</button>
                        @else
                            <button class="template-btn green-btn"> @lang('Delivered')</button>
                        @endif



                    </div>

                    <div class="delivery-details">
                        <div class="row g-4 my-3">
                            <div class="col-md-6">
                                <h5>Delivery Address</h5>
                                <div class="delivery-address-info">
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Name:') </span>
                                        <span class="info-content">{{ $order->customer_name }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Email:') </span>
                                        <span class="info-content">{{ $order->customer_email }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Phone:') </span>
                                        <span class="info-content">{{ $order->customer_phone }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('City:') </span>
                                        <span class="info-content">{{ $order->customer_address }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Address:') </span>
                                        <span
                                            class="info-content">{{ $order->customer_city }}-{{ $order->customer_zip }}</span>
                                    </div>

                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>@lang('Vendor Information')</h5>
                                <div class="delivery-address-info">
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Shop Name:') </span>
                                        <span class="info-content">{{ $data->vendor->shop_name }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Email:') </span>
                                        <span class="info-content">{{ $data->vendor->email }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Phone:') </span>
                                        <span class="info-content">{{ $data->vendor->phone }}</span>
                                    </div>
                                    @if ($data->vendor->city)
                                        <div class="account-info-item">
                                            <span class="info-title">@lang('City:') </span>
                                            <span class="info-content">{{ $data->vendor->city }}</span>
                                        </div>
                                    @endif
                                    @if ($data->vendor->address)
                                        <div class="account-info-item">
                                            <span class="info-title">@lang('Address:') </span>
                                            <span class="info-content">{{ $data->vendor->address }}</span>
                                        </div>
                                    @endif

                                    <div class="account-info-item">
                                        <span class="info-title"><strong>@lang('Pickup Location:')</strong> </span>
                                        <span class="info-content">{{ $data->pickup->location }}</span>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ordered-products">
                        <h5>@lang('Ordered Products:') </h5>
                        <div class="user-table-wrapper all-orders-table-wrapper wow-replaced" data-wow-delay=".1s">

                            <div class="user-table table-responsive position-relative">
                                <table class="gs-data-table custom-table-rider w-100">
                                    <tr class="ordered-tbg">
                                        <th><span class="title">@lang('ID#')</span></th>
                                        <th><span class="title">@lang('Product Name')</span></th>
                                        <th><span class="title">@lang('Details')</span></th>

                                    </tr>
                                    @php
                                        $extra_price = 0;
                                    @endphp
                                    @foreach (json_decode($order->cart, true)['items'] as $product)
                                        @if ($product['user_id'] == $data->vendor_id)
                                            <tr>
                                                <td data-label="{{ __('ID#') }}">
                                                    <div>
                                                    <span class="title">
                                                        {{ $product['item']['id'] }}
                                                    </span>
                                                    </div>
                                                </td>
                                                <td data-label="{{ __('Name') }}">
                                                  <span class="title">
                                                    {{ mb_strlen($product['item']['name'], 'UTF-8') > 50
                                                    ? mb_substr($product['item']['name'], 0, 50, 'UTF-8') . '...'
                                                    : $product['item']['name'] }}
                                                  </span>

                                                </td>
                                                <td data-label="{{ __('Details') }}">
                                                    <div>
                                                        <b>{{ __('Quantity') }}</b>: {{ $product['qty'] }} <br>
                                                        @if (!empty($product['size']))
                                                            <b>{{ __('Size') }}</b>:
                                                            {{ $product['item']['measure'] }}{{ str_replace('-', ' ', $product['size']) }}
                                                            <br>
                                                        @endif
                                                        @if (!empty($product['color']))
                                                            <div class="d-flex mt-2">
                                                                <b>{{ __('Color') }}</b>: <span id="color-bar"
                                                                    style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; border-radius: 50%; background: #{{ $product['color'] }};"></span>
                                                            </div>
                                                        @endif
                                                        @if (!empty($product['keys']))
                                                            @foreach (array_combine(explode(',', $product['keys']), explode(',', $product['values'])) as $key => $value)
                                                                <b>{{ ucwords(str_replace('_', ' ', $key)) }} : </b>
                                                                {{ $value }} <br>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </td>

                                            </tr>
                                        @endif
                                    @endforeach

                                </table>
                            </div>




                        </div>

                        <div class="text-center mt-4">


                            @php
                               
                               $order_shipping = json_decode($order->vendor_shipping_id, true) ?? [];
                                $order_package = json_decode($order->vendor_packing_id, true) ?? [];
                                
                                // Retrieve vendor-specific shipping and packing IDs, defaulting to null if not found
                                $vendor_shipping_id = $order_shipping[$order->vendor_id] ?? null;
                                $vendor_package_id = $order_package[$order->vendor_id] ?? null;
                                
                                // Retrieve the Shipping and Package models, or null if not found
                                $shipping = $vendor_shipping_id ? App\Models\Shipping::find($vendor_shipping_id) : null;
                                $package = $vendor_package_id ? App\Models\Package::find($vendor_package_id) : null;
                                
                                // Calculate shipping and packing costs, defaulting to 0 if models are not found
                                $shipping_cost = $shipping ? $shipping->price : 0;
                                $packing_cost = $package ? $package->price : 0;
                                
                                // Total extra cost
                                $extra_price = $shipping_cost + $packing_cost;
                            @endphp

                            <strong>

                                @lang('Collection Amount from Customer') :
                                @if ($order->method == 'Cash On Delivery')
                                    {{ \PriceHelper::showAdminCurrencyPrice(
                                        ($order->vendororders->where('user_id', $data->vendor_id)->sum('price') + $extra_price) *
                                            $data->order->currency_value,
                                        $order->currency_sign,
                                    ) }}
                                @else
                                    {{ __('N/A') }}
                                @endif

                            </strong>
                        </div>


                    </div>


                    <!-- recent orders -->


                    <!-- account information -->

                </div>
            </div>
        </div>
    </div>
@endsection
