@php
  // Use the passed merchant product data instead of searching for it
  // $mp is now passed from the main loop
  if (!isset($mp)) {
      // Fallback to original logic if $mp is not passed (for backward compatibility)
      if (isset($product->vendor_user_id) && $product->vendor_user_id) {
          $mp = $product->merchantProducts()
                ->where('user_id', $product->vendor_user_id)
                ->where('status', 1)
                ->first();
          $vendorId = $product->vendor_user_id;
      } else {
          $mp = $product->merchantProducts()
                ->where('status', 1)
                ->whereHas('user', function ($user) {
                    $user->where('is_vendor', 2);
                })
                ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                ->orderBy('price')
                ->first();
          $vendorId = optional($mp)->user_id ?? 0;
      }
  } else {
      // Use the passed merchant product
      $vendorId = $mp->user_id;
  }

  $hasVendor  = $vendorId > 0;

  // Use new merchant-product-based route with slug and vendor for SEO
  // If no MP found, try to fetch one as fallback
  if (!isset($mp) && $hasVendor) {
      $mp = $product->merchantProducts()->where('user_id', $vendorId)->where('status', 1)->first();
  }
  $detailsUrl = isset($mp) ? route('front.product', ['slug' => $product->slug, 'vendor_id' => $mp->user_id, 'merchant_product_id' => $mp->id]) : 'javascript:;';

  // نسبة الخصم المعرَضة للمستخدم (من عرض البائع إن وجد وإلا من المنتج)
  $offPercent = null;
  if ($mp && $mp->previous_price && $mp->previous_price > 0) {
      $offPercent = round((1 - ($mp->price / $mp->previous_price)) * 100);
  } elseif (isset($product->previous_price) && isset($product->price) && $product->previous_price > $product->price) {
      // Use effective pricing if already set on the product (from wishlist)
      $offPercent = round((1 - ($product->price / $product->previous_price)) * 100);
  } elseif (method_exists($product, 'offPercentage')) {
      $offPercent = (int) round($product->offPercentage());
  }

  // حالة التوفر حسب عرض البائع
  if ($mp) {
      $inStock = is_null($mp->stock) || (int)$mp->stock > 0;
  } elseif (isset($product->stock)) {
      // Use effective stock if already set on the product
      $inStock = is_null($product->stock) || (int)$product->stock > 0;
  } else {
      $inStock = !$product->emptyStock();
  }
@endphp

<div class="{{ isset($class) ? $class : 'col-md-6 col-lg-4 col-xl-3' }}">
  <div class="single-product">
    <div class="img-wrapper">

      {{-- Badge الخصم (من عرض البائع إن وُجد) --}}
      @if (!is_null($offPercent) && $offPercent > 0)
        <span class="product-badge">-{{ $offPercent }}%</span>
      @endif

      {{-- Brand Quality Badge --}}
      @if ($mp && $mp->qualityBrand)
        <span class="brand-quality-badge" style="position: absolute; top: 40px; left: 10px; background: #007bff; color: white; padding: 2px 6px; border-radius: 3px; font-size: 10px; z-index: 10;">
          @if ($mp->qualityBrand->logo_url)
            <img src="{{ $mp->qualityBrand->logo_url }}" alt="{{ $mp->qualityBrand->display_name }}" style="width: 16px; height: 16px; margin-right: 2px;">
          @endif
          {{ $mp->qualityBrand->display_name }}
        </span>
      @endif

      {{-- Wishlist --}}
      @if (Auth::check())
        @if (isset($wishlist))
          <a href="javascript:;" class="removewishlist"
             data-href="{{ route('user-wishlist-remove', isset($product->wishlist_item_id) ? $product->wishlist_item_id : App\Models\Wishlist::where('user_id', Auth::id())->where('product_id',$product->id)->first()->id ?? 0) }}">
            <div class="add-to-wishlist-btn bg-danger">
              <i class="fas fa-trash text-white"></i>
            </div>
          </a>
        @else
          <a href="javascript:;" class="wishlist"
             data-href="{{ isset($mp) ? route('merchant.wishlist.add', $mp->id) : 'javascript:;' }}">
            <div class="add-to-wishlist-btn {{ isset($mp) ? (merchantWishlistCheck($mp->id) ? 'active' : '') : (wishlistCheck($product->id) ? 'active' : '') }}">
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
          <a class="compare_product" href="javascript:;"
             data-href="{{ isset($mp) ? route('merchant.compare.add', $mp->id) : 'javascript:;' }}">
            <div class="compare {{ isset($mp) ? (merchantCompareCheck($mp->id) ? 'active' : '') : '' }}">…</div>
          </a>
        @endif

        {{-- Affiliate / Add to Cart --}}
        @if ($product->product_type == 'affiliate')
          <a href="{{ $product->affiliate_link }}" class="add_to_cart_button">
            <div class="add-cart">@lang('Add To Cart')</div>
          </a>
        @else
          @if (!$hasVendor || !$inStock)
            <div class="add-cart">{{ __('Out of Stock') }}</div>
          @else
            @if ($product->type != 'Listing')
              <a href="javascript:;"
                 {{ $product->cross_products ? 'data-bs-target=#exampleModal' : '' }}
                 data-href="{{ isset($mp) ? route('merchant.cart.add', $mp->id) : 'javascript:;' }}"
                 data-cross-href="{{ route('front.show.cross.product', $product->id) }}"
                 data-merchant-product="{{ $mp->id ?? '' }}"
                 data-product="{{ $product->id }}"
                 class="add_cart_click {{ $product->cross_products ? 'view_cross_product' : '' }}">
                <div class="add-cart">@lang('Add To Cart')</div>
              </a>
            @endif
          @endif
        @endif

        {{-- Details --}}
        @if ($product->type != 'Listing')
          <a href="{{ $detailsUrl }}">
            <div class="details">…</div>
            <div class="details">…</div>
          </a>
        @endif
      </div>
    </div>

    <div class="content-wrapper">
      <a href="{{ $detailsUrl }}">
        <h6 class="product-title"><x-product-name :product="$product" :vendor-id="$vendorId" target="_self" /></h6>
      </a>

      {{-- Merchant Store Information --}}
      @if($mp && $mp->user)
        <p class="merchant-info text-muted">
          <small>@lang('Sold by:') {{ getMerchantDisplayName($mp) }}</small>
        </p>
      @endif

      <p><span>@lang('Product SKU :')</span> <span>{{ $product->sku }}</span></p>
      <div class="price-wrapper">
        {{-- السعر الحالي --}}
        <h6>
          @if ($mp && method_exists($mp, 'showPrice'))
            {{ $mp->showPrice() }}
          @elseif ($mp && isset($mp->price))
            {{ \App\Helpers\PriceHelper::showCurrencyPrice(\App\Models\Product::convertPrice($mp->price)) }}
          @else
            {{ $product->showPrice() }}
          @endif
        </h6>

        {{-- السعر السابق --}}
        <h6>
          <del>
            @if ($mp && !is_null($mp->previous_price))
              {{ \App\Models\Product::convertPrice($mp->previous_price) }}
            @else
              {{ $product->showPreviousPrice() }}
            @endif
          </del>
        </h6>
      </div>
      {{-- تقييمات … --}}
    </div>
  </div>
</div>
