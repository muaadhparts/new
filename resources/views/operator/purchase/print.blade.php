<!DOCTYPE html>
<html>
<head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="keywords" content="{{ $seo->meta_keys ?? '' }}">
        <meta name="author" content="Muaadh">

        <title>{{$gs->site_name}}</title>
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
            <div class="invoice__name">
                <div class="row">
                    <div class="col-sm-6">
                        {{-- Seller display info pre-computed in DataBuilder (DATA_FLOW_POLICY) --}}
                        <div class="invoice__logo text-left">
                           @if($showPlatform)
                               <img src="{{ asset('assets/images/'.$gs->invoice_logo) }}" alt="{{ $gs->site_name }}" style="width: 150px; height: auto; object-fit: contain;">
                               <div style="margin-top: 5px;"><strong>{{ $gs->site_name }}</strong></div>
                           @else
                               {{-- Merchant is the seller --}}
                               @if($firstSeller['logo_url'])
                                   <img src="{{ $firstSeller['logo_url'] }}" alt="{{ $firstSeller['name'] }}" style="width: 150px; height: auto; object-fit: contain;">
                               @endif
                               <div style="margin-top: 5px;">
                                   <strong>{{ $firstSeller['name'] }}</strong>
                                   @if($firstSeller['address'])
                                       <br><small>{{ $firstSeller['address'] }}</small>
                                   @endif
                               </div>
                           @endif
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
                        <span> <strong>{{ __('Shipping Method') }} :</strong>
                            {{ __('Ship To Address') }}
                        </span><br>
                        <span> <strong>{{ __('Payment Method') }} :</strong> {{$purchase->method}}</span>
                        {{-- Shipment Info - Pure DTO --}}
                        @if($trackingData['hasTrackings'])
                        <br><br>
                        <p><strong>{{ __('Shipment Info') }}</strong></p>
                        @foreach($trackingData['trackings'] as $tracking)
                            <span><strong>{{ __('Merchant') }}:</strong> {{ $tracking['merchantName'] }}</span><br>
                            <span><strong>{{ __('Tracking') }}:</strong> {{ $tracking['trackingNumber'] ?? '-' }}</span><br>
                            <span><strong>{{ __('Company') }}:</strong> {{ $tracking['companyName'] ?? 'N/A' }}</span><br>
                            <span><strong>{{ __('Status') }}:</strong> {{ $tracking['statusDisplay'] }}</span><br>
                            @if(!$loop->last)<hr style="margin:5px 0;">@endif
                        @endforeach
                        @endif
                    </div>
                </div>
            </div>

            <div class="invoice__metaInfo" style="margin-top:0px;">
                <div class="col-lg-6">
                        <div class="invoice__orderDetails" style="margin-top:5px;">
                            <p><strong>{{ __('Shipping Details') }}</strong></p>
                           <span><strong>{{ __('Customer Name') }}</strong>: {{ $purchase->customer_name }}</span><br>
                           <span><strong>{{ __('Address') }}</strong>: {{ $purchase->customer_address }}</span><br>
                           <span><strong>{{ __('City') }}</strong>: {{ $purchase->customer_city }}</span><br>
                           <span><strong>{{ __('Country') }}</strong>: {{ $purchase->customer_country }}</span>
                        </div>
                </div>
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
                                            <th>{{ __('CatalogItem') }}</th>
                                            <th>{{ __('Merchant') }}</th>
                                            <th>{{ __('Details') }}</th>
                                            <th>{{ __('Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- Subtotal pre-computed in DataBuilder (DATA_FLOW_POLICY) --}}
                                        @foreach($cartItemsWithData as $catalogItem)
                                        <tr>
                                            <td width="40%">
                                                {{ getLocalizedCatalogItemName($catalogItem['item']) }}
                                                <br><small>PART_NUMBER: {{ $catalogItem['item']['part_number'] ?? 'N/A' }}</small>
                                                @if(isset($catalogItem['item']['brand_name']))
                                                <br><small>{{ __('Brand') }}: {{ $catalogItem['item']['brand_name'] }}</small>
                                                @endif
                                                @if($catalogItem['_qualityBrandName'])
                                                <br><small>{{ __('Quality') }}: {{ $catalogItem['_qualityBrandName'] }}</small>
                                                @endif
                                                <br><small>{{ __('Condition') }}: {{ $catalogItem['_condition'] }}</small>
                                            </td>
                                            <td width="15%">
                                                {{ $catalogItem['_merchantName'] }}
                                                @if($catalogItem['_branch'])
                                                    <br><small>{{ $catalogItem['_branch']['name'] }}@if($catalogItem['_branch']['city']) ({{ $catalogItem['_branch']['city'] }})@endif</small>
                                                @endif
                                            </td>

                                            <td>
                                                <p>
                                                        <strong>{{ __('Price') }} :</strong> {{ \PriceHelper::showCurrencyPrice(($catalogItem['price'] ?? 0) * $purchase->currency_value) }}
                                                </p>
                                               <p>
                                                    <strong>{{ __('Qty') }} :</strong> {{$catalogItem['qty'] ?? 1}}
                                               </p>


                                                    @if(!empty($catalogItem['keys']))

                                                    @foreach( array_combine(explode(',', $catalogItem['keys']), explode(',', $catalogItem['values']))  as $key => $value)
                                                    <p>

                                                        <b>{{ ucwords(str_replace('_', ' ', $key))  }} : </b> {{ $value }} 

                                                    </p>
                                                    @endforeach

                                                    @endif
                                               
                                            </td>

                                            <td> {{ \PriceHelper::showCurrencyPrice(($catalogItem['price'] ?? 0) * $purchase->currency_value)  }} <small>{{ ($catalogItem['discount'] ?? 0) == 0 ? '' : '('.$catalogItem['discount'].'% '.__('Off').')' }}</small>
                                            </td>
                                        </tr>

                                        @endforeach

                                        <tr class="semi-border">
                                            <td colspan="1"></td>
                                            <td><strong>{{ __('Subtotal') }}</strong></td>
                                            <td>{{ \PriceHelper::showOrderCurrencyPrice((($subtotal) * $purchase->currency_value),$purchase->currency_sign) }}</td>

                                        </tr>
                                        @if($purchase->shipping_cost != 0 && $shippingMethodName)
                                            <tr class="no-border">
                                                <td colspan="1"></td>
                                                <td><strong>{{ $shippingMethodName }}({{$purchase->currency_sign}})</strong></td>
                                                <td>{{ \PriceHelper::showOrderCurrencyPrice($purchase->shipping_cost,$purchase->currency_sign) }}</td>
                                            </tr>
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
    // Close window after print dialog is closed (not before)
    window.onafterprint = function() {
        window.close();
    };
</script>

</body>
</html>
