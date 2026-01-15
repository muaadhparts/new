<!DOCTYPE html>
<html>
<head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="keywords" content="{{$seo->meta_keys}}">
        <meta name="author" content="Muaadh">

        <name>{{$gs->site_name}}</name>
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

#color-bar {
  display: inline-block;
  width: 20px;
  height: 20px;
  margin-left: 5px;
  margin-top: 5px;
}

@page { size: auto;  margin: 0mm; }
@page {
  size: A4;
  margin: 0;
}
@media print 
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
   <div class="container-fluid">
   <div class="row">
   <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
      <!-- Starting of Dashboard data-table area -->
      <div class="section-padding add-catalogItem-1">
         <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
               <div class="product__header">
                  <div class="row reorder-xs">
                     <div class="col-lg-8 col-md-5 col-sm-5 col-xs-12">
                        <div class="catalogItem-header-name">
                           <h2>{{ __('Purchase#') }} {{$purchase->purchase_number}} [{{$purchase->status}}]</h2>
                        </div>
                     </div>
                     <div class="row">
                        <div class="col-md-10">
                           <div class="dashboard-content">
                              <div class="view-purchase-page" id="print">
                                 <p class="purchase-date" style="margin-left: 2%">{{ __('Purchase Date') }} {{date('d-M-Y',strtotime($purchase->created_at))}}</p>
                                 <div class="invoice__metaInfo">
                                    <div class="col-md-6">
                                       <h5>{{ __('Billing Address') }}</h5>
                                       <address>
                                          {{ __('Name:') }} {{$purchase->customer_name}}<br>
                                          {{ __('Email:') }} {{$purchase->customer_email}}<br>
                                          {{ __('Phone:') }} {{$purchase->customer_phone}}<br>
                                          {{ __('Address:') }} {{$purchase->customer_address}}<br>
                                          {{$purchase->customer_city}}-{{$purchase->customer_zip}}
                                       </address>
                                       <h5>{{ __('Payment Information') }}</h5>
                                       <p>{{ __('Tax:') }}  {{ \PriceHelper::showOrderCurrencyPrice((($purchase->tax) / $purchase->currency_value),$purchase->currency_sign) }}</p>
                                       <p>{{ __('Paid Amount:') }} {{ \PriceHelper::showOrderCurrencyPrice(($purchase->pay_amount  * $purchase->currency_value),$purchase->currency_sign) }}</p>
                                       <p>{{ __('Payment Method:') }} {{$purchase->method}}</p>
                                       @if($purchase->method != "Cash On Delivery")
                                       @if($purchase->method=="Stripe")
                                       {{$purchase->method}} {{ __('Charge ID:') }}
                                       <p>{{$purchase->charge_id}}</p>
                                       @endif
                                       {{$purchase->method}} {{ __('Transaction ID:') }}
                                       <p id="ttn">{{$purchase->txnid}}</p>
                                       @endif
                                    </div>
                                    <div class="col-md-6" style="width: 50%;">
                                       <h5>{{ __('Shipping Address') }}</h5>
                                       <address>
                                          {{ __('Name:') }} {{$purchase->customer_name}}<br>
                                          {{ __('Email:') }} {{$purchase->customer_email}}<br>
                                          {{ __('Phone:') }} {{$purchase->customer_phone}}<br>
                                          {{ __('Address:') }} {{$purchase->customer_address}}<br>
                                          {{$purchase->customer_city}}-{{$purchase->customer_zip}}
                                       </address>
                                       <h5>{{ __('Delivery Method') }}</h5>
                                       {{-- Pure DTO - No Model Calls --}}
                                       @if ($trackingData['hasLocalCourier'] && $trackingData['firstDelivery'])
                                          <p><strong>{{ __('Local Courier Delivery') }}</strong></p>
                                          <p>
                                             {{ __('Courier:') }} {{ $trackingData['firstDelivery']['courierName'] }}<br>
                                             @if($trackingData['firstDelivery']['hasCourierPhone'])
                                             {{ __('Phone:') }} {{ $trackingData['firstDelivery']['courierPhone'] }}<br>
                                             @endif
                                             {{ __('Delivery Fee:') }} {{ \PriceHelper::showOrderCurrencyPrice($trackingData['firstDelivery']['deliveryFee'] * $purchase->currency_value, $purchase->currency_sign) }}<br>
                                             {{ __('Status:') }} {{ $trackingData['firstDelivery']['statusLabel'] }}
                                          </p>
                                       @else
                                          <p>{{ __('Ship To Address') }}</p>
                                       @endif
                                       {{-- Shipment Tracking - Pure DTO --}}
                                       @if($trackingData['hasTrackings'])
                                       <h5>{{ __('Shipment Info') }}</h5>
                                       @foreach($trackingData['trackings'] as $tracking)
                                          <p>
                                             <strong>{{ __('Tracking:') }}</strong> {{ $tracking['trackingNumber'] ?? '-' }}<br>
                                             <strong>{{ __('Company:') }}</strong> {{ $tracking['companyName'] ?? 'N/A' }}<br>
                                             <strong>{{ __('Status:') }}</strong> {{ $tracking['statusDisplay'] }}
                                          </p>
                                       @endforeach
                                       @endif
                                    </div>
                                 </div>
                                 <br>
                                 <br>
                                 <div class="table-responsive">
                                    <table id="example" class="table">
                                       <h4 class="text-center">{{ __('Purchased Items:') }}</h4>
                                       <hr>
                                       <thead>
                                          <tr>
                                             <th width="10%">{{ __('ID#') }}</th>
                                             <th>{{ __('Name') }}</th>
                                             <th width="20%">{{ __('Details') }}</th>
                                             <th width="20%">{{ __('Price') }}</th>
                                             <th width="10%">{{ __('Total') }}</th>
                                          </tr>
                                       </thead>
                                       <tbody>
                                          @foreach($cart['items'] as $catalogItem)
                                          <tr>
                                             <td>{{ $catalogItem['item']['id'] }}</td>
                                             <td>{{ getLocalizedCatalogItemName($catalogItem['item'], 50) }}</td>
                                             <td>
                                                <b>{{ __('Quantity') }}</b>: {{$catalogItem['qty']}} <br>
                                                @if(!empty($catalogItem['size']))
                                                <b>{{ __('Size') }}</b>: {{ $catalogItem['item']['measure'] }}{{str_replace('-',' ',$catalogItem['size'])}} <br>
                                                @endif
                                                @if(!empty($catalogItem['color']))
                                                @php
                                                    $clr = $catalogItem['color'];
                                                    $colorHex = is_array($clr) ? ($clr['code'] ?? $clr['color'] ?? '') : $clr;
                                                @endphp
                                                @if($colorHex)
                                                <b>{{ __('Color') }}</b>:  <span id="color-bar" style="border-radius: 50%; vertical-align: bottom; border: 10px solid #{{ $colorHex }};"></span>
                                                @endif
                                                @endif
                                                @if(!empty($catalogItem['keys']))
                                                @foreach( array_combine(explode(',', $catalogItem['keys']), explode(',', $catalogItem['values']))  as $key => $value)
                                                <b>{{ ucwords(str_replace('_', ' ', $key))  }} : </b> {{ $value }} <br>
                                                @endforeach
                                                @endif
                                             </td>
                                             <td>
                                                {{ \PriceHelper::showCurrencyPrice(($catalogItem['item_price'] ) * $purchase->currency_value) }}
                                             </td>
                                             <td>
                                                {{ \PriceHelper::showCurrencyPrice(($catalogItem['item_price'] * $catalogItem['qty'] ) * $purchase->currency_value) }} <small>{{ $catalogItem['discount'] == 0 ? '' : '('.$catalogItem['discount'].'% '.__('Off').')' }}</small>
                                             </td>
                                          </tr>
                                          @endforeach
                                       </tbody>
                                    </table>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <!-- Ending of Dashboard data-table area -->
   </div>
   <!-- ./wrapper -->
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
