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

<div class="{{ isset($class) ? $class : 'col-6 col-sm-6 col-md-4 col-lg-3' }}" style="display: block !important; visibility: visible !important; opacity: 1 !important;">
  <div class="product-card shadow-sm">
    <div class="product-card-image">

      {{-- Badge الخصم (من عرض البائع إن وُجد) --}}
      @if (!is_null($offPercent) && $offPercent > 0)
        <span class="product-badge">-{{ $offPercent }}%</span>
      @endif

      {{-- صورة المنتج --}}
      <a href="{{ $detailsUrl }}" class="d-block product-image-link">
        <img class="product-card-img"
             src="{{ \Illuminate\Support\Facades\Storage::url($product->photo) ?? asset('assets/images/noimage.png') }}"
             alt="{{ $product->name }}"
             loading="lazy">
      </a>

      {{-- Quick Action Buttons (Always Visible at Bottom) --}}
      <div class="product-quick-actions position-absolute bottom-0 start-0 end-0 p-2">
        <div class="d-flex gap-2 justify-content-center">
          {{-- Quick View --}}
          <a href="{{ $detailsUrl }}" class="btn btn-sm btn-primary" title="@lang('View')">
            <i class="fas fa-eye"></i>
          </a>

          {{-- Wishlist Button --}}
          @if (Auth::check())
            @if (isset($wishlist))
              <a href="javascript:;" class="removewishlist btn btn-sm btn-danger"
                 data-href="{{ route('user-wishlist-remove', isset($product->wishlist_item_id) ? $product->wishlist_item_id : App\Models\Wishlist::where('user_id', Auth::id())->where('product_id',$product->id)->first()->id ?? 0) }}"
                 title="@lang('Remove from Wishlist')">
                <i class="fas fa-trash"></i>
              </a>
            @else
              <a href="javascript:;" class="wishlist btn btn-sm {{ isset($mp) ? (merchantWishlistCheck($mp->id) ? 'btn-danger' : 'btn-outline-danger') : (wishlistCheck($product->id) ? 'btn-danger' : 'btn-outline-danger') }}"
                 data-href="{{ isset($mp) ? route('merchant.wishlist.add', $mp->id) : 'javascript:;' }}"
                 title="@lang('Add to Wishlist')">
                <i class="fas fa-heart"></i>
              </a>
            @endif
          @else
            <a href="{{ route('user.login') }}" class="btn btn-sm btn-outline-danger" title="@lang('Login to Add Wishlist')">
              <i class="far fa-heart"></i>
            </a>
          @endif

          {{-- Compare --}}
          @if ($product->type != 'Listing')
            <a class="compare_product btn btn-sm btn-outline-secondary" href="javascript:;"
               data-href="{{ isset($mp) ? route('merchant.compare.add', $mp->id) : 'javascript:;' }}"
               title="@lang('Compare')">
              <i class="fas fa-balance-scale {{ isset($mp) ? (merchantCompareCheck($mp->id) ? 'text-primary' : '') : '' }}"></i>
            </a>
          @endif
        </div>
      </div>
    </div>

    <div class="product-card-body">
      {{-- Quality Brand Badge (Outside Image) --}}
      @if ($mp && $mp->qualityBrand)
        <div class="quality-brand-mini mb-2">
          @if ($mp->qualityBrand->logo_url)
            <img src="{{ $mp->qualityBrand->logo_url }}" alt="{{ $mp->qualityBrand->display_name }}">
          @endif
          <span>{{ $mp->qualityBrand->display_name }}</span>
        </div>
      @endif

      {{-- Product Title --}}
      <a href="{{ $detailsUrl }}" class="text-decoration-none">
        <h6 class="product-title mb-2" title="{{ strip_tags($product->name) }}">
          <x-product-name :product="$product" :vendor-id="$vendorId" target="_self" />
        </h6>
      </a>

      {{-- Merchant Info --}}
      @if($mp && $mp->user)
        <p class="text-muted small mb-2">
          <i class="fas fa-store me-1"></i>
          {{ Str::limit($mp->user->shop_name ?? $mp->user->name, 20) }}
        </p>
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
/* Product Card Image Section */
.product-card-image {
    position: relative;
    overflow: hidden;
    background: #f8f9fa;
    aspect-ratio: 1 / 1;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.product-image-link {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.product-card-img {
    width: 100%;
    height: 100%;
    max-height: 200px;
    object-fit: contain;
    transition: transform 0.4s ease;
}

.product-card:hover .product-card-img {
    transform: scale(1.05);
}

/* Quick Actions - Always Visible */
.product-quick-actions {
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    padding: 1rem 0.5rem 0.75rem !important;
}

.product-quick-actions .btn {
    background: rgba(255, 255, 255, 0.95);
    border: none;
    padding: 0.5rem;
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.product-quick-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.product-quick-actions .btn-primary {
    background: var(--primary-color) !important;
    color: #fff;
}

.product-quick-actions .btn-outline-danger {
    background: rgba(255, 255, 255, 0.95) !important;
    color: var(--danger-color);
    border: 1.5px solid var(--danger-color);
}

.product-quick-actions .btn-danger {
    background: var(--danger-color) !important;
    color: #fff;
}

.product-quick-actions .btn-outline-secondary {
    background: rgba(255, 255, 255, 0.95) !important;
    color: #6c757d;
    border: 1.5px solid #6c757d;
}

/* Product Title - Multi-line with dynamic height */
.product-title {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    min-height: 3.5rem;
    max-height: 4.5rem;
    line-height: 1.4;
    font-size: 0.9rem;
    font-weight: 600;
    word-break: break-word;
    hyphens: auto;
}

/* Quality Brand Mini Badge */
.quality-brand-mini {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 0.75rem;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    border: 1px solid rgba(102, 126, 234, 0.3);
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--primary-color);
}

.quality-brand-mini img {
    width: 18px;
    height: 18px;
    object-fit: contain;
    border-radius: 50%;
    background: #fff;
    padding: 2px;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .product-card-image {
        padding: 0.75rem;
    }

    .product-card-img {
        max-height: 150px;
    }

    .product-quick-actions .btn {
        width: 32px;
        height: 32px;
        padding: 0.4rem;
    }

    .product-card-body {
        padding: 0.875rem !important;
    }

    .product-title {
        font-size: 0.85rem !important;
        min-height: 2.25rem !important;
    }

    .product-price {
        font-size: 1.1rem !important;
    }

    .quality-brand-mini {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
    }

    .quality-brand-mini img {
        width: 16px;
        height: 16px;
    }
}
</style>
