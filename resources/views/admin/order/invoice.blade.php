@extends('layouts.admin')

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
                            <div class="table-responsive">
                                <table id="example2" class="table table-hover dt-responsive" cellspacing="0"
                                    width="100%" >
                                    <thead>
                                        <tr>
                                            <th>{{ __('Product') }}</th>
                                            <th>{{ __('SKU') }}</th>
                                            <th>{{ __('Brand') }}</th>
                                            <th>{{ __('Manufacturer') }}</th>
                                            <th>{{ __('Shop Name') }}</th>
                                            <th>{{ __('Size') }}</th>
                                            <th>{{ __('Color') }}</th>
                                            <th>{{ __('Price') }}</th>
                                            <th>{{ __('Qty') }}</th>
                                            <th>{{ __('Discount') }}</th>
                                            <th>{{ __('Total') }}</th>
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
                                            <td>
                                                @if($product['item']['user_id'] != 0)
                                                    @if(isset($vendorUser))
                                                    <x-product-name :item="$product" :vendor-id="$product['item']['user_id']" :merchant-product-id="$product['item']['id']" target="_blank" />
                                                    @else
                                                    <x-product-name :item="$product" target="_self" />
                                                    @endif
                                                @else
                                                    <x-product-name :item="$product" target="_self" />
                                                @endif
                                            </td>

                                            {{-- SKU --}}
                                            <td>{{ $product['item']['sku'] ?? '-' }}</td>

                                            {{-- Brand --}}
                                            <td>{{ $orderProduct && $orderProduct->brand ? Str::ucfirst($orderProduct->brand->name) : '-' }}</td>

                                            {{-- Manufacturer --}}
                                            <td>
                                                @if($orderMerchant && $orderMerchant->qualityBrand)
                                                    {{ app()->getLocale() == 'ar' && $orderMerchant->qualityBrand->name_ar ? $orderMerchant->qualityBrand->name_ar : $orderMerchant->qualityBrand->name_en }}
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            {{-- Shop Name --}}
                                            <td>
                                                @if($product['item']['user_id'] != 0)
                                                    @if(isset($vendorUser))
                                                        {{ $vendorUser->shop_name }}
                                                    @else
                                                        {{ __('Vendor Removed') }}
                                                    @endif
                                                @else
                                                    {{ App\Models\Admin::find(1)->shop_name }}
                                                @endif
                                            </td>

                                            {{-- Size --}}
                                            <td>{{ $product['size'] ? str_replace('-', ' ', $product['size']) : '-' }}</td>

                                            {{-- Color --}}
                                            <td>
                                                @if($product['color'])
                                                    <span style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; border-radius: 50%; background: #{{$product['color']}};"></span>
                                                @else
                                                    -
                                                @endif
                                            </td>

                                            {{-- Price --}}
                                            <td>{{ \PriceHelper::showCurrencyPrice(($product['item_price'] ) * $order->currency_value) }}</td>

                                            {{-- Qty --}}
                                            <td>{{$product['qty']}} {{ $product['item']['measure'] }}</td>

                                            {{-- Discount --}}
                                            <td>{{ $product['discount'] == 0 ? '-' : $product['discount'].'%' }}</td>

                                            {{-- Total --}}
                                            <td>{{ \PriceHelper::showCurrencyPrice($product['price'] * $order->currency_value) }}</td>

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