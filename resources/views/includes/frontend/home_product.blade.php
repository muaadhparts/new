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

<div class="{{ isset($class) ? $class : 'col-6 col-sm-6 col-md-4 col-lg-3' }}">
  <div class="product-card shadow-sm">
    <div class="product-card-image">

      {{-- Badge الخصم (من عرض البائع إن وُجد) --}}
      @if (!is_null($offPercent) && $offPercent > 0)
        <span class="product-badge">-{{ $offPercent }}%</span>
      @endif

      {{-- Brand Quality Badge --}}
      @if ($mp && $mp->qualityBrand)
        <span class="badge bg-primary position-absolute"
              style="top: 3.5rem; left: 0.75rem; font-size: 0.7rem; z-index: 3; box-shadow: var(--box-shadow-sm);">
          @if ($mp->qualityBrand->logo_url)
            <img src="{{ $mp->qualityBrand->logo_url }}"
                 alt="{{ $mp->qualityBrand->display_name }}"
                 style="width: 14px; height: 14px; margin-right: 3px; vertical-align: middle;">
          @endif
          {{ Str::limit($mp->qualityBrand->display_name, 12) }}
        </span>
      @endif

      {{-- Wishlist Button --}}
      <div class="position-absolute top-0 end-0 p-2" style="z-index: 3;">
        @if (Auth::check())
          @if (isset($wishlist))
            <a href="javascript:;" class="removewishlist btn btn-sm btn-danger rounded-circle p-2"
               data-href="{{ route('user-wishlist-remove', isset($product->wishlist_item_id) ? $product->wishlist_item_id : App\Models\Wishlist::where('user_id', Auth::id())->where('product_id',$product->id)->first()->id ?? 0) }}"
               title="@lang('Remove from Wishlist')">
              <i class="fas fa-trash"></i>
            </a>
          @else
            <a href="javascript:;" class="wishlist btn btn-sm rounded-circle p-2 {{ isset($mp) ? (merchantWishlistCheck($mp->id) ? 'btn-danger' : 'btn-outline-light') : (wishlistCheck($product->id) ? 'btn-danger' : 'btn-outline-light') }}"
               data-href="{{ isset($mp) ? route('merchant.wishlist.add', $mp->id) : 'javascript:;' }}"
               title="@lang('Add to Wishlist')">
              <i class="fas fa-heart"></i>
            </a>
          @endif
        @else
          <a href="{{ route('user.login') }}" class="btn btn-sm btn-outline-light rounded-circle p-2" title="@lang('Login to Add Wishlist')">
            <i class="far fa-heart"></i>
          </a>
        @endif
      </div>

      {{-- صورة المنتج --}}
      <a href="{{ $detailsUrl }}" class="d-block">
        <img class="w-100 h-100"
             style="object-fit: contain;"
             src="{{ \Illuminate\Support\Facades\Storage::url($product->photo) ?? asset('assets/images/noimage.png') }}"
             alt="{{ $product->name }}"
             loading="lazy">
      </a>

      {{-- Quick Action Buttons (Hover) --}}
      <div class="product-quick-actions position-absolute bottom-0 start-0 end-0 p-2"
           style="background: linear-gradient(to top, rgba(0,0,0,0.7), transparent); opacity: 0; transition: opacity 0.3s ease;">
        <div class="d-flex gap-2 justify-content-center">
          {{-- Compare --}}
          @if ($product->type != 'Listing')
            <a class="compare_product btn btn-sm btn-light rounded-circle" href="javascript:;"
               data-href="{{ isset($mp) ? route('merchant.compare.add', $mp->id) : 'javascript:;' }}"
               title="@lang('Compare')">
              <i class="fas fa-balance-scale {{ isset($mp) ? (merchantCompareCheck($mp->id) ? 'text-primary' : '') : '' }}"></i>
            </a>
          @endif

          {{-- Quick View --}}
          <a href="{{ $detailsUrl }}" class="btn btn-sm btn-light rounded-circle" title="@lang('Quick View')">
            <i class="fas fa-eye"></i>
          </a>
        </div>
      </div>
    </div>

    <div class="product-card-body">
      {{-- Product Title --}}
      <a href="{{ $detailsUrl }}" class="text-decoration-none">
        <h6 class="product-title mb-2">
          <x-product-name :product="$product" :vendor-id="$vendorId" target="_self" />
        </h6>
      </a>

      {{-- Merchant Info --}}
      @if($mp && $mp->user)
        <p class="text-muted small mb-1">
          <i class="fas fa-store me-1"></i>
          {{ Str::limit($mp->user->shop_name ?? $mp->user->name, 20) }}
        </p>
        @if($mp->qualityBrand)
          <p class="text-muted small mb-2">
            <i class="fas fa-award me-1"></i>
            {{ Str::limit(app()->getLocale() == 'ar' && $mp->qualityBrand->name_ar ? $mp->qualityBrand->name_ar : $mp->qualityBrand->name_en, 20) }}
          </p>
        @endif
      @endif

      {{-- Price --}}
      <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
          <div class="product-price mb-0">
            @if ($mp && method_exists($mp, 'showPrice'))
              {{ $mp->showPrice() }}
            @elseif ($mp && isset($mp->price))
              {{ \App\Helpers\PriceHelper::showCurrencyPrice(\App\Models\Product::convertPrice($mp->price)) }}
            @else
              {{ $product->showPrice() }}
            @endif
          </div>
          @if ($mp && !is_null($mp->previous_price) && $mp->previous_price > $mp->price)
            <div class="product-price-old">
              {{ \App\Models\Product::convertPrice($mp->previous_price) }}
            </div>
          @elseif($product->showPreviousPrice() != $product->showPrice())
            <div class="product-price-old">
              {{ $product->showPreviousPrice() }}
            </div>
          @endif
        </div>
      </div>

      {{-- Add to Cart Button --}}
      @if ($product->product_type == 'affiliate')
        <a href="{{ $product->affiliate_link }}" class="btn btn-primary w-100 btn-sm" target="_blank">
          <i class="fas fa-external-link-alt me-1"></i>
          @lang('View Product')
        </a>
      @else
        @if (!$hasVendor || !$inStock)
          <button class="btn btn-outline-secondary w-100 btn-sm" disabled>
            <i class="fas fa-times-circle me-1"></i>
            {{ __('Out of Stock') }}
          </button>
        @else
          @if ($product->type != 'Listing')
            <a href="javascript:;"
               {{ $product->cross_products ? 'data-bs-target=#exampleModal' : '' }}
               data-href="{{ isset($mp) ? route('merchant.cart.add', $mp->id) : 'javascript:;' }}"
               data-cross-href="{{ route('front.show.cross.product', $product->id) }}"
               data-merchant-product="{{ $mp->id ?? '' }}"
               data-product="{{ $product->id }}"
               class="btn btn-primary w-100 btn-sm add_cart_click {{ $product->cross_products ? 'view_cross_product' : '' }}">
              <i class="fas fa-shopping-cart me-1"></i>
              @lang('Add To Cart')
            </a>
          @endif
        @endif
      @endif
    </div>
  </div>
</div>

<style>
/* Product Card Hover Effects */
.product-card:hover .product-quick-actions {
    opacity: 1 !important;
}

.product-card-image {
    position: relative;
    overflow: hidden;
    background: #f8f9fa;
    aspect-ratio: 1 / 1;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.product-card-image img {
    transition: transform 0.4s ease;
}

.product-card:hover .product-card-image img {
    transform: scale(1.05);
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .product-card-body {
        padding: 0.875rem !important;
    }

    .product-title {
        font-size: 0.85rem !important;
        min-height: 2.5rem !important;
    }

    .product-price {
        font-size: 1.1rem !important;
    }
}
</style>
