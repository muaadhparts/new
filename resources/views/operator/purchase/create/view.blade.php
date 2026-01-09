@extends('layouts.operator')
     
@section('styles')

<style type="text/css">
    .purchase-table-wrap table#example2 {
    margin: 10px 20px;
}

</style>

@endsection


@section('content')
 

<div class="content-area">
    <div class="mr-breadcrumb">
       <div class="row">
          <div class="col-lg-12">
             <h4 class="heading">{{ __('Purchase Details') }} <a class="add-btn" href="javascript:history.back();"><i class="fas fa-arrow-left"></i> {{ __('Back') }}</a></h4>
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
       <div class="row">
        
          <div class="col-lg-12 purchase-details-table">
            <div class="mr-table">
                <h4 class="title">
                    {{ __('CatalogItems') }}
                </h4>
                <div class="table-responsive">
                    <table class="table table-hover dt-responsive" cellspacing="0" width="100%">
                        <thead>
                           <tr>
                           <tr>
                              <th>{{ __('CatalogItem ID#') }}</th>
                              <th>{{ __('CatalogItem Title') }}</th>
                              <th>{{ __('Price') }}</th>
                              <th>{{ __('Details') }}</th>
                              <th>{{ __('Subtotal') }}</th>
                           </tr>
                           </tr>
                        </thead>
                        <tbody>
                           @foreach($cart->items as $key1 => $catalogItem)
                          
                           <tr>
                              <td><input type="hidden" value="{{$key1}}">{{ $catalogItem['item']['id'] }}</td>
                              <td>
                                <img src="{{ filter_var($catalogItem['item']['photo'] ?? '', FILTER_VALIDATE_URL) ? $catalogItem['item']['photo'] : ($catalogItem['item']['photo'] ?? null ? \Illuminate\Support\Facades\Storage::url($catalogItem['item']['photo']) : asset('assets/images/noimage.png')) }}" alt="">
                                <br>
                                 @php
                                    $createViewProductUrl = '#';
                                    if (isset($catalogItem['item']['slug']) && isset($catalogItem['user_id']) && isset($catalogItem['merchant_item_id'])) {
                                        $createViewProductUrl = route('front.catalog-item', [
                                            'slug' => $catalogItem['item']['slug'],
                                            'merchant_id' => $catalogItem['user_id'],
                                            'merchant_item_id' => $catalogItem['merchant_item_id']
                                        ]);
                                    } elseif (isset($catalogItem['item']['slug'])) {
                                        $createViewProductUrl = route('front.catalog-item.legacy', $catalogItem['item']['slug']);
                                    }
                                 @endphp
                                <a target="_blank" href="{{ $createViewProductUrl }}">{{ getLocalizedCatalogItemName($catalogItem['item'], 30) }}</a>
                              </td>
                              <td class="catalogItem-price">
                                 <span>{{ App\Models\CatalogItem::convertPrice($catalogItem['item_price']) }}
                                 </span>
                              </td>
                              <td>
                                 @if($catalogItem['size'])
                                 <p>
                                    <strong>{{ __('Size') }} :</strong> {{str_replace('-',' ',$catalogItem['size'])}}
                                 </p>
                                 @endif
                                 @if($catalogItem['color'])
                                 <p>
                                    <strong>{{ __('color') }} :</strong> <span
                                       style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; border-radius: 50%; background: #{{$catalogItem['color']}};"></span>
                                 </p>
                                 @endif
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
                              <td class="catalogItem-subtotal">
                                 <p class="d-inline-block"
                                    id="prc{{$catalogItem['item']['id'].$catalogItem['size'].$catalogItem['color'].str_replace(str_split(' ,'),'',$catalogItem['values'])}}">
                                    {{ App\Models\CatalogItem::convertPrice($catalogItem['price']) }}
                                 </p>
                                 @if ($catalogItem['discount'] != 0)
                                 <strong>{{$catalogItem['discount']}} %{{__('off')}}</strong>
                                 @endif
                              </td>
                           </tr>
                           @endforeach
                        </tbody>
                     </table>
                </div>
            </div>
        </div>
          <div class="col-lg-8 my-5">
             <div class="special-box">
                <div class="heading-area">
                   <h4 class="title">
                      {{ __('Customer Details') }} 
                   </h4>
                </div>
                <div class="table-responsive-sm">
                   <table class="table">
                      <tbody>
                         <tr>
                            <th width="45%">{{ __('Name') }}</th>
                            <th width="10%">:</th>
                            <td width="45%">{{$address['customer_name']}}</td>
                         </tr>
                         <tr>
                            <th width="45%">{{ __('Email') }}</th>
                            <th width="10%">:</th>
                            <td width="45%">{{$address['customer_email']}}</td>
                         </tr>
                         <tr>
                            <th width="45%">{{ __('Phone') }}</th>
                            <th width="10%">:</th>
                            <td width="45%">{{$address['customer_phone']}}</td>
                         </tr>
                         <tr>
                            <th width="45%">{{ __('Address') }}</th>
                            <th width="10%">:</th>
                            <td width="45%">{{$address['customer_address']}}</td>
                         </tr>
                         <tr>
                            <th width="45%">{{ __('Country') }}</th>
                            <th width="10%">:</th>
                            <td width="45%">{{$address['customer_country'] ? $address['customer_country'] : '--'}}</td>
                         </tr>
                         @if(@$address['customer_city'] != null)
                         <tr>
                            <th width="45%">{{ __('State') }}</th>
                            <th width="10%">:</th>
                            <td width="45%">{{$address['customer_state'] ? $address['customer_state'] : '--'}}</td>
                         </tr>
                         @endif
                         <tr>
                            <th width="45%">{{ __('City') }}</th>
                            <th width="10%">:</th>
                            <td width="45%">{{$address['customer_city'] ? $address['customer_city'] : '--'}}</td>
                         </tr>
                         <tr>
                            <th width="45%">{{ __('Postal Code') }}</th>
                            <th width="10%">:</th>
                            <td width="45%">{{$address['customer_zip'] ? $address['customer_zip'] : '--'}}</td>
                         </tr>
                      </tbody>
                   </table>
                </div>
             </div>
          </div>
          <div class="col-lg-4 my-5 ">
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
                            <th width="45%">{{ __('Total CatalogItems') }}</th>
                            <th width="10%">:</th>
                            <td width="45%">{{count($cart->items)}}</td>
                         </tr>
                         <tr>
                            <th width="45%">{{ __('Total Quintity') }}</th>
                            <th width="10%">:</th>
                            <td width="45%">{{$cart->totalQty}}</td>
                         </tr>
                         <tr>
                            <th width="45%">{{ __('Total Amount') }}</th>
                            <th width="10%">:</th>
                            <td width="45%">{{App\Models\CatalogItem::convertPrice($cart->totalPrice)}}</td>
                         </tr>
                         <tr>
                            <td>
                                <a href="{{route('operator-purchase-create-submit')}}" class="btn btn-primary">Purchase Submit</a>
                            </td>
                         </tr>
                      </tbody>
                   </table>
                </div>
             </div>
          </div>
       </div>
    </div>
 </div>

@endsection