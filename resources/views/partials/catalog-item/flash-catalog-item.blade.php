@php
    // Use eager-loaded accessor (avoids N+1 query)
    $flashProdMerchant = $prod->best_merchant_item;

    $flashProdUrl = $flashProdMerchant && $prod->slug
        ? route('front.catalog-item', ['slug' => $prod->slug, 'merchant_id' => $flashProdMerchant->user_id, 'merchant_item_id' => $flashProdMerchant->id])
        : ($prod->slug ? route('front.catalog-item.legacy', $prod->slug) : '#');
@endphp

<a href="{{ $flashProdUrl }}" class="single-product-flas">
    <div class="img">
       <img src="{{ filter_var($prod->photo, FILTER_VALIDATE_URL) ? $prod->photo : ($prod->photo ? \Illuminate\Support\Facades\Storage::url($prod->photo) : asset('assets/images/noimage.png')) }}" alt="">
       @if(!empty($prod->features))
       <div class="sell-area">
          @foreach($prod->features as $key => $data1)
          <span class="sale" style="background-color:{{ $prod->colors[$key] }}">
          {{ $prod->features[$key] }}
          </span>
          @endforeach
       </div>
       @endif
    </div>
    <div class="content">
       <h4 class="name">
          {{ $prod->showName() }}
       </h4>

       
       <ul class="stars d-flex">
          <div class="review-stars">
             <div class="empty-stars"></div>
             <div class="full-stars" style="width:{{ number_format($prod->catalog_reviews_avg_rating,1) }}%"></div>
          </div>
          <li class="ml-2">
             <span>({{ $prod->catalog_reviews_count }})</span>
          </li>
       </ul>
       <div class="price">
          <span class="new-price">{{ $prod->showPrice() }}</span>
          <small class="old-price"><del>{{ $prod->showPreviousPrice() }}</del></small>
       </div>
       <ul class="action-meta">
          {{-- FAVORITES SECTION --}}
          @if(Auth::check())
          <li>
             <span class="wish add-to-wish" data-href="{{ route('user-favorite-add',$prod->id) }}" data-bs-toggle="tooltip" data-placement="top" title="{{ __('Favorites') }}">
             <i class="far fa-heart"></i>
             </span>
          </li>
          @else
          <li>
             <span rel-toggle="tooltip" title="{{ __('Favorites') }}" data-placement="top" class="wish add-to-wish" data-bs-toggle="modal" data-bs-target="#user-login">
             <i class="far fa-heart"></i>
             </span>
          </li>
          @endif
          {{-- FAVORITES SECTION ENDS --}}
          {{-- ADD TO CART SECTION --}}
          {{-- product_type and affiliate_link are now on merchant_products --}}
          @if($flashProdMerchant && $flashProdMerchant->product_type == "affiliate" && $flashProdMerchant->affiliate_link)
          <li>
             <span class="cart-btn affilate-btn" data-href="{{ $flashProdMerchant->affiliate_link }}" data-bs-toggle="tooltip" data-placement="top" title="{{ __('Buy Now') }}">
             <i class="icofont-cart"></i>
             </span>
          </li>
          @else
          @if($prod->emptyStock())
          <li>
             <span class="cart-btn cart-out-of-stock" data-bs-toggle="tooltip" data-placement="top" title="{{ __('Out Of Stock') }}">
             <i class="icofont-close-circled"></i>
             </span>
          </li>
          @else
          @if ($prod->type != 'Listing')
          <li>
             <span  class="cart-btn add-to-cart add-to-cart-btn" data-href="{{ route('product.cart.add',$prod->id) }}"  title="{{ __('Add To Cart') }}">
             <i class="icofont-cart"></i>
             </span>
          </li>
          <li>
             <span class="cart-btn quick-view" data-href="{{ route('product.quick',$prod->id) }}" rel-toggle="tooltip" data-placement="top" title="{{ __('Quick View') }}" data-bs-toggle="modal" data-bs-target="#quickview">
             <i class="fas fa-eye"></i>
             </span>
          </li>
          @endif
          @endif
          @endif
          {{-- ADD TO CART SECTION ENDS --}}
          {{-- ADD TO COMPARE SECTION --}}
          <li>
             <span class="compear add-to-compare" data-href="{{ route('catalog-item.compare.add',$prod->id) }}" data-bs-toggle="tooltip" data-placement="top" title="{{ __('Compare') }}">
             <i class="fas fa-random"></i>
             </span>
          </li>
          {{-- ADD TO COMPARE SECTION ENDS --}}
       </ul>
       {{-- discount_date is on merchant_products, not products --}}
       @if($flashProdMerchant && $flashProdMerchant->discount_date)
       <div class="deal-counter">
          <div data-countdown="{{ $flashProdMerchant->discount_date }}"></div>
       </div>
       @endif
    </div>
 </a>
