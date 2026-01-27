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
                        <div class="invoice__logo text-left">
                           @if($sellerInfo['logo_url'])
                               <img src="{{ $sellerInfo['logo_url'] }}" alt="{{ $sellerInfo['name'] }}" style="width: 150px; height: auto; object-fit: contain;">
                           @endif
                           <div style="margin-top: 10px;">
                               <strong>{{ $sellerInfo['name'] }}</strong>
                               @if($sellerInfo['address'])
                                   <br><small>{{ $sellerInfo['address'] }}</small>
                               @endif
                           </div>
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
                        <span><strong>{{ __('Purchase Date') }} :</strong> {{ $printDisplay['date_formatted'] }}</span><br>
                        <span><strong>{{  __('Purchase ID')}} :</strong> {{ $purchase->purchase_number }}</span><br>
                        {{-- Branch Info --}}
                        @if(!empty($branchData))
                        <span><strong>{{ __('Branch') }} :</strong> {{ $branchData['name'] }}@if($branchData['city']) ({{ $branchData['city'] }})@endif</span><br>
                        @endif
                        <span> <strong>{{ __('Shipping Method') }} :</strong>
                            {{ __('Ship To Address') }}
                        </span><br>
                        <span> <strong>{{ __('Payment Method') }} :</strong> {{$purchase->method}}</span>
                        {{-- Customer Shipping Choice (from $trackingData) --}}
                        @if ($trackingData['hasCustomerChoice'])
                        <br><span><strong>{{ __('Customer Selected') }}:</strong> {{ $trackingData['customerChoiceCompany'] ?? 'N/A' }}</span>
                        @endif
                        {{-- Shipment Tracking (from $trackingData) --}}
                        @if ($trackingData['hasShipment'])
                        <br><span><strong>{{ __('Tracking') }}:</strong> {{ $trackingData['trackingNumber'] ?? '-' }}</span>
                        <br><span><strong>{{ __('Shipping Company') }}:</strong> {{ $trackingData['companyName'] ?? 'N/A' }}</span>
                        <br><span><strong>{{ __('Status') }}:</strong> {{ $trackingData['statusDisplay'] }}</span>
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
                                            <th>{{ __('Details') }}</th>
                                            <th>{{ __('Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- All calculations pre-computed in Controller (DATA_FLOW_POLICY) --}}
                                        @foreach($cart['items'] as $cartKey => $catalogItem)
                                        @if($catalogItem['item']['user_id'] != 0)
                                            @if($catalogItem['item']['user_id'] == $user->id)
                                        <tr>
                                            <td width="50%">
                                                {{ getLocalizedCatalogItemName($catalogItem['item']) }}
                                                <br><small>PART_NUMBER: {{ $catalogItem['item']['part_number'] ?? 'N/A' }}</small>
                                            </td>

                                            <td>
                                                <p>
                                                        <strong>{{ __('Price') }} :</strong>
                                                        {{ $cartItemsDisplay[$cartKey]['price_formatted'] }}
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

                                            <td>
                                                {{ $cartItemsDisplay[$cartKey]['price_formatted'] }} <small>{{ $cartItemsDisplay[$cartKey]['discount_text'] }}</small>
                                            </td>
                                        </tr>

                                        @endif
                                    @endif

                                        @endforeach

                                        <tr class="semi-border">
                                            <td colspan="1"></td>
                                            <td><strong>{{ __('Subtotal') }}</strong></td>
                                            <td>
                                            {{ $printCalculations['subtotal_formatted'] }}
                                            </td>
                                        </tr>
                                        @if($printCalculations['showShippingCost'])
                                            <tr class="no-border">
                                                <td colspan="1"></td>
                                                <td><strong>{{ __('Shipping Cost') }}</strong></td>
                                                <td>
                                                {{ $printCalculations['shippingCost_formatted'] }}
                                                </td>
                                            </tr>
                                        @endif
                                        @if($printCalculations['showTax'])
                                        <tr class="no-border">
                                            <td colspan="1"></td>
                                            <td><strong>{{ __('TAX') }}</strong></td>
                                            <td>
                                            {{ $printCalculations['tax_formatted'] }}
                                            </td>
                                        </tr>
                                        @endif

                                        <tr class="final-border">
                                            <td colspan="1"></td>
                                            <td><strong>{{ __('Total') }}</strong></td>
                                            <td>
                                            {{ $printCalculations['total_formatted'] }}
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
