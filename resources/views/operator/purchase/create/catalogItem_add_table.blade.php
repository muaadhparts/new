
@if (Session::has('admin_cart'))
    @php
        $cart = Session::get('admin_cart');
    @endphp

<div class="mr-table allproduct">

  @include('alerts.operator.form-success') 
  <div class="table-responsive">
     <table id="example2" class="table table-hover dt-responsive" cellspacing="0" width="100%">
        <thead>
           <tr>
           <tr>
              <th>{{ __('CatalogItem ID#') }}</th>
              <th>{{ __('CatalogItem Title') }}</th>
              <th>{{ __('Price') }}</th>
              <th>{{ __('Details') }}</th>
              <th>{{ __('Subtotal') }}</th>
              <th>{{ __('Action') }}</th>
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
                 <input type="hidden" value="{{ $catalogItem['license'] }}">
                 @php
                    $prodAddTableUrl = '#';
                    if (isset($catalogItem['item']['slug']) && isset($catalogItem['user_id']) && isset($catalogItem['merchant_item_id'])) {
                        $prodAddTableUrl = route('front.catalog-item', [
                            'slug' => $catalogItem['item']['slug'],
                            'merchant_id' => $catalogItem['user_id'],
                            'merchant_item_id' => $catalogItem['merchant_item_id']
                        ]);
                    } elseif (isset($catalogItem['item']['slug'])) {
                        $prodAddTableUrl = route('front.catalog-item.legacy', $catalogItem['item']['slug']);
                    }
                 @endphp
                <a target="_blank" href="{{ $prodAddTableUrl }}">{{ getLocalizedCatalogItemName($catalogItem['item'], 30) }}</a>
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
              <td>
                 <a href="javascript:;"  data-href="{{ route('operator.purchase.remove.cart',$catalogItem['item']['id'].$catalogItem['size'].$catalogItem['color'].str_replace(str_split(' ,'),'',$catalogItem['values'])) }}" class="btn btn-primary removeOrder"><i class="fa fa-trash"></i> {{ __('Remove') }}</a>
              </td>
           </tr>
           @endforeach
        </tbody>
     </table>
  </div>
  <div class="row">
   
    
    <div class="col-lg-12 text-right">
      <button type="submit" class="btn btn-primary">View & Continue</button>
    </div>

  </div>
</div>


@endif



