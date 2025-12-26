@php
    // Use eager-loaded accessor (avoids N+1 query)
    $homeProdMerchant = $prod->best_merchant_product;

    $homeProdUrl = $homeProdMerchant && $prod->slug
        ? route('front.product', ['slug' => $prod->slug, 'vendor_id' => $homeProdMerchant->user_id, 'merchant_product_id' => $homeProdMerchant->id])
        : ($prod->slug ? route('front.product.legacy', $prod->slug) : '#');
@endphp

<div class="product type-product">
    <div class="product-wrapper">
       <div class="product-image">

          <a href="{{ $homeProdUrl }}" class="woocommerce-LoopProduct-link"><img src="{{ filter_var($prod->photo, FILTER_VALIDATE_URL) ? $prod->photo : ($prod->photo ? \Illuminate\Support\Facades\Storage::url($prod->photo) : asset('assets/images/noimage.png')) }}" alt="Product Image"></a>
          @if(!empty($prod->features))
          <div class="product-variations">
             @foreach($prod->features as $key => $data1)
             <span class="active sale"><a href="#" style="background-color: {{ $prod->colors[$key] }}">{{ $prod->features[$key] }}</a></span>
             @endforeach
          </div>
          @endif
          
          @if ($prod->offPercentage() && round($prod->offPercentage())>0)
          <div class="on-sale">- {{ round($prod->offPercentage() )}}%</div>
          @endif

          <div class="hover-area">
            {{-- product_type and affiliate_link are now on merchant_products --}}
            @if($homeProdMerchant && $homeProdMerchant->product_type == "affiliate" && $homeProdMerchant->affiliate_link)
            <div class="cart-button">
               <a href="javascript:;" data-href="{{ $homeProdMerchant->affiliate_link }}" class="button add_to_cart_button affilate-btn" data-bs-toggle="tooltip" data-bs-placement="right" title="" data-bs-original-title="{{ __('Add To Cart') }}" aria-label="{{ __('Add To Cart') }}"></a>
            </div>
            @else
            @if($prod->emptyStock())
            <div class="closed">
               <a class="cart-out-of-stock button add_to_cart_button"  href="#" title="{{ __('Out Of Stock') }}" ><i class="flaticon-cancel flat-mini mx-auto"></i></a>
            </div>
            @else
            @if ($prod->type != "Listing")
          
               <div class="cart-button">
                 
                  <a href="javascript:;"
                  data-bs-toggle="modal"  {{$prod->cross_products ? 'data-bs-target=#exampleModal' : ''}}  data-href="{{ route('product.cart.add',$prod->id) }}" data-cross-href="{{route('front.show.cross.product',$prod->id)}}" class="add-cart button add_to_cart_button {{$prod->cross_products ? 'view_cross_product' : ''}}"  data-bs-placement="right"  title="Add To Cart" data-bs-original-title="{{ __('Add To Cart') }}" aria-label="{{ __('Add To Cart') }}"></a>
               </div>
               
       
            @endif
            @endif
            @endif
            @if(Auth::check())
            <div class="wishlist-button">
               <a class="add_to_wishlist  new button add_to_cart_button" id="add-to-wish" href="javascript:;" data-href="{{ route('user-wishlist-add',$prod->id) }}" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-title="Add to Wishlist" title="{{ __('Wishlist') }}" aria-label="Add to Wishlist">{{ __('Wishlist') }}</a>
            </div>
            @else
            <div class="wishlist-button">
               <a class="add_to_wishlist button add_to_cart_button" href="{{ route('user.login') }}" data-bs-toggle="tooltip" data-bs-placement="right" title="{{ __('Wishlist') }}" data-bs-original-title="{{ __('Wishlist') }}" aria-label="{{ __('Wishlist') }}">{{ __('Wishlist') }}</a>
            </div>
            @endif

            @if ($prod->type != "Listing")
               <div class="compare-button">
                  <a class="compare button add_to_cart_button" data-href="{{ route('product.compare.add',$prod->id) }}" href="javascrit:;" data-bs-toggle="tooltip" data-bs-placement="right" title="{{__('Compare')}}" data-bs-original-title="{{__('Compare')}}" aria-label="{{__('Compare')}}">{{ __('Compare') }}</a>
               </div>
            @endif
         </div>
       </div>
       <div class="product-info">
          <h3 class="product-title"><a href="{{ $homeProdUrl }}">{{ $prod->showName() }}</a></h3>
          <div class="product-price">
             <div class="price">
                <ins>{{ $prod->showPrice() }} </ins>
                <del>{{ $prod->showPreviousPrice() }}</del>
             </div>
          </div>
          <div class="shipping-feed-back">
             <div class="star-rating">
                <div class="rating-wrap">
                   <p><i class="fas fa-star"></i><span> {{ number_format($prod->ratings_avg_rating ?? 0, 1) }} ({{ $prod->ratings_count ?? 0 }})</span></p>
                </div>
             </div>
          </div>
          {{-- Shipping Quote Button --}}
          @if(($prod->type ?? 'Physical') == 'Physical' && $homeProdMerchant)
              <x-shipping-quote-button
                  :vendor-id="$homeProdMerchant->user_id"
                  :product-name="$prod->showName()"
                  class="mt-2"
              />
          @endif
       </div>
    </div>
 </div>
