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
                                href="{{ route('courier-purchase-delivery-accept', $data->id) }}">@lang('Accept')</a>
                            <a class="template-btn red-btn"
                                href="{{ route('courier-purchase-delivery-reject', $data->id) }}">@lang('Reject')</a>
                        @elseif($data->status == 'accepted')
                            <a class="template-btn green-btn"
                                href="{{ route('courier-purchase-delivery-complete', $data->id) }}">@lang('Make Delivered')</a>
                        @elseif($data->status == 'rejected')
                            <button class="template-btn red-btn">@lang('Rejected')</button>
                        @else
                            <button class="template-btn green-btn"> @lang('Delivered')</button>
                        @endif



                    </div>

                    <div class="delivery-details">
                        <div class="row g-4 my-3">
                            <div class="col-md-6">
                                <h5>@lang('Delivery Address')</h5>
                                <div class="delivery-address-info">
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Name:') </span>
                                        <span class="info-content">{{ $purchase->customer_name }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Email:') </span>
                                        <span class="info-content">{{ $purchase->customer_email }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Phone:') </span>
                                        <span class="info-content">{{ $purchase->customer_phone }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('City:') </span>
                                        <span class="info-content">{{ $purchase->customer_address }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Address:') </span>
                                        <span
                                            class="info-content">{{ $purchase->customer_city }}-{{ $purchase->customer_zip }}</span>
                                    </div>

                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>@lang('Merchant Information')</h5>
                                <div class="delivery-address-info">
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Shop Name:') </span>
                                        <span class="info-content">{{ $data->merchant->shop_name }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Email:') </span>
                                        <span class="info-content">{{ $data->merchant->email }}</span>
                                    </div>
                                    <div class="account-info-item">
                                        <span class="info-title">@lang('Phone:') </span>
                                        <span class="info-content">{{ $data->merchant->phone }}</span>
                                    </div>
                                    @if ($data->merchant->city)
                                        <div class="account-info-item">
                                            <span class="info-title">@lang('City:') </span>
                                            <span class="info-content">{{ $data->merchant->city }}</span>
                                        </div>
                                    @endif
                                    @if ($data->merchant->address)
                                        <div class="account-info-item">
                                            <span class="info-title">@lang('Address:') </span>
                                            <span class="info-content">{{ $data->merchant->address }}</span>
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

                    <div class="ordered-catalogItems">
                        <h5>@lang('Purchased Items:') </h5>
                        <div class="user-table-wrapper all-orders-table-wrapper wow-replaced" data-wow-delay=".1s">

                            <div class="user-table table-responsive position-relative">
                                <table class="gs-data-table custom-table-rider w-100">
                                    <tr class="ordered-tbg">
                                        <th><span class="title">@lang('ID#')</span></th>
                                        <th><span class="title">@lang('CatalogItem Name')</span></th>
                                        <th><span class="title">@lang('Details')</span></th>

                                    </tr>
                                    @php
                                        $extra_price = 0;
                                    @endphp
                                    @foreach (json_decode($purchase->cart, true)['items'] as $catalogItem)
                                        @if ($catalogItem['user_id'] == $data->merchant_id)
                                            <tr>
                                                <td data-label="{{ __('ID#') }}">
                                                    <div>
                                                    <span class="title">
                                                        {{ $catalogItem['item']['id'] }}
                                                    </span>
                                                    </div>
                                                </td>
                                                <td data-label="{{ __('Name') }}">
                                                  <span class="title">
                                                    {{ getLocalizedCatalogItemName($catalogItem['item'], 50) }}
                                                  </span>

                                                </td>
                                                <td data-label="{{ __('Details') }}">
                                                    <div>
                                                        <b>{{ __('Quantity') }}</b>: {{ $catalogItem['qty'] }} <br>
                                                        @if (!empty($catalogItem['size']))
                                                            <b>{{ __('Size') }}</b>:
                                                            {{ $catalogItem['item']['measure'] }}{{ str_replace('-', ' ', $catalogItem['size']) }}
                                                            <br>
                                                        @endif
                                                        @if (!empty($catalogItem['color']))
                                                            <div class="d-flex mt-2">
                                                                <b>{{ __('Color') }}</b>: <span id="color-bar"
                                                                    style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; border-radius: 50%; background: #{{ $catalogItem['color'] }};"></span>
                                                            </div>
                                                        @endif
                                                        @if (!empty($catalogItem['keys']))
                                                            @foreach (array_combine(explode(',', $catalogItem['keys']), explode(',', $catalogItem['values'])) as $key => $value)
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

                               $purchase_shipping = json_decode($purchase->merchant_shipping_id, true) ?? [];
                                $purchase_package = json_decode($purchase->merchant_packing_id, true) ?? [];

                                // Retrieve merchant-specific shipping and packing IDs, defaulting to null if not found
                                $merchant_shipping_id = $purchase_shipping[$data->merchant_id] ?? null;
                                $merchant_package_id = $purchase_package[$data->merchant_id] ?? null;
                                
                                // Retrieve the Shipping and Package models, or null if not found
                                $shipping = $merchant_shipping_id ? App\Models\Shipping::find($merchant_shipping_id) : null;
                                $package = $merchant_package_id ? App\Models\Package::find($merchant_package_id) : null;
                                
                                // Calculate shipping and packing costs, defaulting to 0 if models are not found
                                $shipping_cost = $shipping ? $shipping->price : 0;
                                $packing_cost = $package ? $package->price : 0;
                                
                                // Total extra cost
                                $extra_price = $shipping_cost + $packing_cost;
                            @endphp

                            <strong>

                                @lang('Collection Amount from Customer') :
                                @if ($purchase->method == 'Cash On Delivery')
                                    {{ \PriceHelper::showAdminCurrencyPrice(
                                        ($purchase->merchantPurchases->where('user_id', $data->merchant_id)->sum('price') + $extra_price) *
                                            $data->purchase->currency_value,
                                        $purchase->currency_sign,
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
