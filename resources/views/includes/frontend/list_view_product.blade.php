@php
    /** @var \App\Models\Product $product */
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

          {{-- Brand Quality Badge --}}
          @if ($merchant && $merchant->qualityBrand)
            <span class="badge bg-primary position-absolute" style="top: 2.5rem; left: 0.5rem; z-index: 3; font-size: 0.7rem;">
              @if ($merchant->qualityBrand->logo_url)
                <img src="{{ $merchant->qualityBrand->logo_url }}" alt="{{ $merchant->qualityBrand->display_name }}" style="width: 14px; height: 14px; margin-right: 3px; vertical-align: middle;">
              @endif
              {{ Str::limit($merchant->qualityBrand->display_name, 12) }}
            </span>
          @endif

          {{-- Wishlist Button --}}
          <div class="position-absolute top-0 end-0 p-2" style="z-index: 3;">
            @if (Auth::check())
              <a href="javascript:;" class="wishlist btn btn-sm rounded-circle p-2 {{ isset($merchant) ? (merchantWishlistCheck($merchant->id) ? 'btn-danger' : 'btn-outline-light') : (wishlistCheck($product->id) ? 'btn-danger' : 'btn-outline-light') }}"
                 data-href="{{ isset($merchant) ? route('merchant.wishlist.add', $merchant->id) : 'javascript:;' }}"
                 title="@lang('Add to Wishlist')">
                <i class="fas fa-heart"></i>
              </a>
            @else
              <a href="{{ route('user.login') }}" class="btn btn-sm btn-outline-light rounded-circle p-2" title="@lang('Login to Add Wishlist')">
                <i class="far fa-heart"></i>
              </a>
            @endif
          </div>

          {{-- Product Image --}}
          <a href="{{ $detailsUrl }}" class="d-block h-100">
            <img src="{{ \Illuminate\Support\Facades\Storage::url($product->photo) ?? asset('assets/images/noimage.png') }}"
                 alt="{{ $product->name }}"
                 class="w-100 h-100"
                 style="object-fit: contain; min-height: 200px; background: #f8f9fa;"
                 loading="lazy">
          </a>
        </div>
      </div>

      {{-- Product Details Section --}}
      <div class="col-md-6 col-lg-7">
        <div class="card-body">

          {{-- Product Title --}}
          <h5 class="card-title mb-2">
            <a href="{{ $detailsUrl }}" class="text-decoration-none text-dark fw-bold">
              <x-product-name :product="$product" :vendor-id="$vendorId" target="_self" />
            </a>
          </h5>

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
              <livewire:compatibility :sku="$product->sku" wire:key="$product->sku" />
            </div>
          </div>

          {{-- Quick Actions (Mobile Only) --}}
          <div class="d-md-none d-flex gap-2 mt-3">
            <a href="{{ $detailsUrl }}" class="btn btn-sm btn-outline-primary flex-fill">
              <i class="fas fa-eye me-1"></i>
              @lang('View')
            </a>
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
/* Enhanced List View Product Card */
.product-list-card {
    border-radius: var(--border-radius);
    border: 1.5px solid var(--border-color);
    transition: all var(--transition);
    overflow: hidden;
}

.product-list-card:hover {
    box-shadow: var(--box-shadow-lg);
    transform: translateY(-2px);
    border-color: var(--primary-color);
}

.product-list-image {
    background: #f8f9fa;
    min-height: 200px;
    overflow: hidden;
}

.product-list-image img {
    transition: transform var(--transition-slow);
}

.product-list-card:hover .product-list-image img {
    transform: scale(1.05);
}

/* Responsive Adjustments */
@media (max-width: 767px) {
    .product-list-image {
        min-height: 250px;
    }

    .product-list-card .card-body {
        padding: 1rem;
    }

    .product-list-card .card-title {
        font-size: 1rem;
    }
}

@media (min-width: 768px) {
    .product-list-image {
        height: 100%;
        min-height: 280px;
    }
}
</style>
