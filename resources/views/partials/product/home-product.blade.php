@php
  // Check if $prod is a MerchantProduct or Product model
  $isMerchantProduct = $prod instanceof \App\Models\MerchantProduct;

  if ($isMerchantProduct) {
      // $prod is MerchantProduct - use it directly
      $merchantProductId = $prod->id;
      $vendorId = $prod->user_id;
      $productSlug = $prod->product->slug ?? $prod->slug;
  } else {
      // $prod is Product - get first active merchant product
      $mp = $prod->merchantProducts()->where('status', 1)->orderBy('price')->first();
      $merchantProductId = $mp->id ?? null;
      $vendorId = $mp->user_id ?? null;
      $productSlug = $prod->slug;
  }

  $productUrl = ($merchantProductId && $vendorId)
      ? route('front.product', ['slug' => $productSlug, 'vendor_id' => $vendorId, 'merchant_product_id' => $merchantProductId])
      : 'javascript:;';
@endphp

<div class="product type-product">
  <div class="product-wrapper">
    <div class="product-image">

      <a href="{{ $productUrl }}" class="woocommerce-LoopProduct-link">
        <img src="{{ $prod->thumbnail ? asset('assets/images/thumbnails/'.$prod->thumbnail) : asset('assets/images/noimage.png') }}" alt="Product Image">
      </a>

      <div class="hover-area">
        @if($prod->product_type == "affiliate")
          <div class="cart-button">
            <a href="javascript:;" data-href="{{ $prod->affiliate_link }}" class="button add_to_cart_button affilate-btn"
               data-bs-toggle="tooltip" data-bs-placement="right" aria-label="{{ __('Add To Cart') }}"></a>
          </div>
        @else
          @if($prod->emptyStock())
            <div class="cart-button">
              <a class="cart-out-of-stock button add_to_cart_button" href="#" title="{{ __('Out Of Stock') }}">
                <i class="flaticon-cancel flat-mini mx-auto"></i>
              </a>
            </div>
          @else
            @if ($prod->type != 'Listing')
              <div class="cart-button">
                <a href="javascript:;" data-bs-toggle="modal"
                   data-cross-href="{{ route('front.show.cross.product', $prod->id) }}"
                   {{ $prod->cross_products ? 'data-bs-target=#exampleModal' : '' }}
                   data-href="{{ route('product.cart.add', ['product' => $prod->id, 'user' => $prod->user_id]) }}"
                   class="add-cart button add_to_cart_button {{ $prod->cross_products ? 'view_cross_product' : '' }}"
                   data-bs-placement="right" title="{{ __('Add To Cart') }}" aria-label="{{ __('Add To Cart') }}"></a>
              </div>
            @endif
          @endif
        @endif

        @if(Auth::check())
          <div class="wishlist-button">
            <a class="add_to_wishlist new button add_to_cart_button" id="add-to-wish" href="javascript:;"
               data-href="{{ route('user-wishlist-add', ['id' => $prod->id, 'user' => $prod->user_id]) }}"
               data-bs-toggle="tooltip" data-bs-placement="right" aria-label="{{ __('Wishlist') }}">{{ __('Wishlist') }}</a>
          </div>
        @else
          <div class="wishlist-button">
            <a class="add_to_wishlist button add_to_cart_button" href="{{ route('user.login') }}"
               data-bs-toggle="tooltip" data-bs-placement="right" aria-label="{{ __('Wishlist') }}">{{ __('Wishlist') }}</a>
          </div>
        @endif

        @if ($prod->type != 'Listing')
          <div class="compare-button">
            <a class="compare button add_to_cart_button" href="javascrit:;"
               data-href="{{ route('product.compare.add', ['id' => $prod->id, 'user' => $prod->user_id]) }}"
               data-bs-toggle="tooltip" data-bs-placement="right" aria-label="{{__('Compare')}}">{{ __('Compare') }}</a>
          </div>
        @endif
      </div>
    </div>

    <div class="product-info">
      <h3 class="product-title">
        <a href="{{ $productUrl }}"><x-product-name :product="$prod" :vendor-id="$vendorId" :merchant-product-id="$merchantProductId" target="_self" /></a>
      </h3>
      <div class="product-price">
        <div class="price">
          <ins>{{ $prod->showPrice() }}</ins>
          <del>{{ $prod->showPreviousPrice() }}</del>
        </div>
      </div>
      <div class="shipping-feed-back">
        <div class="star-rating">
          <div class="rating-wrap">
            <p><i class="fas fa-star"></i><span> {{ number_format($prod->ratings_avg_rating,1) }} ({{ $prod->ratings_count }})</span></p>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
