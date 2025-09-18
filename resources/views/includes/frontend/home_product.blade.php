@php
  $mp = $product->merchantProducts()->where('status',1)->orderBy('price')->first();
  $vendorId = optional($mp)->user_id ?? 0;
@endphp

<div class="{{ isset($class) ? $class : 'col-md-6 col-lg-4 col-xl-3' }}">
  <div class="single-product">
    <div class="img-wrapper">

      {{-- Badge الخصم --}}
      @if ($product->offPercentage() && round($product->offPercentage()) > 0)
        <span class="product-badge">-{{ round($product->offPercentage()) }}%</span>
      @endif

      {{-- Wishlist --}}
      @if (Auth::check())
        @if (isset($wishlist))
          <a href="javascript:;" class="removewishlist"
             data-href="{{ route('user-wishlist-remove', App\Models\Wishlist::where('user_id',$user->id)->where('product_id',$product->id)->first()->id) }}">
            <div class="add-to-wishlist-btn bg-danger">
              <i class="fas fa-trash text-white"></i>
            </div>
          </a>
        @else
          <a href="javascript:;" class="wishlist"
             data-href="{{ route('user-wishlist-add', ['id'=>$product->id,'user'=>$vendorId]) }}">
            <div class="add-to-wishlist-btn {{ wishlistCheck($product->id) ? 'active' : '' }}">
              {{-- أيقونة --}}
            </div>
          </a>
        @endif
      @else
        <a href="{{ route('user.login') }}">
          <div class="add-to-wishlist-btn">…</div>
        </a>
      @endif

      {{-- صورة --}}
      <a class="test-popup-link" href="{{ \Illuminate\Support\Facades\Storage::url($product->photo) ?? asset('assets/images/noimage.png') }}">
        <img class="product-img"
             src="{{ \Illuminate\Support\Facades\Storage::url($product->photo) ?? asset('assets/images/noimage.png') }}"
             alt="product img">
      </a>

      {{-- أزرار --}}
      <div class="add-to-cart">
        @if ($product->type != 'Listing')
          {{-- Compare --}}
          <a class="compare_product" href="javascrit:;"
             data-href="{{ route('product.compare.add', ['id'=>$product->id,'user'=>$vendorId]) }}">
            <div class="compare">…</div>
          </a>
        @endif

        {{-- Affiliate / Add to Cart --}}
        @if ($product->product_type == 'affiliate')
          <a href="{{ $product->affiliate_link }}" class="add_to_cart_button">
            <div class="add-cart">@lang('Add To Cart')</div>
          </a>
        @else
          @if ($product->emptyStock())
            <div class="add-cart">{{ __('Out of Stock') }}</div>
          @else
            @if ($product->type != 'Listing')
              <a href="javascript:;"
                 {{ $product->cross_products ? 'data-bs-target=#exampleModal' : '' }}
                 data-href="{{ route('product.cart.add', ['product'=>$product->id,'user'=>$vendorId]) }}"
                 data-cross-href="{{ route('front.show.cross.product', $product->id) }}"
                 class="add_cart_click {{ $product->cross_products ? 'view_cross_product' : '' }}">
                <div class="add-cart">@lang('Add To Cart')</div>
              </a>
            @endif
          @endif
        @endif

        {{-- Details --}}
        @if ($product->type != 'Listing')
          <a href="{{ route('front.product', ['slug' => $product->slug, 'user' => $vendorId]) }}">
            <div class="details">…</div>
            <div class="details">…</div>
          </a>
        @endif
      </div>
    </div>

    <div class="content-wrapper">
      <a href="{{ route('front.product', ['slug' => $product->slug, 'user' => $vendorId]) }}">
        <h6 class="product-title">{{ $product->showName() }}</h6>
      </a>
      <p><span>@lang('Product SKU :')</span> <span>{{ $product->sku }}</span></p>
      <div class="price-wrapper">
        <h6>{{ $mp ? $mp->showPrice() : $product->showPrice() }}</h6>
        <h6><del>{{ $mp && $mp->previous_price ? \App\Models\Product::convertPrice($mp->previous_price) : $product->showPreviousPrice() }}</del></h6>
      </div>
      {{-- تقييمات … --}}
    </div>
  </div>
</div>
