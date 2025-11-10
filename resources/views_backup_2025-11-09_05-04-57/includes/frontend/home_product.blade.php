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
/* ========================================
   MODERN PRODUCT CARD - GRID VIEW
   ======================================== */

/* Main Card Container */
.product-card {
    border-radius: 16px;
    border: 2px solid transparent;
    background: #fff;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    position: relative;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.product-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 16px;
    padding: 2px;
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    opacity: 1;
    transition: opacity 0.4s ease;
    z-index: 0;
}

.product-card:hover::before {
    opacity: 0;
}

.product-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    border-radius: 16px;
    padding: 2px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    opacity: 0;
    transition: opacity 0.4s ease;
    z-index: 0;
}

.product-card:hover::after {
    opacity: 1;
}

.product-card:hover {
    box-shadow: 0 20px 60px rgba(102, 126, 234, 0.15);
    transform: translateY(-6px);
}

/* Product Card Image Section */
.product-card-image {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    aspect-ratio: 1 / 1;
    border-radius: 14px 14px 0 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    z-index: 1;
}

.product-card-image::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.03) 0%, rgba(118, 75, 162, 0.03) 100%);
    opacity: 0;
    transition: opacity 0.4s ease;
}

.product-card:hover .product-card-image::before {
    opacity: 1;
}

.product-image-link {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Product Image */
.product-card-img {
    width: 100%;
    height: 100%;
    max-height: 220px;
    object-fit: contain;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    filter: brightness(1);
    z-index: 1;
}

.product-card:hover .product-card-img {
    transform: scale(1.1) translateY(-4px);
    filter: brightness(1.05) drop-shadow(0 8px 16px rgba(0,0,0,0.1));
}

/* Discount Badge */
.product-badge {
    position: absolute !important;
    top: 0.75rem !important;
    left: 0.75rem !important;
    z-index: 10 !important;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
    color: #fff !important;
    padding: 0.4rem 0.875rem !important;
    border-radius: 50px !important;
    font-weight: 800 !important;
    font-size: 0.875rem !important;
    letter-spacing: 0.5px !important;
    box-shadow: 0 4px 12px rgba(245, 87, 108, 0.35) !important;
    border: 2px solid #fff !important;
}

/* Quick Actions - Modern Design */
.product-quick-actions {
    background: linear-gradient(to top, rgba(0,0,0,0.85), transparent);
    padding: 1.25rem 0.75rem 0.875rem !important;
    z-index: 2;
}

.product-quick-actions .btn {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(8px);
    border: none;
    padding: 0.5rem;
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    border-radius: 10px;
}

.product-quick-actions .btn:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
}

.product-quick-actions .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: #fff;
}

.product-quick-actions .btn-outline-danger {
    background: rgba(255, 255, 255, 0.98) !important;
    color: #f5576c;
    border: 2px solid #f5576c;
}

.product-quick-actions .btn-danger {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
    color: #fff;
    border: none;
}

.product-quick-actions .btn-outline-secondary {
    background: rgba(255, 255, 255, 0.98) !important;
    color: #64748b;
    border: 2px solid #94a3b8;
}

/* Product Card Body */
.product-card-body {
    padding: 1.25rem;
    position: relative;
    z-index: 1;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

/* Product Title - Enhanced */
.product-title {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    min-height: 3.6rem;
    max-height: 4.8rem;
    line-height: 1.5;
    font-size: 0.9rem;
    font-weight: 700;
    word-break: break-word;
    hyphens: auto;
    color: #1e293b;
    margin-bottom: 0.75rem;
    transition: all 0.3s ease;
}

.product-card:hover .product-title {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Quality Brand Badge - Premium Design */
.quality-brand-mini {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 1rem;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
    border: 1.5px solid rgba(102, 126, 234, 0.25);
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #667eea;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
}

.product-card:hover .quality-brand-mini {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
    border-color: rgba(102, 126, 234, 0.4);
    transform: translateX(2px);
}

.quality-brand-mini img {
    width: 20px;
    height: 20px;
    object-fit: contain;
    border-radius: 50%;
    background: #fff;
    padding: 2px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Price Section */
.product-price {
    font-size: 1.25rem;
    font-weight: 800;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    letter-spacing: -0.5px;
}

.product-price-old {
    font-size: 0.9rem;
    color: #94a3b8;
    text-decoration: line-through;
    font-weight: 600;
}

/* Merchant Info */
.product-card-body .text-muted {
    color: #64748b !important;
    font-size: 0.85rem;
    font-weight: 500;
}

.product-card-body .text-muted i {
    color: #667eea;
}

/* Buttons - Modern & Gradient */
.product-card-body .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    font-weight: 700;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
}

.product-card-body .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.product-card-body .btn-outline-secondary {
    border: 2px solid #cbd5e1;
    color: #64748b;
    font-weight: 700;
    transition: all 0.3s ease;
    border-radius: 10px;
    background: #f8fafc;
}

.product-card-body .btn-outline-secondary:hover {
    background: #64748b;
    border-color: #64748b;
    color: #fff;
    transform: translateY(-1px);
}

/* ========================================
   RESPONSIVE DESIGN - MOBILE FIRST
   ======================================== */

/* Tablets */
@media (max-width: 991px) {
    .product-card {
        border-radius: 14px;
    }

    .product-card::before,
    .product-card::after {
        border-radius: 14px;
    }

    .product-card-image {
        padding: 1rem;
    }

    .product-card-img {
        max-height: 200px;
    }
}

/* Small Tablets & Large Phones */
@media (max-width: 767px) {
    .product-card {
        border-radius: 12px;
    }

    .product-card::before,
    .product-card::after {
        border-radius: 12px;
    }

    .product-card-image {
        padding: 0.875rem;
        border-radius: 12px 12px 0 0;
    }

    .product-card-img {
        max-height: 180px;
    }

    .product-card-body {
        padding: 1rem !important;
    }

    .product-title {
        font-size: 0.875rem;
        min-height: 3rem;
        max-height: 4rem;
    }

    .product-price {
        font-size: 1.15rem;
    }

    .product-badge {
        top: 0.5rem !important;
        left: 0.5rem !important;
        padding: 0.35rem 0.75rem !important;
        font-size: 0.8rem !important;
    }

    .quality-brand-mini {
        font-size: 0.7rem;
        padding: 0.35rem 0.75rem;
    }

    .quality-brand-mini img {
        width: 18px;
        height: 18px;
    }
}

/* Mobile Devices */
@media (max-width: 576px) {
    .product-card {
        border-radius: 10px;
    }

    .product-card::before,
    .product-card::after {
        border-radius: 10px;
    }

    .product-card-image {
        padding: 0.75rem;
        border-radius: 10px 10px 0 0;
    }

    .product-card-img {
        max-height: 160px;
    }

    .product-quick-actions {
        padding: 1rem 0.5rem 0.75rem !important;
    }

    .product-quick-actions .btn {
        width: 34px;
        height: 34px;
        padding: 0.45rem;
        border-radius: 8px;
    }

    .product-card-body {
        padding: 0.875rem !important;
    }

    .product-title {
        font-size: 0.825rem;
        min-height: 2.5rem;
        max-height: 3.5rem;
        line-height: 1.4;
        -webkit-line-clamp: 2;
    }

    .product-price {
        font-size: 1.1rem;
    }

    .product-price-old {
        font-size: 0.8rem;
    }

    .product-badge {
        top: 0.5rem !important;
        left: 0.5rem !important;
        padding: 0.3rem 0.65rem !important;
        font-size: 0.75rem !important;
    }

    .quality-brand-mini {
        font-size: 0.65rem;
        padding: 0.3rem 0.65rem;
    }

    .quality-brand-mini img {
        width: 16px;
        height: 16px;
    }

    .product-card-body .text-muted {
        font-size: 0.75rem;
    }

    .product-card-body .btn {
        font-size: 0.8rem;
        padding: 0.6rem 1rem;
    }
}

/* Very Small Devices */
@media (max-width: 375px) {
    .product-card-image {
        padding: 0.5rem;
    }

    .product-card-img {
        max-height: 140px;
    }

    .product-title {
        font-size: 0.8rem;
        min-height: 2.3rem;
    }

    .product-price {
        font-size: 1rem;
    }

    .quality-brand-mini {
        font-size: 0.6rem;
        padding: 0.25rem 0.55rem;
    }

    .quality-brand-mini img {
        width: 14px;
        height: 14px;
    }
}

/* Landscape Mobile */
@media (max-width: 896px) and (orientation: landscape) {
    .product-card-image {
        aspect-ratio: 4 / 3;
    }

    .product-card-img {
        max-height: 150px;
    }
}
</style>
