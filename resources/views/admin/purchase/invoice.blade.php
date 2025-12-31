@extends('layouts.admin')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Purchase Invoice') }} <a class="add-btn" href="javascript:history.back();"><i
                            class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Purchases') }}</a>
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
                           <img src="{{ asset('assets/images/'.$gs->invoice_logo) }}" alt="woo commerce logo">
                        </div>
                    </div>
                    <div class="col-lg-6 text-right">
                        <a class="btn  add-newProduct-btn print" href="{{route('admin-purchase-print',$purchase->id)}}"
                        target="_blank"><i class="fa fa-print"></i> {{ __('Print Invoice') }}</a>
                    </div>
                </div>
            </div>
            <br>
            <div class="row invoice__metaInfo mb-4">
                <div class="col-lg-6">
                    <div class="invoice__orderDetails">

                        <p><strong>{{ __('Purchase Details') }} </strong></p>
                        <span><strong>{{ __('Invoice Number') }} :</strong> {{ sprintf("%'.08d", $purchase->id) }}</span><br>
                        <span><strong>{{ __('Purchase Date') }} :</strong> {{ date('d-M-Y',strtotime($purchase->created_at)) }}</span><br>
                        <span><strong>{{  __('Purchase ID')}} :</strong> {{ $purchase->purchase_number }}</span><br>
                        @if($purchase->dp == 0)
                        <span> <strong>{{ __('Shipping Method') }} :</strong>
                            @if($purchase->shipping == "pickup")
                            {{ __('Pick Up') }}
                            @else
                            {{ __('Ship To Address') }}
                            @endif
                        </span><br>
                        @endif
                        <span> <strong>{{ __('Payment Method') }} :</strong> {{$purchase->method}}</span>
                        @php
                            $adminInvoiceShipments = App\Models\ShipmentStatusLog::where('purchase_id', $purchase->id)
                                ->orderBy('status_date', 'desc')
                                ->get()
                                ->groupBy('vendor_id');
                        @endphp
                        @if($adminInvoiceShipments->count() > 0)
                        <br><br>
                        <p><strong>{{ __('Shipment Info') }}</strong></p>
                        @foreach($adminInvoiceShipments as $vendorId => $logs)
                            @php
                                $latestLog = $logs->first();
                                $vendor = App\Models\User::find($vendorId);
                            @endphp
                            <span><strong>{{ __('Vendor') }}:</strong> {{ $vendor->shop_name ?? $vendor->name ?? 'N/A' }}</span><br>
                            <span><strong>{{ __('Tracking') }}:</strong> {{ $latestLog->tracking_number }}</span><br>
                            <span><strong>{{ __('Company') }}:</strong> {{ $latestLog->company_name ?? 'N/A' }}</span><br>
                            <span><strong>{{ __('Status') }}:</strong> {{ ucfirst($latestLog->status) }}</span><br>
                            @if(!$loop->last)<hr style="margin:5px 0;">@endif
                        @endforeach
                        @endif
                    </div>
                </div>
            </div>
            <div class="row invoice__metaInfo">
           @if($purchase->dp == 0)
                <div class="col-lg-6">
                        <div class="invoice__shipping">
                            <p><strong>{{ __('Shipping Address') }}</strong></p>
                           <span><strong>{{ __('Customer Name') }}</strong>: {{ $purchase->customer_name }}</span><br>
                           <span><strong>{{ __('Address') }}</strong>: {{ $purchase->customer_address }}</span><br>
                           <span><strong>{{ __('City') }}</strong>: {{ $purchase->customer_city }}</span><br>
                           <span><strong>{{ __('Country') }}</strong>: {{ $purchase->customer_country }}</span>

                        </div>
                </div>

            @endif

                <div class="col-lg-6">
                        <div class="buyer">
                            <p><strong>{{ __('Billing Details') }}</strong></p>
                            <span><strong>{{ __('Customer Name') }}</strong>: {{ $purchase->customer_name}}</span><br>
                            <span><strong>{{ __('Address') }}</strong>: {{ $purchase->customer_address }}</span><br>
                            <span><strong>{{ __('City') }}</strong>: {{ $purchase->customer_city }}</span><br>
                            <span><strong>{{ __('Country') }}</strong>: {{ $purchase->customer_country }}</span>
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
                                            <th>{{ __('Vendor/Brand') }}</th>
                                            <th>{{ __('Details') }}</th>
                                            <th>{{ __('Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                        $subtotal = 0;
                                        $tax = 0;
                                        @endphp
                                        @foreach($cart['items'] as $product)
                                        <tr>
                                            <td width="40%">
                                                @php
                                                $user = isset($product['item']['user_id']) && $product['item']['user_id'] != 0
                                                    ? App\Models\User::find($product['item']['user_id'])
                                                    : null;
                                                $invoiceProductUrl = '#';
                                                if (isset($product['item']['slug']) && isset($product['user_id']) && isset($product['merchant_item_id'])) {
                                                    $invoiceProductUrl = route('front.catalog-item', [
                                                        'slug' => $product['item']['slug'],
                                                        'vendor_id' => $product['user_id'],
                                                        'merchant_item_id' => $product['merchant_item_id']
                                                    ]);
                                                } elseif (isset($product['item']['slug'])) {
                                                    $invoiceProductUrl = route('front.catalog-item.legacy', $product['item']['slug']);
                                                }
                                                @endphp
                                                @if(isset($user))
                                                <a target="_blank"
                                                    href="{{ $invoiceProductUrl }}">{{ getLocalizedProductName($product['item']) }}</a>
                                                @else
                                                <a href="javascript:;">{{ getLocalizedProductName($product['item']) }}</a>
                                                @endif
                                                <br><small class="text-muted">SKU: {{ $product['item']['sku'] ?? 'N/A' }}</small>
                                            </td>
                                            <td width="20%">
                                                @if(isset($product['vendor_name']))
                                                    <strong>{{ __('Vendor') }}:</strong> {{ $product['vendor_name'] }}<br>
                                                @elseif(isset($user))
                                                    <strong>{{ __('Vendor') }}:</strong> {{ $user->shop_name ?? $user->name }}<br>
                                                @endif
                                                @if(isset($product['item']['brand_name']))
                                                    <strong>{{ __('Brand') }}:</strong> {{ $product['item']['brand_name'] }}<br>
                                                @endif
                                                @php
                                                    // جودة البراند والشركة المصنعة
                                                    $invoiceQualityBrand = null;
                                                    if (isset($product['brand_quality_id']) && $product['brand_quality_id']) {
                                                        $invoiceQualityBrand = \App\Models\QualityBrand::find($product['brand_quality_id']);
                                                    }
                                                @endphp
                                                @if($invoiceQualityBrand)
                                                    <strong>{{ __('Quality Brand') }}:</strong> {{ getLocalizedQualityName($invoiceQualityBrand) }}<br>
                                                @elseif(isset($product['quality_name']))
                                                    <strong>{{ __('Quality') }}:</strong> {{ $product['quality_name'] }}<br>
                                                @endif
                                                @php
                                                    $invoiceCondition = isset($product['item']['product_condition']) && $product['item']['product_condition'] == 1 ? __('Used') : __('New');
                                                @endphp
                                                <strong>{{ __('Condition') }}:</strong> {{ $invoiceCondition }}
                                            </td>


                                            <td>
                                                @if($product['size'])
                                               <p>
                                                    <strong>{{ __('Size') }} :</strong> {{str_replace('-',' ',$product['size'])}}
                                               </p>
                                               @endif
                                               @if($product['color'])
                                                <p>
                                                        <strong>{{ __('color') }} :</strong> <span
                                                        style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; border-radius: 50%; background: #{{$product['color']}};"></span>
                                                </p>
                                                @endif
                                                <p>
                                                        <strong>{{ __('Price') }} :</strong>{{ \PriceHelper::showCurrencyPrice(($product['item_price'] ) * $purchase->currency_value) }}
                                                </p>
                                               <p>
                                                    <strong>{{ __('Qty') }} :</strong> {{$product['qty']}} {{ $product['item']['measure'] }}
                                               </p>

                                                    @if(!empty($product['keys']))

                                                    @foreach( array_combine(explode(',', $product['keys']), explode(',', $product['values']))  as $key => $value)
                                                    <p>
                                                        <b>{{ ucwords(str_replace('_', ' ', $key))  }} : </b> {{ $value }} 
                                                    </p>
                                                    @endforeach
                                                    @endif
                                            </td>


                                            <td>{{ \PriceHelper::showCurrencyPrice($product['price'] * $purchase->currency_value)  }} <small>{{ $product['discount'] == 0 ? '' : '('.$product['discount'].'% '.__('Off').')' }}</small>
                                            </td>
                                            @php
                                            $subtotal += round(($product['price'] / $purchase->currency_value) * $purchase->currency_value, 2);
                                            @endphp
                                        </tr>
                                        @endforeach
                                    </tbody>

                                    <tfoot>
                                        <tr>
                                            <td colspan="2">{{ __('Subtotal') }}</td>
                                            <td>{{ \PriceHelper::showCurrencyPrice($subtotal  * $purchase->currency_value) }}</td>
                                        </tr>
                                        @if($purchase->shipping_cost != 0)
                                        @php
                                        $price = round(($purchase->shipping_cost / $purchase->currency_value),2);
                                        @endphp
                                            @if(DB::table('shippings')->where('price','=',$price)->count() > 0)
                                            <tr>
                                                <td colspan="2">{{ DB::table('shippings')->where('price','=',$price)->first()->title }}({{$purchase->currency_sign}})</td>
                                                <td>{{ \PriceHelper::showOrderCurrencyPrice($purchase->shipping_cost,$purchase->currency_sign) }}</td>
                                            </tr>
                                            @endif
                                        @endif

                                        @if($purchase->packing_cost != 0)
                                        @php
                                        $pprice = round(($purchase->packing_cost / $purchase->currency_value),2);
                                        @endphp
                                        @if(DB::table('packages')->where('price','=',$pprice)->count() > 0)
                                        <tr>
                                            <td colspan="2">{{ DB::table('packages')->where('price','=',$pprice)->first()->title }}({{$purchase->currency_sign}})</td>
                                            <td>{{ \PriceHelper::showOrderCurrencyPrice($purchase->packing_cost,$purchase->currency_sign) }}</td>
                                        </tr>
                                        @endif
                                        @endif

                                        @if($purchase->tax != 0)
                                        <tr>
                                            <td colspan="2">{{ __('Tax') }}</td>
                                            <td> {{ \PriceHelper::showOrderCurrencyPrice((($purchase->tax) / $purchase->currency_value),$purchase->currency_sign) }}</td>
                                        </tr>
                                        @endif
                                        @if($purchase->discount_amount != null)
                                        <tr>
                                            <td colspan="2">{{ __('Discount Amount') }}({{$purchase->currency_sign}})</td>
                                            <td>{{ \PriceHelper::showOrderCurrencyPrice($purchase->discount_amount,$purchase->currency_sign) }}</td>
                                        </tr>
                                        @endif
                                        @if($purchase->wallet_price != 0)
                                        <tr>
                                            <td colspan="1"></td>
                                            <td>{{ __('Paid From Wallet') }}</td>
                                            <td>{{ \PriceHelper::showOrderCurrencyPrice(($purchase->wallet_price * $purchase->currency_value),$purchase->currency_sign) }}
                                            </td>
                                        </tr>
                                            @if($purchase->method != "Wallet")
                                            <tr>
                                                <td colspan="1"></td>
                                                <td>{{$purchase->method}}</td>
                                                <td>{{ \PriceHelper::showOrderCurrencyPrice(($purchase->pay_amount * $purchase->currency_value),$purchase->currency_sign) }}
                                                </td>
                                            </tr>
                                            @endif
                                        @endif

                                        <tr>
                                            <td colspan="1"></td>
                                            <td>{{ __('Total') }}</td>
                                            <td>{{ \PriceHelper::showOrderCurrencyPrice((($purchase->pay_amount + $purchase->wallet_price) * $purchase->currency_value),$purchase->currency_sign) }}
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