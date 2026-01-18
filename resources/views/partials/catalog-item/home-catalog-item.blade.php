@php
    // Use eager-loaded accessor (avoids N+1 query)
    $homeProdMerchant = $cartItem->best_merchant_item;

    $homeProdUrl = $homeProdMerchant && $cartItem->slug
        ? route('front.catalog-item', ['slug' => $cartItem->slug, 'merchant_item_id' => $homeProdMerchant->id])
        : ($cartItem->slug ? route('front.catalog-item.legacy', $cartItem->slug) : '#');
@endphp

<div class="catalogItem type-catalogItem">
    <div class="catalogItem-wrapper">
       <div class="catalog-item-image">

          <a href="{{ $homeProdUrl }}" class="woocommerce-LoopProduct-link"><img src="{{ filter_var($cartItem->photo, FILTER_VALIDATE_URL) ? $cartItem->photo : ($cartItem->photo ? \Illuminate\Support\Facades\Storage::url($cartItem->photo) : asset('assets/images/noimage.png')) }}" alt="CatalogItem Image"></a>
          @if(!empty($cartItem->features))
          <div class="catalogItem-variations">
             @foreach($cartItem->features as $key => $data1)
             <span class="active sale"><a href="#" style="background-color: {{ $cartItem->colors[$key] }}">{{ $cartItem->features[$key] }}</a></span>
             @endforeach
          </div>
          @endif
          
          @if ($cartItem->offPercentage() && round($cartItem->offPercentage())>0)
          <div class="on-sale">- {{ round($cartItem->offPercentage() )}}%</div>
          @endif

          <div class="hover-area">
            {{-- item_type and affiliate_link are now on merchant_items --}}
            @if($homeProdMerchant && $homeProdMerchant->item_type == "affiliate" && $homeProdMerchant->affiliate_link)
            <div class="cart-button">
               <a href="javascript:;" data-href="{{ $homeProdMerchant->affiliate_link }}" class="button add_to_cart_button affilate-btn" data-bs-toggle="tooltip" data-bs-placement="right" name="" data-bs-original-name="{{ __('Add To Cart') }}" aria-label="{{ __('Add To Cart') }}"></a>
            </div>
            @else
            @if(!$homeProdMerchant || $cartItem->emptyStock())
            <div class="closed">
               <a class="cart-out-of-stock button add_to_cart_button" href="#" name="{{ __('Out Of Stock') }}"><i class="flaticon-cancel flat-mini mx-auto"></i></a>
            </div>
            @else
               <div class="cart-button">
                  <a href="javascript:;"
                     class="m-cart-add button add_to_cart_button"
                     data-merchant-item-id="{{ $homeProdMerchant->id }}"
                     data-catalog-item-id="{{ $cartItem->id }}"
                     data-bs-placement="right"
                     name="Add To Cart"
                     data-bs-original-name="{{ __('Add To Cart') }}"
                     aria-label="{{ __('Add To Cart') }}"></a>
               </div>
            @endif
            @endif
            @if(Auth::check())
            <div class="favorite-button">
               <a class="add_to_favorite  new button add_to_cart_button" id="add-to-wish" href="javascript:;" data-href="{{ route('user-favorite-add',$cartItem->id) }}" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-original-name="Add to Favorites" name="{{ __('Favorites') }}" aria-label="Add to Favorites">{{ __('Favorites') }}</a>
            </div>
            @else
            <div class="favorite-button">
               <a class="add_to_favorite button add_to_cart_button" href="{{ route('user.login') }}" data-bs-toggle="tooltip" data-bs-placement="right" name="{{ __('Favorites') }}" data-bs-original-name="{{ __('Favorites') }}" aria-label="{{ __('Favorites') }}">{{ __('Favorites') }}</a>
            </div>
            @endif

               <div class="compare-button">
                  <a class="compare button add_to_cart_button" data-href="{{ route('catalog-item.compare.add',$cartItem->id) }}" href="javascrit:;" data-bs-toggle="tooltip" data-bs-placement="right" name="{{__('Compare')}}" data-bs-original-name="{{__('Compare')}}" aria-label="{{__('Compare')}}">{{ __('Compare') }}</a>
               </div>
         </div>
       </div>
       <div class="catalogItem-info">
          <h3 class="catalogItem-name"><a href="{{ $homeProdUrl }}">{{ $cartItem->showName() }}</a></h3>
          <div class="catalogItem-price">
             <div class="price">
                <ins>{{ $cartItem->showPrice() }} </ins>
                <del>{{ $cartItem->showPreviousPrice() }}</del>
             </div>
          </div>
          <div class="shipping-feed-back">
             <div class="star-rating">
                <div class="rating-wrap">
                   <p><i class="fas fa-star"></i><span> {{ number_format($cartItem->catalog_reviews_avg_rating ?? 0, 1) }} ({{ $cartItem->catalog_reviews_count ?? 0 }})</span></p>
                </div>
             </div>
          </div>
          {{-- Shipping Quote Button --}}
          @if($homeProdMerchant)
              <x-shipping-quote-button
                  :merchant-user-id="$homeProdMerchant->user_id"
                  :catalog-item-name="$cartItem->showName()"
                  class="mt-2"
              />
          @endif
       </div>
    </div>
 </div>
