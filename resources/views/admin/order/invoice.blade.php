@extends('layouts.admin')

@section('styles')
<link rel="stylesheet" href="{{ asset('assets/admin/css/order-table-enhancements.css') }}">
@endsection

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Order Invoice') }} <a class="add-btn" href="javascript:history.back();"><i
                            class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Orders') }}</a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Invoice') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div class="order-table-wrap">
        <div class="invoice-wrap">
            <div class="invoice__title">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="invoice__logo text-left">
                           <img src="{{ asset('assets/images/'.$gs->invoice_logo) }}" alt="logo">
                        </div>
                    </div>
                    <div class="col-lg-6 text-right">
                        <a class="btn  add-newProduct-btn print" href="{{route('admin-order-print',$order->id)}}"
                        target="_blank"><i class="fa fa-print"></i> {{ __('Print Invoice') }}</a>
                    </div>
                </div>
            </div>
            <br>
            <div class="row invoice__metaInfo mb-4">
                <div class="col-lg-6">
                    <div class="invoice__orderDetails">
                        
                        <p><strong>{{ __('Order Details') }} </strong></p>
                        <span><strong>{{ __('Invoice Number') }} :</strong> {{ sprintf("%'.08d", $order->id) }}</span><br>
                        <span><strong>{{ __('Order Date') }} :</strong> {{ date('d-M-Y',strtotime($order->created_at)) }}</span><br>
                        <span><strong>{{  __('Order ID')}} :</strong> {{ $order->order_number }}</span><br>
                        @if($order->dp == 0)
                        <span> <strong>{{ __('Shipping Method') }} :</strong>
                            @if($order->shipping == "pickup")
                            {{ __('Pick Up') }}
                            @else
                            {{ __('Ship To Address') }}
                            @endif
                        </span><br>
                        @endif
                        <span> <strong>{{ __('Payment Method') }} :</strong> {{$order->method}}</span>
                    </div>
                </div>
            </div>
            <div class="row invoice__metaInfo">
           @if($order->dp == 0)
                <div class="col-lg-6">
                        <div class="invoice__shipping">
                            <p><strong>{{ __('Shipping Address') }}</strong></p>
                           <span><strong>{{ __('Customer Name') }}</strong>: {{ $order->shipping_name == null ? $order->customer_name : $order->shipping_name}}</span><br>
                           <span><strong>{{ __('Address') }}</strong>: {{ $order->shipping_address == null ? $order->customer_address : $order->shipping_address }}</span><br>
                           <span><strong>{{ __('City') }}</strong>: {{ $order->shipping_city == null ? $order->customer_city : $order->shipping_city }}</span><br>
                           <span><strong>{{ __('Country') }}</strong>: {{ $order->shipping_country == null ? $order->customer_country : $order->shipping_country }}</span>

                        </div>
                </div>

            @endif

                <div class="col-lg-6">
                        <div class="buyer">
                            <p><strong>{{ __('Billing Details') }}</strong></p>
                            <span><strong>{{ __('Customer Name') }}</strong>: {{ $order->customer_name}}</span><br>
                            <span><strong>{{ __('Address') }}</strong>: {{ $order->customer_address }}</span><br>
                            <span><strong>{{ __('City') }}</strong>: {{ $order->customer_city }}</span><br>
                            <span><strong>{{ __('Country') }}</strong>: {{ $order->customer_country }}</span>
                        </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="invoice_table">
                        <div class="mr-table">
                            <div class="table-responsive order-table-responsive">
                                <table id="example2" class="table table-hover order-table-enhanced" cellspacing="0"
                                    width="100%" >
                                    <thead>
                                        <tr>
                                            <th class="col-title">{{ __('Product') }}</th>
                                            <th class="col-sku">{{ __('SKU') }}</th>
                                            <th class="col-brand">{{ __('Brand') }}</th>
                                            <th class="col-manufacturer">{{ __('Manufacturer') }}</th>
                                            <th class="col-shop">{{ __('Shop Name') }}</th>
                                            <th class="col-size">{{ __('Size') }}</th>
                                            <th class="col-color">{{ __('Color') }}</th>
                                            <th class="col-price">{{ __('Price') }}</th>
                                            <th class="col-qty">{{ __('Qty') }}</th>
                                            <th class="col-discount">{{ __('Discount') }}</th>
                                            <th class="col-total">{{ __('Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                        $subtotal = 0;
                                        $tax = 0;
                                        @endphp
                                        @foreach($cart['items'] as $product)
                                        @php
                                            $orderProduct = \App\Models\Product::find($product['item']['id']);
                                            $orderVendorId = $product['item']['user_id'] ?? 0;
                                            $orderMerchant = $orderProduct && $orderVendorId ? $orderProduct->merchantProducts()->where('user_id', $orderVendorId)->where('status', 1)->first() : null;
                                            $vendorUser = App\Models\User::find($product['item']['user_id']);
                                        @endphp
                                        <tr>
                                            {{-- Product --}}
                                            <td class="col-title">
                                                @php
                                                    $productName = $product['item']['name'] ?? '';
                                                @endphp
                                                <div class="tooltip-wrapper">
                                                    <span class="text-truncate-custom">
                                                        @if($product['item']['user_id'] != 0)
                                                            @if(isset($vendorUser))
                                                            <x-product-name :item="$product" :vendor-id="$product['item']['user_id']" :merchant-product-id="$product['item']['id']" target="_blank" />
                                                            @else
                                                            <x-product-name :item="$product" target="_self" />
                                                            @endif
                                                        @else
                                                            <x-product-name :item="$product" target="_self" />
                                                        @endif
                                                    </span>
                                                    @if(mb_strlen($productName) > 50)
                                                        <span class="tooltip-text">{{ $productName }}</span>
                                                    @endif
                                                </div>
                                            </td>

                                            {{-- SKU --}}
                                            <td class="col-sku">
                                                @if($product['item']['sku'])
                                                    <span class="badge-custom badge-sku">{{ $product['item']['sku'] }}</span>
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            {{-- Brand --}}
                                            <td class="col-brand">
                                                @php
                                                    $brandName = $orderProduct && $orderProduct->brand ? Str::ucfirst($orderProduct->brand->name) : '-';
                                                @endphp
                                                <div class="tooltip-wrapper">
                                                    <span class="text-truncate-custom">{{ $brandName }}</span>
                                                    @if(mb_strlen($brandName) > 15)
                                                        <span class="tooltip-text">{{ $brandName }}</span>
                                                    @endif
                                                </div>
                                            </td>

                                            {{-- Manufacturer --}}
                                            <td class="col-manufacturer">
                                                @if($orderMerchant && $orderMerchant->qualityBrand)
                                                    @php
                                                        $manufacturerName = app()->getLocale() == 'ar' && $orderMerchant->qualityBrand->name_ar ? $orderMerchant->qualityBrand->name_ar : $orderMerchant->qualityBrand->name_en;
                                                    @endphp
                                                    <div class="tooltip-wrapper">
                                                        <span class="text-truncate-custom">{{ $manufacturerName }}</span>
                                                        @if(mb_strlen($manufacturerName) > 15)
                                                            <span class="tooltip-text">{{ $manufacturerName }}</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            {{-- Shop Name --}}
                                            <td class="col-shop">
                                                @if($product['item']['user_id'] != 0)
                                                    @if(isset($vendorUser))
                                                        <div class="tooltip-wrapper">
                                                            <span class="text-truncate-custom">{{ $vendorUser->shop_name }}</span>
                                                            @if(mb_strlen($vendorUser->shop_name) > 20)
                                                                <span class="tooltip-text">{{ $vendorUser->shop_name }}</span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        {{ __('Vendor Removed') }}
                                                    @endif
                                                @else
                                                    {{ App\Models\Admin::find(1)->shop_name }}
                                                @endif
                                            </td>

                                            {{-- Size --}}
                                            <td class="col-size">{{ $product['size'] ? str_replace('-', ' ', $product['size']) : '-' }}</td>

                                            {{-- Color --}}
                                            <td class="col-color">
                                                @if($product['color'])
                                                    <div class="tooltip-wrapper">
                                                        <span class="color-circle" style="background: #{{$product['color']}};"></span>
                                                        <span class="tooltip-text">#{{ strtoupper($product['color']) }}</span>
                                                    </div>
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            {{-- Price --}}
                                            <td class="col-price">{{ \PriceHelper::showCurrencyPrice(($product['item_price'] ) * $order->currency_value) }}</td>

                                            {{-- Qty --}}
                                            <td class="col-qty">{{$product['qty']}} {{ $product['item']['measure'] }}</td>

                                            {{-- Discount --}}
                                            <td class="col-discount">{{ $product['discount'] == 0 ? '-' : $product['discount'].'%' }}</td>

                                            {{-- Total --}}
                                            <td class="col-total">{{ \PriceHelper::showCurrencyPrice($product['price'] * $order->currency_value) }}</td>

                                            @php
                                            $subtotal += round(($product['price'] / $order->currency_value) * $order->currency_value, 2);
                                            @endphp
                                        </tr>
                                        @endforeach
                                    </tbody>

                                    <tfoot>
                                        <tr>
                                            <td colspan="10" class="text-right"><strong>{{ __('Subtotal') }}</strong></td>
                                            <td>{{ \PriceHelper::showCurrencyPrice($subtotal  * $order->currency_value) }}</td>
                                        </tr>
                                        @if($order->shipping_cost != 0)
                                        @php
                                        $price = round(($order->shipping_cost / $order->currency_value),2);
                                        @endphp
                                            @if(DB::table('shippings')->where('price','=',$price)->count() > 0)
                                            <tr>
                                                <td colspan="10" class="text-right"><strong>{{ DB::table('shippings')->where('price','=',$price)->first()->title }}({{$order->currency_sign}})</strong></td>
                                                <td>{{ \PriceHelper::showOrderCurrencyPrice($order->shipping_cost,$order->currency_sign) }}</td>
                                            </tr>
                                            @endif
                                        @endif

                                        @if($order->packing_cost != 0)
                                        @php
                                        $pprice = round(($order->packing_cost / $order->currency_value),2);
                                        @endphp
                                        @if(DB::table('packages')->where('price','=',$pprice)->count() > 0)
                                        <tr>
                                            <td colspan="10" class="text-right"><strong>{{ DB::table('packages')->where('price','=',$pprice)->first()->title }}({{$order->currency_sign}})</strong></td>
                                            <td>{{ \PriceHelper::showOrderCurrencyPrice($order->packing_cost,$order->currency_sign) }}</td>
                                        </tr>
                                        @endif
                                        @endif

                                        @if($order->tax != 0)
                                        <tr>
                                            <td colspan="10" class="text-right"><strong>{{ __('Tax') }}</strong></td>
                                            <td> {{ \PriceHelper::showOrderCurrencyPrice((($order->tax) / $order->currency_value),$order->currency_sign) }}</td>
                                        </tr>
                                        @endif
                                        @if($order->coupon_discount != null)
                                        <tr>
                                            <td colspan="10" class="text-right"><strong>{{ __('Coupon Discount') }}({{$order->currency_sign}})</strong></td>
                                            <td>{{ \PriceHelper::showOrderCurrencyPrice($order->coupon_discount,$order->currency_sign) }}</td>
                                        </tr>
                                        @endif
                                        @if($order->wallet_price != 0)
                                        <tr>
                                            <td colspan="10" class="text-right"><strong>{{ __('Paid From Wallet') }}</strong></td>
                                            <td>{{ \PriceHelper::showOrderCurrencyPrice(($order->wallet_price * $order->currency_value),$order->currency_sign) }}
                                            </td>
                                        </tr>
                                            @if($order->method != "Wallet")
                                            <tr>
                                                <td colspan="10" class="text-right"><strong>{{$order->method}}</strong></td>
                                                <td>{{ \PriceHelper::showOrderCurrencyPrice(($order->pay_amount * $order->currency_value),$order->currency_sign) }}
                                                </td>
                                            </tr>
                                            @endif
                                        @endif

                                        <tr class="font-weight-bold">
                                            <td colspan="10" class="text-right"><strong>{{ __('Total') }}</strong></td>
                                            <td><strong>{{ \PriceHelper::showOrderCurrencyPrice((($order->pay_amount + $order->wallet_price) * $order->currency_value),$order->currency_sign) }}</strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Main Content Area End -->
</div>
</div>
</div>

@endsection