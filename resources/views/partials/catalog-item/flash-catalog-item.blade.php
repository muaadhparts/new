@php
    // Use eager-loaded accessor (avoids N+1 query)
    $flashProdMerchant = $cartItem->best_merchant_item;

    $flashProdUrl = $flashProdMerchant && $cartItem->slug
        ? route('front.catalog-item', ['slug' => $cartItem->slug, 'merchant_item_id' => $flashProdMerchant->id])
        : '#';
@endphp

<a href="{{ $flashProdUrl }}" class="single-catalogItem-flas">
    <div class="img">
       <img src="{{ filter_var($cartItem->photo, FILTER_VALIDATE_URL) ? $cartItem->photo : ($cartItem->photo ? \Illuminate\Support\Facades\Storage::url($cartItem->photo) : asset('assets/images/noimage.png')) }}" alt="">
       @if(!empty($cartItem->features))
       <div class="sell-area">
          @foreach($cartItem->features as $key => $data1)
          <span class="sale" style="background-color:{{ $cartItem->colors[$key] }}">
          {{ $cartItem->features[$key] }}
          </span>
          @endforeach
       </div>
       @endif
    </div>
    <div class="content">
       <h4 class="name">
          {{ $cartItem->showName() }}
       </h4>

       
       <ul class="stars d-flex">
          <div class="review-stars">
             <div class="empty-stars"></div>
             <div class="full-stars" style="width:{{ number_format($cartItem->catalog_reviews_avg_rating,1) }}%"></div>
          </div>
          <li class="ml-2">
             <span>({{ $cartItem->catalog_reviews_count }})</span>
          </li>
       </ul>
       <div class="price">
          <span class="new-price">{{ $cartItem->showPrice() }}</span>
          <small class="old-price"><del>{{ $cartItem->showPreviousPrice() }}</del></small>
       </div>
       <ul class="action-meta">
          {{-- FAVORITES SECTION --}}
          @if(Auth::check())
          <li>
             <span class="wish add-to-wish" data-href="{{ $flashProdMerchant ? route('user-favorite-add-merchant', $flashProdMerchant->id) : '#' }}" data-bs-toggle="tooltip" data-placement="top" name="{{ __('Favorites') }}">
             <i class="far fa-heart"></i>
             </span>
          </li>
          @else
          <li>
             <span rel-toggle="tooltip" name="{{ __('Favorites') }}" data-placement="top" class="wish add-to-wish" data-bs-toggle="modal" data-bs-target="#user-login">
             <i class="far fa-heart"></i>
             </span>
          </li>
          @endif
          {{-- FAVORITES SECTION ENDS --}}
          {{-- ADD TO CART SECTION --}}
          {{-- item_type and affiliate_link are now on merchant_items --}}
          @if($flashProdMerchant && $flashProdMerchant->item_type == "affiliate" && $flashProdMerchant->affiliate_link)
          <li>
             <span class="cart-btn affilate-btn" data-href="{{ $flashProdMerchant->affiliate_link }}" data-bs-toggle="tooltip" data-placement="top" name="{{ __('Buy Now') }}">
             <i class="icofont-cart"></i>
             </span>
          </li>
          @else
          @if(!$flashProdMerchant || $cartItem->emptyStock())
          <li>
             <span class="cart-btn cart-out-of-stock" data-bs-toggle="tooltip" data-placement="top" name="{{ __('Out Of Stock') }}">
             <i class="icofont-close-circled"></i>
             </span>
          </li>
          @else
          <li>
             <span class="cart-btn m-cart-add"
                   data-merchant-item-id="{{ $flashProdMerchant->id }}"
                   data-catalog-item-id="{{ $cartItem->id }}"
                   name="{{ __('Add To Cart') }}">
             <i class="icofont-cart"></i>
             </span>
          </li>
          <li>
             <span class="cart-btn quick-view"
                   data-url="{{ route('modal.quickview', ['id' => $cartItem->id]) }}?user={{ $flashProdMerchant->user_id }}"
                   data-id="{{ $cartItem->id }}"
                   rel-toggle="tooltip"
                   data-placement="top"
                   name="{{ __('Quick View') }}"
                   data-bs-toggle="modal"
                   data-bs-target="#quickview">
             <i class="fas fa-eye"></i>
             </span>
          </li>
          @endif
          @endif
          {{-- ADD TO CART SECTION ENDS --}}
          {{-- ADD TO COMPARE SECTION --}}
          <li>
             <span class="compear add-to-compare" data-href="{{ $flashProdMerchant ? route('merchant.compare.add', $flashProdMerchant->id) : '#' }}" data-bs-toggle="tooltip" data-placement="top" name="{{ __('Compare') }}">
             <i class="fas fa-random"></i>
             </span>
          </li>
          {{-- ADD TO COMPARE SECTION ENDS --}}
       </ul>
       {{-- discount_date is on merchant_items, not catalogItems --}}
       @if($flashProdMerchant && $flashProdMerchant->discount_date)
       <div class="deal-counter">
          <div data-countdown="{{ $flashProdMerchant->discount_date }}"></div>
       </div>
       @endif
    </div>
 </a>
