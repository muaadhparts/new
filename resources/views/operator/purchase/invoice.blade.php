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
                        @php
                            // Get first seller info for header (or platform if multiple)
                            $firstSeller = count($sellersInfoLookup) > 0 ? reset($sellersInfoLookup) : null;
                            // Show platform if: no seller found, multiple sellers, or first seller is platform
                            $showPlatform = !$firstSeller || count($sellersInfoLookup) > 1 || ($firstSeller['is_platform'] ?? true);
                        @endphp
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
                                        @php
                                        $subtotal = 0;
                                        $tax = 0;
                                        @endphp
                                        @foreach($cart['items'] as $catalogItem)
                                        <tr>
                                            <td width="40%">
                                                @php
                                                $user = isset($catalogItem['item']['user_id']) && $catalogItem['item']['user_id'] != 0
                                                    ? App\Models\User::find($catalogItem['item']['user_id'])
                                                    : null;
                                                $invoiceProductUrl = !empty($catalogItem['item']['part_number'])
                                                    ? route('front.part-result', $catalogItem['item']['part_number'])
                                                    : '#';
                                                @endphp
                                                @if(isset($user))
                                                <a target="_blank"
                                                    href="{{ $invoiceProductUrl }}">{{ getLocalizedCatalogItemName($catalogItem['item']) }}</a>
                                                @else
                                                <a href="javascript:;">{{ getLocalizedCatalogItemName($catalogItem['item']) }}</a>
                                                @endif
                                                <br><small class="text-muted">PART_NUMBER: {{ $catalogItem['item']['part_number'] ?? 'N/A' }}</small>
                                            </td>
                                            <td width="20%">
                                                @if(isset($catalogItem['merchant_name']))
                                                    <strong>{{ __('Merchant') }}:</strong> {{ $catalogItem['merchant_name'] }}<br>
                                                @elseif(isset($user))
                                                    <strong>{{ __('Merchant') }}:</strong> {{ $user->shop_name ?? $user->name }}<br>
                                                @endif
                                                {{-- Branch Info --}}
                                                @php
                                                    $itemMerchantId = $catalogItem['item']['user_id'] ?? 0;
                                                    $branchInfo = $branchesLookup[$itemMerchantId] ?? null;
                                                @endphp
                                                @if($branchInfo)
                                                    <strong><i class="fas fa-warehouse"></i> {{ __('Branch') }}:</strong> {{ $branchInfo['name'] }}
                                                    @if($branchInfo['city'])
                                                        <small>({{ $branchInfo['city'] }})</small>
                                                    @endif
                                                    <br>
                                                @endif
                                                @if(isset($catalogItem['item']['brand_name']))
                                                    <strong>{{ __('Brand') }}:</strong> {{ $catalogItem['item']['brand_name'] }}<br>
                                                @endif
                                                @php
                                                    // جودة البراند والشركة المصنعة
                                                    $invoiceQualityBrand = null;
                                                    // Check both new name (quality_brand_id) and old name (quality_brand_id) for backward compatibility
                                                    $qbId = $catalogItem['quality_brand_id'] ?? $catalogItem['quality_brand_id'] ?? null;
                                                    if ($qbId) {
                                                        $invoiceQualityBrand = \App\Models\QualityBrand::find($qbId);
                                                    }
                                                @endphp
                                                @if($invoiceQualityBrand)
                                                    <strong>{{ __('Quality Brand') }}:</strong> {{ getLocalizedQualityName($invoiceQualityBrand) }}<br>
                                                @elseif(isset($catalogItem['quality_name']))
                                                    <strong>{{ __('Quality') }}:</strong> {{ $catalogItem['quality_name'] }}<br>
                                                @endif
                                                @php
                                                    $invoiceCondition = isset($catalogItem['item']['item_condition']) && $catalogItem['item']['item_condition'] == 1 ? __('Used') : __('New');
                                                @endphp
                                                <strong>{{ __('Condition') }}:</strong> {{ $invoiceCondition }}
                                            </td>


                                            <td>
                                                @if($catalogItem['size'])
                                               <p>
                                                    <strong>{{ __('Size') }} :</strong> {{str_replace('-',' ',$catalogItem['size'])}}
                                               </p>
                                               @endif
                                               @if($catalogItem['color'])
                                                @php
                                                    $clr = $catalogItem['color'];
                                                    $colorHex = is_array($clr) ? ($clr['code'] ?? $clr['color'] ?? '') : $clr;
                                                @endphp
                                                @if($colorHex)
                                                <p>
                                                        <strong>{{ __('color') }} :</strong> <span
                                                        style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; border-radius: 50%; background: #{{ $colorHex }};"></span>
                                                </p>
                                                @endif
                                                @endif
                                                <p>
                                                        <strong>{{ __('Price') }} :</strong>{{ \PriceHelper::showCurrencyPrice(($catalogItem['item_price'] ) * $purchase->currency_value) }}
                                                </p>
                                               <p>
                                                    <strong>{{ __('Qty') }} :</strong> {{$catalogItem['qty']}} {{ $catalogItem['item']['measure'] }}
                                               </p>

                                                    @if(!empty($catalogItem['keys']))

                                                    @foreach( array_combine(explode(',', $catalogItem['keys']), explode(',', $catalogItem['values']))  as $key => $value)
                                                    <p>
                                                        <b>{{ ucwords(str_replace('_', ' ', $key))  }} : </b> {{ $value }} 
                                                    </p>
                                                    @endforeach
                                                    @endif
                                            </td>


                                            <td>{{ \PriceHelper::showCurrencyPrice($catalogItem['price'] * $purchase->currency_value)  }} <small>{{ $catalogItem['discount'] == 0 ? '' : '('.$catalogItem['discount'].'% '.__('Off').')' }}</small>
                                            </td>
                                            @php
                                            $subtotal += round(($catalogItem['price'] / $purchase->currency_value) * $purchase->currency_value, 2);
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
                                                <td colspan="2">{{ DB::table('shippings')->where('price','=',$price)->first()->name }}({{$purchase->currency_sign}})</td>
                                                <td>{{ \PriceHelper::showOrderCurrencyPrice($purchase->shipping_cost,$purchase->currency_sign) }}</td>
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