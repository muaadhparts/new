<!DOCTYPE html>
<html>
<head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="keywords" content="{{$seo->meta_keys}}">
        <meta name="author" content="Muaadh">

        <title>{{$gs->title}}</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="{{asset('assets/print/bootstrap/dist/css/bootstrap.min.css')}}">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="{{asset('assets/print/font-awesome/css/font-awesome.min.css')}}">
  <!-- Ionicons -->
  <link rel="stylesheet" href="{{asset('assets/print/Ionicons/css/ionicons.min.css')}}">
  <!-- Theme style -->
  <link rel="stylesheet" href="{{asset('assets/print/css/style.css')}}">
  <link href="{{asset('assets/print/css/print.css')}}" rel="stylesheet">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
        <link rel="icon" type="image/png" href="{{asset('assets/images/'.$gs->favicon)}}"> 
  <style type="text/css">
@page { size: auto;  margin: 0mm; }
@page {
  size: A4;
  margin: 0;
}
@media print {
  html, body {
    width: 210mm;
    height: 287mm;
  }

html {

}
::-webkit-scrollbar {
    width: 0px;  /* remove scrollbar space */
    background: transparent;  /* optional: just make scrollbar invisible */
}
  </style>
</head>
<body onload="window.print();">
    <div class="invoice-wrap">
            <div class="invoice__title">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="invoice__logo text-left">
                           <img src="{{ asset('assets/images/'.$gs->invoice_logo) }}" alt="woo commerce logo">
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <div class="invoice__metaInfo">
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
                            $adminPrintShipments = App\Models\ShipmentStatusLog::where('purchase_id', $purchase->id)
                                ->orderBy('status_date', 'desc')
                                ->get()
                                ->groupBy('merchant_id');
                        @endphp
                        @if($adminPrintShipments->count() > 0)
                        <br><br>
                        <p><strong>{{ __('Shipment Info') }}</strong></p>
                        @foreach($adminPrintShipments as $vendorId => $logs)
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

            <div class="invoice__metaInfo" style="margin-top:0px;">
                @if($purchase->dp == 0)
                <div class="col-lg-6">
                        <div class="invoice__orderDetails" style="margin-top:5px;">
                            <p><strong>{{ __('Shipping Details') }}</strong></p>
                           <span><strong>{{ __('Customer Name') }}</strong>: {{ $purchase->customer_name }}</span><br>
                           <span><strong>{{ __('Address') }}</strong>: {{ $purchase->customer_address }}</span><br>
                           <span><strong>{{ __('City') }}</strong>: {{ $purchase->customer_city }}</span><br>
                           <span><strong>{{ __('Country') }}</strong>: {{ $purchase->customer_country }}</span>
                        </div>
                </div>
                @endif
                <div class="col-lg-6" style="width:50%;">
                        <div class="invoice__orderDetails" style="margin-top:5px;">
                            <p><strong>{{ __('Billing Details') }}</strong></p>
                            <span><strong>{{ __('Customer Name') }}</strong>: {{ $purchase->customer_name}}</span><br>
                            <span><strong>{{ __('Address') }}</strong>: {{ $purchase->customer_address }}</span><br>
                            <span><strong>{{ __('City') }}</strong>: {{ $purchase->customer_city }}</span><br>
                            <span><strong>{{ __('Country') }}</strong>: {{ $purchase->customer_country }}</span>
                        </div>
                </div>
            </div>

                <div class="col-lg-12">
                    <div class="invoice_table">
                        <div class="mr-table">
                            <div class="table-responsive">
                                <table id="example2" class="table table-hover dt-responsive" cellspacing="0"
                                    width="100%">
                                    <thead style="border-top:1px solid rgba(0, 0, 0, 0.1) !important;">
                                        <tr>
                                            <th>{{ __('Product') }}</th>
                                            <th>{{ __('Vendor') }}</th>
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
                                                {{ getLocalizedProductName($product['item']) }}
                                                <br><small>SKU: {{ $product['item']['sku'] ?? 'N/A' }}</small>
                                                @if(isset($product['item']['brand_name']))
                                                <br><small>{{ __('Brand') }}: {{ $product['item']['brand_name'] }}</small>
                                                @endif
                                                @php
                                                    $printQualityBrand = null;
                                                    if (isset($product['brand_quality_id']) && $product['brand_quality_id']) {
                                                        $printQualityBrand = \App\Models\QualityBrand::find($product['brand_quality_id']);
                                                    }
                                                    $printCondition = isset($product['item']['product_condition']) && $product['item']['product_condition'] == 1 ? __('Used') : __('New');
                                                @endphp
                                                @if($printQualityBrand)
                                                <br><small>{{ __('Quality') }}: {{ getLocalizedQualityName($printQualityBrand) }}</small>
                                                @endif
                                                <br><small>{{ __('Condition') }}: {{ $printCondition }}</small>
                                            </td>
                                            <td width="15%">
                                                @if(isset($product['vendor_name']))
                                                    {{ $product['vendor_name'] }}
                                                @elseif(isset($product['item']['user_id']) && $product['item']['user_id'] != 0)
                                                    @php $user = App\Models\User::find($product['item']['user_id']); @endphp
                                                    {{ $user->shop_name ?? $user->name ?? '' }}
                                                @endif
                                            </td>

                                            <td>
                                                @if($product['size'])
                                               <p>
                                                    <strong>{{ __('Size') }} :</strong> {{str_replace('-',' ',$product['size'])}}
                                               </p>
                                               @endif
                                               @if($product['color'])
                                                <p>
                                                        <strong>{{ __('color') }} :</strong> <span style="width: 20px; height: 5px; display: block; border-radius: 50%; border: 10px solid {{$product['color'] == "" ? "white" : '#'.$product['color']}};"></span>
                                                </p>
                                                @endif
                                                <p>
                                                        <strong>{{ __('Price') }} :</strong> {{ \PriceHelper::showCurrencyPrice(($product['item_price'] ) * $purchase->currency_value) }}
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

                                            <td> {{ \PriceHelper::showCurrencyPrice($product['price'] * $purchase->currency_value)  }} <small>{{ $product['discount'] == 0 ? '' : '('.$product['discount'].'% '.__('Off').')' }}</small>
                                            </td>
                                            @php
                                            $subtotal += round(($product['price']/ $purchase->currency_value) * $purchase->currency_value, 2);
                                            @endphp

                                        </tr>

                                        @endforeach

                                        <tr class="semi-border">
                                            <td colspan="1"></td>
                                            <td><strong>{{ __('Subtotal') }}</strong></td>
                                            <td>{{ \PriceHelper::showOrderCurrencyPrice((($subtotal) * $purchase->currency_value),$purchase->currency_sign) }}</td>

                                        </tr>
                                        @if($purchase->shipping_cost != 0)
                                        @php
                                        $price = round(($purchase->shipping_cost / $purchase->currency_value),2);
                                        @endphp
                                            @if(DB::table('shippings')->where('price','=',$price)->count() > 0)
                                            <tr class="no-border">
                                                <td colspan="1"></td>
                                                <td><strong>{{ DB::table('shippings')->where('price','=',$price)->first()->title }}({{$purchase->currency_sign}})</strong></td>
                                                <td>{{ \PriceHelper::showOrderCurrencyPrice($purchase->shipping_cost,$purchase->currency_sign) }}</td>
                                            </tr>
                                            @endif
                                        @endif

                                        @if($purchase->packing_cost != 0)
                                        @php
                                        $pprice = round(($purchase->packing_cost / $purchase->currency_value),2);
                                        @endphp
                                        @if(DB::table('packages')->where('price','=',$pprice)->count() > 0)
                                        <tr class="no-border">
                                            <td colspan="1"></td>
                                            <td><strong>{{ DB::table('packages')->where('price','=',$pprice)->first()->title }}({{$purchase->currency_sign}})</strong></td>
                                            <td>{{ \PriceHelper::showOrderCurrencyPrice($purchase->packing_cost,$purchase->currency_sign) }}</td>
                                        </tr>
                                        @endif
                                        @endif

                                        @if($purchase->tax != 0)
                                        <tr class="no-border">
                                            <td colspan="1"></td>
                                            <td>{{ __('Tax') }} </td>
                                            <td>{{ \PriceHelper::showOrderCurrencyPrice((($purchase->tax) / $purchase->currency_value),$purchase->currency_sign) }}</td>
                                        </tr>
                                        @endif
                                        @if($purchase->discount_amount != null)
                                        <tr class="no-border">
                                            <td colspan="1"></td>
                                            <td><strong>{{ __('Discount Amount') }}({{$purchase->currency_sign}})</strong></td>
                                            <td>{{ \PriceHelper::showOrderCurrencyPrice($purchase->discount_amount,$purchase->currency_sign) }}</td>
                                        </tr>
                                        @endif

                                        @if($purchase->wallet_price != 0)
                                        <tr class="no-border">
                                            <td colspan="1"></td>
                                            <td><strong>{{ __('Paid From Wallet') }}</strong></td>
                                            <td>{{ \PriceHelper::showOrderCurrencyPrice(($purchase->wallet_price * $purchase->currency_value),$purchase->currency_sign) }}</td>
                                        </tr>
                                            @if($purchase->method != "Wallet")
                                            <tr class="no-border">
                                                <td colspan="1"></td>
                                                <td><strong>{{$purchase->method}}</strong></td>
                                                <td>{{ \PriceHelper::showOrderCurrencyPrice(($purchase->pay_amount * $purchase->currency_value),$purchase->currency_sign) }}
                                                </td>
                                            </tr>
                                            @endif

                                        @endif


                                        <tr class="final-border">
                                            <td colspan="1"></td>
                                            <td><strong>{{ __('Total') }}</strong></td>
                                            <td>{{ \PriceHelper::showOrderCurrencyPrice((($purchase->pay_amount + $purchase->wallet_price) * $purchase->currency_value),$purchase->currency_sign) }}
                                            </td>
                                        </tr>

                                    </tbody>

                                </table>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
<!-- ./wrapper -->

<script type="text/javascript">
    (function($) {
		"use strict";

setTimeout(function () {
        window.close();
      }, 500);

    })(jQuery);

</script>

</body>
</html>
