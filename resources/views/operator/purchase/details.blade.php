@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">{{ __('Purchase Details') }} <a class="add-btn" href="javascript:history.back();"><i
                            class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
                <ul class="links">
                    <li>
                        <a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }} </a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Purchases') }}</a>
                    </li>
                    <li>
                        <a href="javascript:;">{{ __('Purchase Details') }}</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="purchase-table-wrap">
        @include('alerts.operator.form-both')
        @include('alerts.form-success')
        <div class="row">

            <div class="col-lg-6">
                <div class="special-box">
                    <div class="heading-area">
                        <h4 class="title">
                            {{ __('Purchase Details') }}
                        </h4>
                    </div>
                    <div class="table-responsive-sm">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th class="45%" width="45%">{{ __('Purchase ID') }}</th>
                                    <td width="10%">:</td>
                                    <td class="45%" width="45%">{{$purchase->purchase_number}}</td>
                                </tr>
                                <tr>
                                    <th width="45%">{{ __('Total Items') }}</th>
                                    <td width="10%">:</td>
                                    <td width="45%">{{$purchase->totalQty}}</td>
                                </tr>
                                @if($purchase->shipping_title != null)
                                <tr>
                                    <th width="45%">{{ __('Shipping Method') }}</th>
                                    <td width="10%">:</td>
                                    <td width="45%">{{ $purchase->shipping_title }}</td>
                                </tr>
                                @endif

                                @if($purchase->shipping_cost != 0)
                                <tr>
                                    <th width="45%">{{ __('Shipping Cost') }}</th>
                                    <td width="10%">:</td>
                                    <td width="45%">{{
                                        \PriceHelper::showOrderCurrencyPrice($purchase->shipping_cost,$purchase->currency_sign)
                                        }}</td>
                                </tr>
                                @endif

                                @if($purchase->tax != 0)
                                <tr>
                                    <th width="45%">{{ __('Tax :') }}</th>
                                    <td width="10%">:</td>
                                    <td width="45%"> {{ \PriceHelper::showOrderCurrencyPrice((($purchase->tax) /
                                        $purchase->currency_value),$purchase->currency_sign) }}</td>
                                </tr>
                                @endif

                                @if($purchase->packing_title != null)
                                <tr>
                                    <th width="45%">{{ __('Packaging Method') }}</th>
                                    <td width="10%">:</td>
                                    <td width="45%">{{ $purchase->packing_title }}</td>
                                </tr>
                                @endif

                                @if($purchase->packing_cost != 0)

                                <tr>
                                    <th width="45%">{{ __('Packaging Cost') }}</th>
                                    <td width="10%">:</td>
                                    <td width="45%">{{
                                        \PriceHelper::showOrderCurrencyPrice($purchase->packing_cost,$purchase->currency_sign)
                                        }}</td>
                                </tr>

                                @endif


                                @if($purchase->wallet_price != 0)
                                <tr>
                                    <th width="45%">{{ __('Paid From Wallet') }}</th>
                                    <td width="10%">:</td>
                                    <td width="45%">{{ \PriceHelper::showOrderCurrencyPrice(($purchase->wallet_price *
                                        $purchase->currency_value),$purchase->currency_sign) }}</td>
                                </tr>

                                @if($purchase->method != "Wallet")
                                <tr>
                                    <th width="45%">{{$purchase->method}}</th>
                                    <td width="10%">:</td>
                                    <td width="45%">{{ \PriceHelper::showOrderCurrencyPrice(($purchase->pay_amount *
                                        $purchase->currency_value),$purchase->currency_sign) }}</td>
                                </tr>
                                @endif

                                @endif

                                <tr>
                                    <th width="45%">{{ __('Total Cost') }}</th>
                                    <td width="10%">:</td>
                                    <td width="45%">{{ \PriceHelper::showOrderCurrencyPrice((($purchase->pay_amount +
                                        $purchase->wallet_price) * $purchase->currency_value),$purchase->currency_sign) }}</td>
                                </tr>
                                <tr>
                                    <th width="45%">{{ __('Purchase Date') }}</th>
                                    <td width="10%">:</td>
                                    <td width="45%">{{date('d-M-Y H:i:s a',strtotime($purchase->created_at))}}</td>
                                </tr>
                                <tr>
                                    <th width="45%">{{ __('Payment Method') }}</th>
                                    <td width="10%">:</td>
                                    <td width="45%">{{$purchase->method}}</td>
                                </tr>

                                @if($purchase->method != "Cash On Delivery" && $purchase->method != "Wallet")
                                @if($purchase->method=="Stripe")
                                <tr>
                                    <th width="45%">{{$purchase->method}} {{ __('Charge ID') }}</th>
                                    <td width="10%">:</td>
                                    <td width="45%">{{$purchase->charge_id}}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th width="45%">{{$purchase->method}} {{ __('Transaction ID') }}</th>
                                    <td width="10%">:</td>
                                    <td width="45%">{{$purchase->txnid}}</td>
                                </tr>
                                @endif


                                <th width="45%">{{ __('Payment Status') }}</th>
                                <th width="10%">:</th>

                                @if($purchase->payment_status == 'Pending')
                                <span class='badge badge-danger'>{{__('Unpaid')}}</span>
                                @else
                                <span class='badge badge-success'>{{__('Paid')}}</span>
                                @endif

                                @if(!empty($purchase->order_note))
                                <th width="45%">{{ __('Purchase Note') }}</th>
                                <th width="10%">:</th>
                                <td width="45%">{{$purchase->order_note}}</td>
                                @endif

                            </tbody>
                        </table>
                    </div>
                    <div class="footer-area">
                        <a href="{{ route('operator-purchase-invoice',$purchase->id) }}" class="btn btn-primary"><i
                                class="fas fa-eye"></i> {{ __('View Invoice') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="special-box">
                    <div class="heading-area">
                        <h4 class="title">
                            {{ __('Billing Details') }}
                            <a class="f15" href="javascript:;" data-bs-toggle="modal"
                                data-bs-target="#billing-details-edit"><i class="fas fa-edit"></i>{{ __("Edit") }}</a>
                        </h4>
                    </div>
                    <div class="table-responsive-sm">
                        <table class="table">
                            <tbody>
                                <tr>
                                    <th width="45%">{{ __('Name') }}</th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{$purchase->customer_name}}</td>
                                </tr>
                                <tr>
                                    <th width="45%">{{ __('Email') }}</th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{$purchase->customer_email}}</td>
                                </tr>
                                <tr>
                                    <th width="45%">{{ __('Phone') }}</th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{$purchase->customer_phone}}</td>
                                </tr>
                                <tr>
                                    <th width="45%">{{ __('Address') }}</th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{$purchase->customer_address}}</td>
                                </tr>
                                <tr>
                                    <th width="45%">{{ __('Country') }}</th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{$purchase->customer_country}}</td>
                                </tr>
                                @if($purchase->customer_state != null)
                                <tr>
                                    <th width="45%">{{ __('State') }}</th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{$purchase->customer_state}}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th width="45%">{{ __('City') }}</th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{$purchase->customer_city}}</td>
                                </tr>
                                <tr>
                                    <th width="45%">{{ __('Postal Code') }}</th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{$purchase->customer_zip}}</td>
                                </tr>
                                @if($purchase->discount_code != null)
                                <tr>
                                    <th width="45%">{{ __('Discount Code') }}</th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{$purchase->discount_code}}</td>
                                </tr>
                                @endif
                                @if($purchase->discount_amount != null)
                                <tr>
                                    <th width="45%">{{ __('Discount Amount') }}</th>
                                    <th width="10%">:</th>
                                    @if($gs->currency_format == 0)
                                    <td width="45%">{{ $purchase->currency_sign }}{{ $purchase->discount_amount }}</td>
                                    @else
                                    <td width="45%">{{ $purchase->discount_amount }}{{ $purchase->currency_sign }}</td>
                                    @endif
                                </tr>
                                @endif
                                @if($purchase->affilate_user != null)
                                <tr>
                                    <th width="45%">{{ __('Affilate User') }}</th>
                                    <th width="10%">:</th>
                                    <td width="45%">
                                        @if( App\Models\User::where('id', $purchase->affilate_user)->exists() )
                                        {{ App\Models\User::where('id', $purchase->affilate_user)->first()->name }}
                                        @else
                                        {{ __('Deleted') }}
                                        @endif
                                    </td>
                                </tr>
                                @endif
                                @if($purchase->affilate_charge != null)
                                <tr>
                                    <th width="45%">{{ __('Affilate Charge') }}</th>
                                    <th width="10%">:</th>
                                    <td width="45%">
                                        {{ \PriceHelper::showOrderCurrencyPrice(($purchase->affilate_charge *
                                        $purchase->currency_value),$purchase->currency_sign) }}
                                    </td>

                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($purchase->dp == 0)
            <div class="col-lg-6">
                <div class="special-box">
                    <div class="heading-area">
                        <h4 class="title">
                            {{ __('Shipping Details') }}
                            <a class="f15" href="javascript:;" data-bs-toggle="modal"
                                data-bs-target="#shipping-details-edit"><i class="fas fa-edit"></i>{{ __("Edit") }}</a>
                        </h4>
                    </div>
                    <div class="table-responsive-sm">
                        <table class="table">
                            <tbody>
                                @if($purchase->shipping == "pickup")
                                <tr>
                                    <th width="45%"><strong>{{ __('Pickup Location') }}:</strong></th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{$purchase->pickup_location}}</td>
                                </tr>
                                @else
                                <tr>
                                    <th width="45%"><strong>{{ __('Name') }}:</strong></th>
                                    <th width="10%">:</th>
                                    <td>{{ $purchase->customer_name }}</td>
                                </tr>
                                <tr>
                                    <th width="45%"><strong>{{ __('Email') }}:</strong></th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{ $purchase->customer_email }}</td>
                                </tr>
                                <tr>
                                    <th width="45%"><strong>{{ __('Phone') }}:</strong></th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{ $purchase->customer_phone }}</td>
                                </tr>
                                <tr>
                                    <th width="45%"><strong>{{ __('Address') }}:</strong></th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{ $purchase->customer_address }}</td>
                                </tr>
                                <tr>
                                    <th width="45%"><strong>{{ __('Country') }}:</strong></th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{ $purchase->customer_country }}</td>
                                </tr>


                                <tr>
                                    <th width="45%">{{ __('State') }}</th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{ $purchase->customer_state }}</td>
                                </tr>



                                <tr>
                                    <th width="45%"><strong>{{ __('City') }}:</strong></th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{$purchase->shipping_city == null ? $purchase->customer_city :
                                        $purchase->shipping_city}}</td>
                                </tr>
                                <tr>
                                    <th width="45%"><strong>{{ __('Postal Code') }}:</strong></th>
                                    <th width="10%">:</th>
                                    <td width="45%">{{$purchase->shipping_zip == null ? $purchase->customer_zip :
                                        $purchase->shipping_zip}}</td>
                                </tr>


                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Shipment Status Section --}}
        @php
            $shipments = App\Models\ShipmentStatusLog::where('purchase_id', $purchase->id)
                ->select('merchant_id', 'company_name', 'tracking_number', 'status', 'status_ar', 'message', 'message_ar', 'location', 'status_date')
                ->orderBy('merchant_id')
                ->orderBy('status_date', 'desc')
                ->get()
                ->groupBy('merchant_id');

            $deliveries = App\Models\DeliveryCourier::where('purchase_id', $purchase->id)->get();
        @endphp

        @if($shipments->count() > 0 || $deliveries->count() > 0)
        <div class="row mt-4">
            <div class="col-lg-12">
                <div class="special-box">
                    <div class="heading-area">
                        <h4 class="title">
                            <i class="fas fa-truck"></i> {{ __('Shipment Status') }}
                        </h4>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('Merchant') }}</th>
                                    <th>{{ __('Shipping Company') }}</th>
                                    <th>{{ __('Tracking Number') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Location') }}</th>
                                    <th>{{ __('Last Update') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shipments as $merchantId => $merchantShipments)
                                    @php
                                        $latestShipment = $merchantShipments->first();
                                        $merchant = App\Models\User::find($merchantId);
                                    @endphp
                                    <tr>
                                        <td>{{ $merchant->shop_name ?? $merchant->name ?? 'Merchant #' . $merchantId }}</td>
                                        <td>
                                            <span class="badge badge-info">{{ $latestShipment->company_name }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-primary">{{ $latestShipment->tracking_number }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge
                                                @if($latestShipment->status == 'delivered') badge-success
                                                @elseif($latestShipment->status == 'in_transit') badge-primary
                                                @elseif($latestShipment->status == 'out_for_delivery') badge-info
                                                @elseif(in_array($latestShipment->status, ['failed', 'returned', 'cancelled'])) badge-danger
                                                @else badge-secondary
                                                @endif">
                                                {{ $latestShipment->status_ar ?? $latestShipment->status }}
                                            </span>
                                        </td>
                                        <td>{{ $latestShipment->location ?? '-' }}</td>
                                        <td>{{ $latestShipment->status_date ? $latestShipment->status_date->format('Y-m-d H:i') : '-' }}</td>
                                    </tr>
                                @endforeach

                                @foreach($deliveries as $delivery)
                                    @php
                                        $merchant = App\Models\User::find($delivery->merchant_id);
                                    @endphp
                                    <tr>
                                        <td>{{ $merchant->shop_name ?? $merchant->name ?? 'Merchant #' . $delivery->merchant_id }}</td>
                                        <td>
                                            <span class="badge badge-secondary">{{ __('Local Courier') }}</span>
                                        </td>
                                        <td>{{ $delivery->courier->name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge
                                                @if($delivery->status == 'delivered') badge-success
                                                @elseif($delivery->status == 'accepted') badge-primary
                                                @elseif($delivery->status == 'rejected') badge-danger
                                                @else badge-warning
                                                @endif">
                                                {{ ucfirst($delivery->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $delivery->servicearea->name ?? '-' }}</td>
                                        <td>{{ $delivery->updated_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @php
        foreach ($cart['items'] as $key => $item) {
        $userId = $item["user_id"];
        if (!isset($resultArray[$userId])) {
        $resultArray[$userId] = [];
        }
        $resultArray[$userId][$key] = $item;
        }

        @endphp

        <div class="row">
            <div class="col-lg-12 purchase-details-table">

                @foreach($resultArray as $key1 => $catalogItem)

                @php

                if($key1 == 0){
                $merchant = App\Models\Operator::find(1);
                }else{
                $merchant = App\Models\User::find($key1);
                }

                @endphp
                <div class="mr-table">
                    <h4 class="title">
                        <a href="javascript:;" data-bs-toggle="modal" merchant="{{$key1}}"
                            merchant-store="{{$merchant->shop_name}}" class="btn btn-primary btn-sm pl-2 show_add_product"
                            data-bs-target="#add-catalogItem"><i class="fas fa-plus"></i>{{ __("Add Item") }}</a> {{
                        __('Items Purchased From') }} - <strong>{{$merchant->shop_name}}</strong>

                    </h4>
                    <div class="table-responsive">
                        <table class="table table-hover dt-responsive" cellspacing="0" width="100%">
                            <thead>

                                <tr>
                                    <th>{{ __('Item ID#') }}</th>
                                    <th>{{ __('Shop Name') }}</th>
                                    <th>{{ __('Merchant Status') }}</th>
                                    <th>{{ __('Item Title') }}</th>
                                    <th>{{ __('Details') }}</th>
                                    <th>{{ __('Total Price') }}</th>
                                    <th>{{ __('Action') }}</th>

                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $merchant_total = 0;
                                @endphp
                                @foreach ($catalogItem as $itemKey => $catalogItem)
                                @php
                                $merchant_total += $catalogItem['price'];
                                @endphp
                                <tr>
                                    <td><input type="hidden" value="{{$key1}}">{{ $catalogItem['item']['id'] }}</td>

                                    <td>
                                        @if($catalogItem['item']['user_id'] != 0)
                                        @php
                                        $user = App\Models\User::find($catalogItem['item']['user_id']);
                                        @endphp
                                        @if(isset($user))
                                        <a target="_blank"
                                            href="{{route('operator-merchant-show',$user->id)}}">{{$user->shop_name}}</a>
                                        @else
                                        {{ __('Merchant Removed') }}
                                        @endif
                                        @else
                                        <a href="javascript:;">{{ App\Models\Operator::find(1)->shop_name }}</a>
                                        @endif

                                    </td>
                                    <td>
                                        @if($catalogItem['item']['user_id'] != 0)
                                        @php
                                        $merchantPurchase = App\Models\MerchantPurchase::where('purchase_id','=',$purchase->id)->where('user_id','=',$catalogItem['item']['user_id'])->first();


                                        @endphp

                                        @if($purchase->dp == 1 && $purchase->payment_status == 'Completed')

                                        <span class="badge badge-success">{{ __('Completed') }}</span>

                                        @else
                                        @if($merchantPurchase->status == 'pending')
                                        <span class="badge badge-warning">{{ucwords($merchantPurchase->status)}}</span>
                                        @elseif($merchantPurchase->status == 'processing')
                                        <span class="badge badge-info">{{ucwords($merchantPurchase->status)}}</span>
                                        @elseif($merchantPurchase->status == 'on delivery')
                                        <span class="badge badge-primary">{{ucwords($merchantPurchase->status)}}</span>
                                        @elseif($merchantPurchase->status == 'completed')
                                        <span class="badge badge-success">{{ucwords($merchantPurchase->status)}}</span>
                                        @elseif($merchantPurchase->status == 'declined')
                                        <span class="badge badge-danger">{{ucwords($merchantPurchase->status)}}</span>
                                        @endif
                                        @endif

                                        @endif
                                    </td>


                                    <td>
                                        <input type="hidden" value="{{ $catalogItem['license'] }}">

                                        @php
                                        $detailsCatalogItemUrl = '#';
                                        if (isset($catalogItem['item']['slug']) && isset($catalogItem['user_id']) && isset($catalogItem['merchant_item_id'])) {
                                            $detailsCatalogItemUrl = route('front.catalog-item', [
                                                'slug' => $catalogItem['item']['slug'],
                                                'merchant_id' => $catalogItem['user_id'],
                                                'merchant_item_id' => $catalogItem['merchant_item_id']
                                            ]);
                                        } elseif (isset($catalogItem['item']['slug'])) {
                                            $detailsCatalogItemUrl = route('front.catalog-item.legacy', $catalogItem['item']['slug']);
                                        }
                                        @endphp
                                        <a target="_blank" href="{{ $detailsCatalogItemUrl }}">{{ getLocalizedCatalogItemName($catalogItem['item'], 30) }}</a>
                                        <br><small class="text-muted">PART_NUMBER: {{ $catalogItem['item']['part_number'] ?? 'N/A' }}</small>
                                        @php
                                        $user = isset($catalogItem['item']['user_id']) && $catalogItem['item']['user_id'] != 0
                                            ? App\Models\User::find($catalogItem['item']['user_id'])
                                            : null;
                                        @endphp
                                        @if(isset($user) || isset($catalogItem['merchant_name']))
                                        <p class="mb-0 mt-1">
                                            <strong>{{ __('Merchant') }}:</strong>
                                            {{ $catalogItem['merchant_name'] ?? ($user->shop_name ?? $user->name ?? '') }}
                                        </p>
                                        @endif
                                        @if(isset($catalogItem['item']['brand_name']))
                                        <p class="mb-0">
                                            <strong>{{ __('Brand') }}:</strong> {{ $catalogItem['item']['brand_name'] }}
                                        </p>
                                        @endif
                                        @php
                                            // جودة البراند والشركة المصنعة
                                            $qualityBrand = null;
                                            if (isset($catalogItem['brand_quality_id']) && $catalogItem['brand_quality_id']) {
                                                $qualityBrand = \App\Models\QualityBrand::find($catalogItem['brand_quality_id']);
                                            }
                                            // حالة المنتج (جديد/مستعمل)
                                            $itemCondition = isset($catalogItem['item']['item_condition']) && $catalogItem['item']['item_condition'] == 1 ? __('Used') : __('New');
                                        @endphp
                                        @if($qualityBrand)
                                        <p class="mb-0">
                                            <strong>{{ __('Quality Brand') }}:</strong> {{ getLocalizedQualityName($qualityBrand) }}
                                        </p>
                                        @endif
                                        <p class="mb-0">
                                            <strong>{{ __('Condition') }}:</strong>
                                            <span class="badge {{ isset($catalogItem['item']['item_condition']) && $catalogItem['item']['item_condition'] == 1 ? 'badge-warning' : 'badge-success' }}">{{ $itemCondition }}</span>
                                        </p>

                                        @if($catalogItem['license'] != '')
                                        <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#confirm-delete"
                                            class="btn btn-info btn-sm catalogItem-btn license"><i
                                                class="fa fa-eye"></i> {{ __('View License') }}</a>
                                        @endif

                                        @if($catalogItem['affilate_user'] != 0)
                                        <p>
                                            <strong>{{ __('Referral User') }} :</strong> {{
                                            \App\Models\User::find($catalogItem['affilate_user'])->name }}
                                        </p>
                                        @endif

                                    </td>
                                    <td>
                                        @if($catalogItem['size'])
                                        <p>
                                            <strong>{{ __('Size') }} :</strong> {{str_replace('-','
                                            ',$catalogItem['size'])}}
                                        </p>
                                        @endif
                                        @if($catalogItem['color'])
                                        <p>
                                            <strong>{{ __('color') }} :</strong> <span style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; border-radius: 50%; background: #{{$catalogItem['color']}};"></span>
                                        </p>
                                        @endif
                                        <p>
                                            <strong>{{ __('Price') }} :</strong> {{
                                            \PriceHelper::showCurrencyPrice(($catalogItem['item_price'] ) *
                                            $purchase->currency_value) }}
                                        </p>
                                        <p>
                                            <strong>{{ __('Qty') }} :</strong> {{$catalogItem['qty']}} {{
                                            $catalogItem['item']['measure'] }}
                                        </p>
                                        @if(!empty($catalogItem['keys']))

                                        @foreach( array_combine(explode(',', $catalogItem['keys']), explode(',',
                                        $catalogItem['values'])) as $key => $value)
                                        <p>
                                            <b>{{ ucwords(str_replace('_', ' ', $key)) }} : </b> {{ $value }}
                                        </p>
                                        @endforeach

                                        @endif

                                    </td>

                                    <td> {{ \PriceHelper::showCurrencyPrice($catalogItem['price'] *
                                        $purchase->currency_value)
                                        }} <small>{{ $catalogItem['discount'] == 0 ? '' : '('.$catalogItem['discount'].'%
                                            '.__('Off').')' }}</small>
                                    </td>


                                    <td>

                                        <div class="action-list">

                                            @if (App\Models\CatalogItem::whereId($catalogItem['item']['id'])->exists())
                                            <a class="btn btn-primary btn-sm edit-catalogItem" data-href="{{ route('operator-purchase-catalogItem-edit',[$itemKey, $catalogItem['item']['id'] ,$purchase->id]) }}"
                                                data-bs-toggle="modal" data-bs-target="#edit-catalogItem-modal">
                                                <i class="fas fa-edit"></i> {{ __("Edit") }}
                                            </a>
                                            @endif

                                            <a class="btn btn-danger btn-sm delete-catalogItem"
                                                data-href="{{ route('operator-purchase-catalogItem-delete',[$itemKey,$purchase->id]) }}"
                                                data-bs-toggle="modal" data-bs-target="#delete-catalogItem-modal">
                                                <i class="fas fa-trash"></i>
                                            </a>

                                        </div>

                                    </td>

                                </tr>

                                @endforeach
                                @php

                                $purchase_shipping = @json_decode($purchase->merchant_shipping_id, true);
                                $purchase_package = @json_decode($purchase->merchant_packing_id, true);

                                $merchant_shipping_id = @$purchase_shipping[$key1];
                                $merchant_package_id = @$purchase_package[$key1];
                                if($merchant_shipping_id){
                                $shipping = App\Models\Shipping::findOrFail($merchant_shipping_id);
                                }else{
                                $shipping = [];
                                }
                                if($merchant_package_id){
                                $package = App\Models\Package::findOrFail($merchant_package_id);
                                }else{
                                $package = [];
                                }

                                @endphp
                                <td colspan="7">
                                    <div class="text-right mx-4">
                                        @if ($shipping)
                                        <p>
                                            {{ __('Shipping Method') }} :
                                            <strong>{{$shipping->title}} | {{
                                                \PriceHelper::showCurrencyPrice($shipping->price *
                                                $purchase->currency_value) }}</strong>
                                        </p>
                                        @endif
                                        @if ($package)

                                        <p>
                                            {{ __('Packaging Method') }} :
                                            <strong>{{$package->title}} | {{
                                                \PriceHelper::showCurrencyPrice($package->price *
                                                $purchase->currency_value) }}</strong>
                                        </p>

                                        @endif
                                        <p>
                                            {{ __('Total Amount') }} :
                                            <strong>
                                                {{ \PriceHelper::showCurrencyPrice(($merchant_total +
                                                @$shipping->price + @$package->price ) *
                                                $purchase->currency_value )}}
                                            </strong>
                                        </p>

                                    </div>
                                </td>

                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="col-lg-12 text-center mt-2">
                <a class="btn btn-primary sendEmail send" href="javascript:;" data-email="{{ $purchase->customer_email }}"
                    data-bs-toggle="modal" data-bs-target="#merchantform">
                    <i class="fa fa-send"></i> {{ __('Send Email') }}
                </a>
            </div>
        </div>
    </div>
</div>
<!-- Main Content Area End -->
</div>
</div>


</div>

{{-- LICENSE MODAL --}}

<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header d-block text-center">
                <h4 class="modal-title d-inline-block">{{ __('License Key') }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    
                </button>
            </div>

            <div class="modal-body">
                <p class="text-center">{{ __('The Licenes Key is') }} : <span id="key"></span> <a href="javascript:;"
                        id="license-edit">{{ __('Edit License') }}</a><a href="javascript:;" id="license-cancel"
                        class="showbox">{{ __('Cancel') }}</a></p>
                <form method="POST" action="{{route('operator-purchase-license',$purchase->id)}}" id="edit-license"
                    style="display: none;">
                    {{csrf_field()}}
                    <input type="hidden" name="license_key" id="license-key" value="">
                    <div class="form-group text-center">
                        <input type="text" name="license" class="form-control d-inline-block" placeholder="{{ __('Enter New License Key') }}"
                            required="">
                        <input type="submit" name="submit" class="btn btn-primary btn-sm">
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>



{{-- LICENSE MODAL ENDS --}}

{{-- BILLING DETAILS EDIT MODAL --}}

@include('operator.purchase.partials.billing-details')

{{-- BILLING DETAILS MODAL ENDS --}}

{{-- SHIPPING DETAILS EDIT MODAL --}}

@include('operator.purchase.partials.shipping-details')

{{-- SHIPPING DETAILS MODAL ENDS --}}

{{-- ADD ITEM MODAL --}}

@include('operator.purchase.partials.add-catalogItem')

{{-- ADD ITEM MODAL ENDS --}}


{{-- EDIT ITEM MODAL --}}

<div class="modal fade" id="edit-catalogItem-modal" tabindex="-1" role="dialog" aria-labelledby="edit-catalogItem-modal"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="submit-loader">
                <img src="{{asset('assets/images/'.$gs->admin_loader)}}" alt="">
            </div>
            <div class="modal-header">
                <h5 class="modal-title">
                    {{ __('Edit Item') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    
                </button>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ __('Close') }}
                </button>
            </div>
        </div>
    </div>

</div>

{{-- EDIT ITEM MODAL ENDS --}}

{{-- DELETE ITEM MODAL --}}

<div class="modal fade" id="delete-catalogItem-modal" tabindex="-1" role="dialog" aria-labelledby="modal1"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header d-block text-center">
                <h4 class="modal-title d-inline-block">{{ __('Confirm Delete') }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    
                </button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <p class="text-center">{{ __('You are about to delete this item from this cart.') }}</p>
                <p class="text-center">{{ __('Do you want to proceed?') }}</p>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <a class="btn btn-danger btn-ok">{{ __('Delete') }}</a>

            </div>

        </div>
    </div>
</div>

{{-- DELETE ITEM MODAL ENDS --}}



{{-- MESSAGE MODAL --}}
<div class="sub-categori">
    <div class="modal" id="merchantform" tabindex="-1" role="dialog" aria-labelledby="merchantformLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="merchantformLabel">{{ __('Send Email') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        
                    </button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid p-0">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="contact-form">
                                    <form id="emailreply">
                                        {{csrf_field()}}
                                        <ul>
                                            <li>
                                                <input type="email" class="form-control eml-val" id="eml" name="to"
                                                    placeholder="{{ __('Email') }} *" value="" required="">
                                            </li>
                                            <li>
                                                <input type="text" class="form-control" id="subj" name="subject"
                                                    placeholder="{{ __('Subject') }} *" required="">
                                            </li>
                                            <li>
                                                <textarea class="form-control textarea" name="message" id="msg"
                                                    placeholder="{{ __('Your Message') }} *" required=""></textarea>
                                            </li>
                                        </ul>
                                        <button class="btn btn-primary" id="emlsub" type="submit">{{ __('Send Email')
                                            }}</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MESSAGE MODAL ENDS --}}

{{-- PURCHASE MODAL --}}

<div class="modal fade" id="confirm-delete2" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="submit-loader">
                <img src="{{asset('assets/images/'.$gs->admin_loader)}}" alt="">
            </div>
            <div class="modal-header d-block text-center">
                <h4 class="modal-title d-inline-block">{{ __('Update Status') }}</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                </button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <p class="text-center">{{ __("You are about to update the purchase's status.") }}</p>
                <p class="text-center">{{ __('Do you want to proceed?') }}</p>
            </div>

            <!-- Modal footer -->
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <a class="btn btn-success btn-ok purchase-btn">{{ __('Proceed') }}</a>
            </div>

        </div>
    </div>
</div>

{{-- PURCHASE MODAL ENDS --}}


@endsection


@section('scripts')

<script type="text/javascript">
    (function($) {
		"use strict";


$(document).on('click','.show_add_product',function(){
    let merchant_id = $(this).attr('merchant');
    $('#add_merchant_id').val(merchant_id);
    let message = `You can add only <strong>(${$(this).attr('merchant-store')})</strong> Store Items`;
    $('.show_merchant_message').html(message);
})


  function disablekey()
  {
    document.onkeydown = function (e)
    {
        return false;
    }
  }

  function enablekey()
  {
    document.onkeydown = function (e)
    {
        return true;
    }
  }

    $('#example2').dataTable( {
        "ordering": false,
            'lengthChange': false,
            'searching'   : false,
            'ordering'    : false,
            'info'        : false,
            'autoWidth'   : false,
            'responsive'  : true
    });

     $(document).on('click','.license' , function(e){
        var id = $(this).parent().find('input[type=hidden]').val();
        var key = $(this).parent().parent().find('input[type=hidden]').val();
        $('#key').html(id);  
        $('#license-key').val(key);    
    });

    $(document).on('click','#license-edit' , function(e){
        $(this).hide();
        $('#edit-license').show();
        $('#license-cancel').show();
    });

    $(document).on('click','#license-cancel' , function(e){
        $(this).hide();
        $('#edit-license').hide();
        $('#license-edit').show();
    });

    @if(Session::has('license'))

    $.notify('{{  Session::get('license')  }}','success');

    @endif

// ADD OPERATION

    $(document).on('click','.edit-catalogItem',function(){

        if(admin_loader == 1)
        {
            $('.submit-loader').show();
        }
        $('#edit-catalogItem-modal .modal-content .modal-body').html('').load($(this).data('href'),function(response, status, xhr){
            if(status == "success")
            {
                if(admin_loader == 1)
                {
                    $('.submit-loader').hide();
                }
            }
        });
    });

// ADD OPERATION END

// SHOW PRODUCT FORM SUBMIT

$(document).on('submit','#show-catalogItem',function(e){
  e.preventDefault();
  if(admin_loader == 1)
  {
    $('.submit-loader').show();
  }
    $('button.btn.btn-primary').prop('disabled',true);
    disablekey();
      $.ajax({
       method:"POST",
       url:$(this).prop('action'),
       data:new FormData(this),
       dataType:'JSON',
       contentType: false,
       cache: false,
       processData: false,
       success:function(data)
       {
        if(data[0]){
            $('#catalogItem-show').html('').load(mainurl+"/admin/purchase/catalogItem-show/"+data[1],function(response, status, xhr){
                if(status == "success")
                {
                    if(admin_loader == 1)
                    {
                        $('.submit-loader').hide();
                    }
                }
            });
        }
        else{
            if(admin_loader == 1)
            {
                $('.submit-loader').hide();
            }
            $('#catalogItem-show').html('<div class="col-lg-12 text-center"><h4>'+data[1]+'.</h4></div>')
        }

        $('button.btn.btn-primary').prop('disabled',false);

        enablekey();
       }

      });

});

// SHOW ITEM FORM SUBMIT ENDS


$('#delete-catalogItem-modal').on('show.bs.modal', function(e) {
    $(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
  });

})(jQuery);

</script>

@endsection