@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Purchase Invoice') }} <a class="add-btn" href="javascript:history.back();"><i
                            class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
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
    <div class="purchase-table-wrap">
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
                    <div class="col-lg-6 text-right">
                        <a class="btn  add-newProduct-btn print" href="{{route('operator-purchase-print',$purchase->id)}}"
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
            <div class="row invoice__metaInfo">
                <div class="col-lg-6">
                        <div class="invoice__shipping">
                            <p><strong>{{ __('Shipping Address') }}</strong></p>
                           <span><strong>{{ __('Customer Name') }}</strong>: {{ $purchase->customer_name }}</span><br>
                           <span><strong>{{ __('Address') }}</strong>: {{ $purchase->customer_address }}</span><br>
                           <span><strong>{{ __('City') }}</strong>: {{ $purchase->customer_city }}</span><br>
                           <span><strong>{{ __('Country') }}</strong>: {{ $purchase->customer_country }}</span>

                        </div>
                </div>

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
                                            <th>{{ __('CatalogItem') }}</th>
                                            <th>{{ __('Merchant/Brand') }}</th>
                                            <th>{{ __('Details') }}</th>
                                            <th>{{ __('Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- Subtotal pre-computed in DataBuilder (DATA_FLOW_POLICY) --}}
                                        @foreach($cartItemsWithData as $catalogItem)
                                        <tr>
                                            <td width="40%">
                                                <a target="_blank" href="{{ $catalogItem['_productUrl'] }}">{{ getLocalizedCatalogItemName($catalogItem['item']) }}</a>
                                                <br><small class="text-muted">PART_NUMBER: {{ $catalogItem['item']['part_number'] ?? 'N/A' }}</small>
                                            </td>
                                            <td width="20%">
                                                @if($catalogItem['_merchantName'])
                                                    <strong>{{ __('Merchant') }}:</strong> {{ $catalogItem['_merchantName'] }}<br>
                                                @endif
                                                @if($catalogItem['_branch'])
                                                    <strong><i class="fas fa-warehouse"></i> {{ __('Branch') }}:</strong> {{ $catalogItem['_branch']['name'] }}
                                                    @if($catalogItem['_branch']['city'])
                                                        <small>({{ $catalogItem['_branch']['city'] }})</small>
                                                    @endif
                                                    <br>
                                                @endif
                                                @if(isset($catalogItem['item']['brand_name']))
                                                    <strong>{{ __('Brand') }}:</strong> {{ $catalogItem['item']['brand_name'] }}<br>
                                                @endif
                                                @if($catalogItem['_qualityBrandName'])
                                                    <strong>{{ __('Quality Brand') }}:</strong> {{ $catalogItem['_qualityBrandName'] }}<br>
                                                @elseif(isset($catalogItem['quality_name']))
                                                    <strong>{{ __('Quality') }}:</strong> {{ $catalogItem['quality_name'] }}<br>
                                                @endif
                                                <strong>{{ __('Condition') }}:</strong> {{ $catalogItem['_condition'] }}
                                            </td>


                                            <td>
                                                <p>
                                                        <strong>{{ __('Price') }} :</strong>{{ \PriceHelper::showCurrencyPrice(($catalogItem['price'] ?? 0) * $purchase->currency_value) }}
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


                                            <td>{{ \PriceHelper::showCurrencyPrice(($catalogItem['price'] ?? 0) * $purchase->currency_value)  }} <small>{{ ($catalogItem['discount'] ?? 0) == 0 ? '' : '('.$catalogItem['discount'].'% '.__('Off').')' }}</small>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>

                                    <tfoot>
                                        <tr>
                                            <td colspan="2">{{ __('Subtotal') }}</td>
                                            <td>{{ \PriceHelper::showCurrencyPrice($subtotal  * $purchase->currency_value) }}</td>
                                        </tr>
                                        @if($purchase->shipping_cost != 0 && $shippingMethodName)
                                            <tr>
                                                <td colspan="2">{{ $shippingMethodName }}({{$purchase->currency_sign}})</td>
                                                <td>{{ \PriceHelper::showOrderCurrencyPrice($purchase->shipping_cost,$purchase->currency_sign) }}</td>
                                            </tr>
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