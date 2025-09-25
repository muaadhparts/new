@php
  // If vendor_user_id is already set on the product (from wishlist), use that merchant product
  if (isset($product->vendor_user_id) && $product->vendor_user_id) {
      $mp = $product->merchantProducts()
            ->where('user_id', $product->vendor_user_id)
            ->where('status', 1)
            ->first();
      $vendorId = $product->vendor_user_id;
  } else {
      // اختيار عرض البائع: نشط أولاً، المتوفر قبل غير المتوفر، ثم الأرخص
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

  $hasVendor  = $vendorId > 0;

  // رابط التفاصيل يتطلب تمرير {user}.. عند غياب بائع متاح نوقف الرابط
  $detailsUrl = $hasVendor ? route('front.product', ['slug' => $product->slug, 'user' => $vendorId]) : 'javascript:;';

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
          @if($mp && $mp->id)
            <a href="javascript:;" class="wishlist"
               data-href="{{ route('user-wishlist-add-merchant', $mp->id) }}">
          @else
            <a href="javascript:;" class="wishlist"
               data-href="{{ route('user-wishlist-add', $product->id) }}">
          @endif
            <div class="add-to-wishlist-btn {{ ($mp && $mp->id) ? (merchantWishlistCheck($mp->id) ? 'active' : '') : (wishlistCheck($product->id) ? 'active' : '') }}">
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
             data-href="{{ ($mp && $mp->id) ? route('product.compare.add.merchant', $mp->id) : ($hasVendor ? route('product.compare.add', ['id'=>$product->id,'user'=>$vendorId]) : 'javascript:;') }}">
            <div class="compare">…</div>
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
                 data-href="{{ route('product.cart.add', ['id'=>$product->id,'user'=>$vendorId]) }}"
                 data-cross-href="{{ route('front.show.cross.product', $product->id) }}"
                 data-user="{{ $vendorId }}"
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
