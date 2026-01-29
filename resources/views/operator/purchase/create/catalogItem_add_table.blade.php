{{-- Data pre-computed in PurchaseCreateController (DATA_FLOW_POLICY) --}}
@if (!empty($adminCart))

<div class="mr-table allproduct">

  @include('alerts.operator.form-success') 
  <div class="table-responsive">
     <table id="example2" class="table table-hover dt-responsive" cellspacing="0" width="100%">
        <thead>
           <tr>
           <tr>
              <th>{{ __('CatalogItem ID#') }}</th>
              <th>{{ __('CatalogItem Name') }}</th>
              <th>{{ __('Price') }}</th>
              <th>{{ __('Details') }}</th>
              <th>{{ __('Subtotal') }}</th>
              <th>{{ __('Action') }}</th>
           </tr>
           </tr>
        </thead>
        <tbody>
           @foreach($adminCart->items as $key1 => $catalogItem)

           <tr>
              <td><input type="hidden" value="{{$key1}}">{{ $catalogItem['item']['id'] }}</td>
              <td>
                <img src="{{ filter_var($catalogItem['item']['photo'] ?? '', FILTER_VALIDATE_URL) ? $catalogItem['item']['photo'] : ($catalogItem['item']['photo'] ?? null ? \Illuminate\Support\Facades\Storage::url($catalogItem['item']['photo']) : asset('assets/images/noimage.png')) }}" alt="">
                <br>
                {{-- URL pre-computed in controller (DATA_FLOW_POLICY) --}}
                <a target="_blank" href="{{ $catalogItem['computed_url'] ?? '#' }}">{{ getLocalizedCatalogItemName($catalogItem['item'], 30) }}</a>
              </td>
              <td class="catalogItem-price">
                 <span>{{ formatPrice($catalogItem['price'] ?? 0) }}
                 </span>
              </td>
              <td>
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
              <td class="catalogItem-subtotal">
                 <p class="d-inline-block"
                    id="prc{{$catalogItem['item']['id'].str_replace(str_split(' ,'),'',$catalogItem['values'] ?? '')}}">
                    {{ formatPrice($catalogItem['price'] ?? 0) }}
                 </p>
                 @if (($catalogItem['discount'] ?? 0) != 0)
                 <strong>{{$catalogItem['discount']}} %{{__('off')}}</strong>
                 @endif
              </td>
              <td>
                 <a href="javascript:;"  data-href="{{ route('operator.purchase.remove.cart',$catalogItem['item']['id'].str_replace(str_split(' ,'),'',$catalogItem['values'] ?? '')) }}" class="btn btn-primary removeOrder"><i class="fa fa-trash"></i> {{ __('Remove') }}</a>
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



