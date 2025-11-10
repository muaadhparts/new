@php
    /** @var \App\Models\Product $product*/
    /** @var \App\Models\MerchantProduct|null $mp */

    // Use passed merchant product data or fallback to search
    if (!isset($mp)) {
        $merchant = $product->merchantProducts()
            ->where('status', 1)
            ->whereHas('user', function ($user) {
                $user->where('is_vendor', 2);
            })
            ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
            ->orderBy('price')
            ->first();
    } else {
        $merchant = $mp;
    }

    $vendorId  = optional($merchant)->user_id;
    $hasVendor = $vendorId > 0;

    // Calculate discount percentage
    $off = 0;
    if ($merchant && $merchant->previous_price > 0 && $merchant->price > 0) {
        $off = round((($merchant->previous_price - $merchant->price) * 100) / $merchant->previous_price);
    }

    // Details URL
    $detailsUrl = isset($merchant) ? route('front.product', ['slug' => $product->slug, 'vendor_id' => $merchant->user_id, 'merchant_product_id' => $merchant->id]) : 'javascript:;';

    $stockQty = optional($merchant)->stock;
    $inStock = $stockQty > 0;
@endphp

<div class="col-12">
  <div class="product-list-card card shadow-sm mb-3">
    <div class="row g-0">

      {{-- Product Image Section --}}
      <div class="col-md-3 col-lg-2">
        <div class="product-list-image position-relative">

          {{-- Discount Badge --}}
          @if ($off > 0)
            <span class="badge bg-danger position-absolute" style="top: 0.5rem; left: 0.5rem; z-index: 3; border-radius: 2rem; font-weight: 700; font-size: 0.75rem; padding: 0.35rem 0.75rem;">
              -{{ $off }}%
            </span>
          @endif

          {{-- Product Image --}}
          <a href="{{ $detailsUrl }}" class="d-block h-100">
            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->photo) ?? asset('assets/images/noimage.png') }}"
                 alt="{{ $product->name }}"
                 class="product-list-img"
                 loading="lazy">
          </a>
        </div>
      </div>

      {{-- Product Details Section --}}
      <div class="col-md-6 col-lg-7">
        <div class="card-body">

          {{-- Quality Brand Badge (Outside Image) --}}
          @if ($merchant && $merchant->qualityBrand)
            <div class="quality-brand-mini mb-2">
              @if ($merchant->qualityBrand->logo_url)
                <img src="{{ $merchant->qualityBrand->logo_url }}" alt="{{ $merchant->qualityBrand->display_name }}">
              @endif
              <span>{{ $merchant->qualityBrand->display_name }}</span>
            </div>
          @endif

          {{-- Product Title --}}
          <h5 class="card-title mb-2 product-list-title">
            <a href="{{ $detailsUrl }}" class="text-decoration-none text-dark fw-bold" title="{{ strip_tags($product->name) }}">
              {{ app()->getLocale() == 'ar' && !empty($product->label_ar) ? $product->label_ar : $product->name }}
            </a>
          </h5>

          {{-- Product SKU --}}
          <div class="mb-2">
            <small class="text-muted">
              <a href="{{ $detailsUrl }}" class="text-decoration-none text-muted">
                @lang('SKU'): {{ $product->sku ?? '-' }}
              </a>
            </small>
          </div>

          {{-- Ratings --}}
          <div class="mb-2">
            <i class="fas fa-star text-warning"></i>
            <span class="text-muted small">
              {{ number_format($product->ratings_avg_rating, 1) }} ({{ $product->ratings_count }} @lang('reviews'))
            </span>
          </div>

          {{-- Product Information Grid --}}
          <div class="row g-2 mb-2 small">

            {{-- Merchant Info --}}
            @if($merchant && $merchant->user)
              <div class="col-12">
                <i class="fas fa-store text-primary me-1"></i>
                <strong>@lang('Sold by:')</strong>
                <span class="text-primary">{{ Str::limit($merchant->user->shop_name ?? $merchant->user->name, 30) }}</span>
              </div>
            @endif

            {{-- Brand --}}
            @if($product->brand)
              <div class="col-sm-6">
                <i class="fas fa-tag text-muted me-1"></i>
                <strong>@lang('Brand:')</strong>
                <span>{{ Str::ucfirst($product->brand->name) }}</span>
              </div>
            @endif

            {{-- Stock Availability --}}
            <div class="col-sm-6">
              <i class="fas fa-box text-muted me-1"></i>
              <strong>@lang('Availability:')</strong>
              <span class="badge {{ $inStock ? 'bg-success' : 'bg-warning' }} ms-1">
                {{ $inStock ? __('In Stock') . ' (' . $stockQty . ')' : __('Out of Stock') }}
              </span>
            </div>

            {{-- Weight --}}
            @if($product->weight)
              <div class="col-sm-6">
                <i class="fas fa-weight text-muted me-1"></i>
                <strong>@lang('Weight, kg:')</strong>
                <span>{{ $product->weight }}</span>
              </div>
            @endif

            {{-- Processing Time --}}
            <div class="col-sm-6">
              <i class="fas fa-clock text-muted me-1"></i>
              <strong>@lang('Processing:')</strong>
              <span>@lang('2-3 days')</span>
            </div>

            {{-- Compatibility --}}
            <div class="col-12">
              {{-- <livewire:compatibility :sku="$product->sku" wire:key="$product->sku" /> --}}
            </div>
          </div>

          {{-- Quick Actions (Mobile Only) --}}
          <div class="d-md-none d-flex gap-2 mt-3">
            <a href="{{ $detailsUrl }}" class="btn btn-sm btn-outline-primary flex-fill">
              <i class="fas fa-eye me-1"></i>
              @lang('View')
            </a>

            {{-- Wishlist Button (Mobile) --}}
            @if (Auth::check())
              <a href="javascript:;" class="wishlist btn btn-sm {{ isset($merchant) ? (merchantWishlistCheck($merchant->id) ? 'btn-danger' : 'btn-outline-danger') : (wishlistCheck($product->id) ? 'btn-danger' : 'btn-outline-danger') }}"
                 data-href="{{ isset($merchant) ? route('merchant.wishlist.add', $merchant->id) : 'javascript:;' }}"
                 title="@lang('Add to Wishlist')">
                <i class="fas fa-heart"></i>
              </a>
            @else
              <a href="{{ route('user.login') }}" class="btn btn-sm btn-outline-danger" title="@lang('Login to Add Wishlist')">
                <i class="far fa-heart"></i>
              </a>
            @endif

            @if ($product->type != 'Listing')
              <a href="javascript:;" class="compare_product btn btn-sm btn-outline-secondary"
                 data-href="{{ isset($merchant) ? route('merchant.compare.add', $merchant->id) : 'javascript:;' }}"
                 title="@lang('Compare')">
                <i class="fas fa-balance-scale {{ isset($merchant) ? (merchantCompareCheck($merchant->id) ? 'text-primary' : '') : '' }}"></i>
              </a>
            @endif
          </div>
        </div>
      </div>

      {{-- Price & Actions Section --}}
      <div class="col-md-3 col-lg-3">
        <div class="card-body d-flex flex-column h-100 justify-content-between align-items-center text-center border-start">

          {{-- Quality Brand Logo --}}
          @if($merchant && $merchant->qualityBrand && $merchant->qualityBrand->logo)
            <div class="mb-3">
              <img src="{{ \Illuminate\Support\Facades\Storage::url($merchant->qualityBrand->logo) }}"
                   width="80"
                   height="80"
                   class="img-fluid"
                   style="object-fit: contain;"
                   alt="{{ $merchant->qualityBrand->display_name }}">
              <p class="small text-muted mb-0 mt-1">
                {{ app()->getLocale() == 'ar' && $merchant->qualityBrand->name_ar ? $merchant->qualityBrand->name_ar : $merchant->qualityBrand->name_en }}
              </p>
            </div>
          @endif

          {{-- Price --}}
          @if($merchant)
            <div class="mb-3">
              <div class="h4 text-primary fw-bold mb-1">
                {{ method_exists($merchant,'showPrice') ? $merchant->showPrice() : \App\Models\Product::convertPrice($merchant->price) }}
              </div>
              @if($merchant->previous_price > 0)
                <div class="text-muted text-decoration-line-through small">
                  {{ \App\Models\Product::convertPrice($merchant->previous_price) }}
                </div>
              @endif
            </div>
          @endif

          {{-- Action Buttons --}}
          <div class="w-100">

            {{-- Quick Actions (Desktop) --}}
            <div class="d-none d-md-flex gap-2 mb-2 justify-content-center">
              <a href="{{ $detailsUrl }}" class="btn btn-sm btn-outline-primary" title="@lang('View Details')">
                <i class="fas fa-eye"></i>
              </a>

              {{-- Wishlist Button --}}
              @if (Auth::check())
                <a href="javascript:;" class="wishlist btn btn-sm {{ isset($merchant) ? (merchantWishlistCheck($merchant->id) ? 'btn-danger' : 'btn-outline-danger') : (wishlistCheck($product->id) ? 'btn-danger' : 'btn-outline-danger') }}"
                   data-href="{{ isset($merchant) ? route('merchant.wishlist.add', $merchant->id) : 'javascript:;' }}"
                   title="@lang('Add to Wishlist')">
                  <i class="fas fa-heart"></i>
                </a>
              @else
                <a href="{{ route('user.login') }}" class="btn btn-sm btn-outline-danger" title="@lang('Login to Add Wishlist')">
                  <i class="far fa-heart"></i>
                </a>
              @endif

              @if ($product->type != 'Listing')
                <a href="javascript:;" class="compare_product btn btn-sm btn-outline-secondary"
                   data-href="{{ isset($merchant) ? route('merchant.compare.add', $merchant->id) : 'javascript:;' }}"
                   title="@lang('Compare')">
                  <i class="fas fa-balance-scale {{ isset($merchant) ? (merchantCompareCheck($merchant->id) ? 'text-primary' : '') : '' }}"></i>
                </a>
              @endif
            </div>

            {{-- Add to Cart Button --}}
            @if ($product->product_type == 'affiliate')
              <a href="{{ $product->affiliate_link }}" class="btn btn-primary w-100" target="_blank">
                <i class="fas fa-external-link-alt me-1"></i>
                @lang('View Product')
              </a>
            @else
              @if (!$hasVendor || !$inStock)
                <button class="btn btn-outline-secondary w-100" disabled>
                  <i class="fas fa-times-circle me-1"></i>
                  {{ __('Out of Stock') }}
                </button>
              @else
                @if ($product->type != 'Listing')
                  <a {{ $product->cross_products ? 'data-bs-target=#exampleModal' : '' }}
                     href="javascript:;"
                     data-href="{{ isset($merchant) ? route('merchant.cart.add', $merchant->id) : 'javascript:;' }}"
                     data-cross-href="{{ route('front.show.cross.product', $product->id) }}"
                     data-merchant-product="{{ $merchant->id ?? '' }}"
                     data-product="{{ $product->id }}"
                     class="btn btn-primary w-100 add_cart_click {{ $product->cross_products ? 'view_cross_product' : '' }}">
                    <i class="fas fa-shopping-cart me-1"></i>
                    @lang('Add To Cart')
                  </a>
                @endif
              @endif
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* ========================================
   ENHANCED LIST VIEW PRODUCT CARD
   ======================================== */
.product-list-card {
    border-radius: 16px;
    border: 2px solid transparent;
    background: #fff;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    position: relative;
}

.product-list-card::before {
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
}

.product-list-card:hover::before {
    opacity: 0;
}

.product-list-card::after {
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
}

.product-list-card:hover::after {
    opacity: 1;
}

.product-list-card:hover {
    box-shadow: 0 20px 60px rgba(102, 126, 234, 0.15);
    transform: translateY(-4px);
}

/* Product Image Section */
.product-list-image {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    min-height: 200px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    position: relative;
}

.product-list-image::before {
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

.product-list-card:hover .product-list-image::before {
    opacity: 1;
}

.product-list-img {
    width: 100%;
    height: 100%;
    max-height: 280px;
    object-fit: contain;
    transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
    filter: brightness(1);
}

.product-list-card:hover .product-list-img {
    transform: scale(1.08) translateY(-4px);
    filter: brightness(1.05);
}

/* Quality Brand Mini Badge */
.quality-brand-mini {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.08), rgba(118, 75, 162, 0.08));
    border: 1.5px solid rgba(102, 126, 234, 0.25);
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
    color: #667eea;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.1);
}

.quality-brand-mini:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.15), rgba(118, 75, 162, 0.15));
    border-color: rgba(102, 126, 234, 0.4);
    transform: translateY(-1px);
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

/* Product List Title - Multi-line */
.product-list-title {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    min-height: 3.5rem;
    max-height: 5rem;
    line-height: 1.5;
    word-break: break-word;
    hyphens: auto;
    margin-bottom: 0.75rem;
}

.product-list-title a {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    color: #1e293b;
    font-weight: 700;
    transition: all 0.3s ease;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    opacity: 0;
}

.product-list-title a {
    opacity: 1;
    -webkit-text-fill-color: #1e293b;
}

.product-list-card:hover .product-list-title a {
    -webkit-text-fill-color: transparent;
}

/* ========================================
   RESPONSIVE DESIGN
   ======================================== */

/* Tablets and Small Laptops */
@media (max-width: 991px) {
    .product-list-card {
        border-radius: 14px;
    }

    .product-list-image {
        min-height: 220px;
        padding: 1.25rem;
    }

    .product-list-img {
        max-height: 240px;
    }
}

/* Mobile Devices */
@media (max-width: 767px) {
    .product-list-card {
        border-radius: 12px;
        margin-bottom: 1rem !important;
    }

    .product-list-card::before,
    .product-list-card::after {
        border-radius: 12px;
    }

    .product-list-image {
        min-height: 200px;
        padding: 1rem;
    }

    .product-list-img {
        max-height: 200px;
    }

    .product-list-card .card-body {
        padding: 1rem !important;
    }

    .product-list-card .card-title {
        font-size: 0.95rem;
    }

    .product-list-title {
        min-height: 2.8rem;
        max-height: 3.8rem;
        font-size: 0.9rem;
        line-height: 1.4;
        margin-bottom: 0.5rem;
    }

    .quality-brand-mini {
        font-size: 0.7rem;
        padding: 0.35rem 0.75rem;
    }

    .quality-brand-mini img {
        width: 16px;
        height: 16px;
    }

    /* Mobile Price Section */
    .product-list-card .h4 {
        font-size: 1.25rem !important;
    }

    /* Mobile Buttons */
    .product-list-card .btn-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
    }

    .product-list-card .btn {
        font-size: 0.9rem;
        padding: 0.6rem 1rem;
    }
}

/* Small Mobile Devices */
@media (max-width: 575px) {
    .product-list-card {
        border-radius: 10px;
    }

    .product-list-card::before,
    .product-list-card::after {
        border-radius: 10px;
    }

    .product-list-image {
        min-height: 180px;
        padding: 0.75rem;
    }

    .product-list-img {
        max-height: 180px;
    }

    .product-list-title {
        font-size: 0.875rem;
        min-height: 2.5rem;
        max-height: 3.5rem;
    }

    .quality-brand-mini {
        font-size: 0.65rem;
        padding: 0.3rem 0.6rem;
    }

    .quality-brand-mini img {
        width: 14px;
        height: 14px;
    }

    .product-list-card .small,
    .product-list-card small {
        font-size: 0.8rem !important;
    }
}

/* Desktop */
@media (min-width: 768px) {
    .product-list-image {
        height: 100%;
        min-height: 280px;
    }

    .product-list-card .border-start {
        border-left: 2px solid #e2e8f0 !important;
    }

    .product-list-card:hover .border-start {
        border-left-color: rgba(102, 126, 234, 0.2) !important;
    }
}

/* Large Desktop */
@media (min-width: 1200px) {
    .product-list-image {
        min-height: 300px;
    }

    .product-list-img {
        max-height: 300px;
    }
}

/* ========================================
   ENHANCED BUTTONS & BADGES
   ======================================== */

/* Discount Badge */
.product-list-card .badge.bg-danger {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
    border: none;
    box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3);
    font-weight: 800;
    letter-spacing: 0.5px;
}

/* Stock Badge */
.product-list-card .badge.bg-success {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%) !important;
    color: #0f172a !important;
    font-weight: 700;
}

.product-list-card .badge.bg-warning {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
    color: #0f172a !important;
    font-weight: 700;
}

/* Button Enhancements */
.product-list-card .btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.product-list-card .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.35);
}

.product-list-card .btn-outline-primary {
    border: 2px solid #667eea;
    color: #667eea;
    font-weight: 600;
    transition: all 0.3s ease;
}

.product-list-card .btn-outline-primary:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-color: transparent;
    color: #fff;
    transform: translateY(-1px);
}

.product-list-card .btn-outline-danger {
    border: 2px solid #f5576c;
    color: #f5576c;
    transition: all 0.3s ease;
}

.product-list-card .btn-outline-danger:hover,
.product-list-card .btn-danger {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-color: transparent;
    color: #fff;
    transform: translateY(-1px);
}

.product-list-card .btn-outline-secondary {
    border: 2px solid #94a3b8;
    color: #64748b;
    transition: all 0.3s ease;
}

.product-list-card .btn-outline-secondary:hover {
    background: #64748b;
    border-color: #64748b;
    color: #fff;
    transform: translateY(-1px);
}

/* Rating Stars */
.product-list-card .fa-star {
    filter: drop-shadow(0 2px 4px rgba(251, 191, 36, 0.3));
}

/* Info Icons */
.product-list-card .fa-store,
.product-list-card .fa-tag,
.product-list-card .fa-box,
.product-list-card .fa-weight,
.product-list-card .fa-clock {
    filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.1));
}

/* Price Styling */
.product-list-card .h4.text-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 800;
    letter-spacing: -0.5px;
}

/* Card Body Border */
.product-list-card .card-body {
    position: relative;
    z-index: 1;
}

/* Smooth Transitions */
.product-list-card * {
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>
