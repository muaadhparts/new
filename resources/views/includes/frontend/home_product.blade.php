@php
    /** @var \App\Models\Product|\App\Models\MerchantProduct $product */
    /** @var \App\Models\MerchantProduct|null $mp */

    // Check if $product is actually a MerchantProduct instance
    $isMerchantProduct = $product instanceof \App\Models\MerchantProduct;

    // Determine merchant product - USE EAGER LOADED DATA (avoids N+1)
    if ($isMerchantProduct) {
        $merchant = $product;
        $actualProduct = $product->product;
    } else {
        $actualProduct = $product;
        $merchant = $mp ?? null;

        if (!$merchant) {
            // Use the accessor which uses eager-loaded data
            $merchant = $product->best_merchant_product;
        }
    }

    // Build product URL
    $productSlug = $isMerchantProduct ? optional($actualProduct)->slug : $product->slug;
    $productUrl = $merchant && $productSlug
        ? route('front.product', ['slug' => $productSlug, 'vendor_id' => $merchant->user_id, 'merchant_product_id' => $merchant->id])
        : ($productSlug ? route('front.product.legacy', $productSlug) : '#');

    // Calculate discount
    $offPercentage = $merchant && method_exists($merchant, 'offPercentage')
        ? $merchant->offPercentage()
        : ($actualProduct && method_exists($actualProduct, 'offPercentage') ? $actualProduct->offPercentage() : 0);

    // Get stock info
    $stockQty = $merchant ? (int)($merchant->stock ?? 0) : 0;
    $inStock = $stockQty > 0 || ($merchant && $merchant->preordered);
    $hasVendor = $merchant && $merchant->user_id > 0;

    // Build images array (main photo + vendor galleries)
    $images = [];
    $mainPhoto = $actualProduct->photo ?? null;
    $defaultImage = asset('assets/images/noimage.png');
    $vendorUserId = $merchant ? $merchant->user_id : null;

    // 1. Add main product photo first (product identity - always shown)
    if ($mainPhoto) {
        $images[] = filter_var($mainPhoto, FILTER_VALIDATE_URL)
            ? $mainPhoto
            : Storage::url($mainPhoto);
    }

    // Gallery images disabled for performance (N+1 optimization)
    // Use main product photo only for cards - gallery shown on product detail page

    // Ensure at least one image
    if (empty($images)) {
        $images[] = $defaultImage;
    }

    $hasMultipleImages = count($images) > 1;
    $cardId = 'pc_' . ($actualProduct->id ?? uniqid());
@endphp

<div class="{{ $class ?? 'col-6 col-md-4 col-lg-3' }}">
    <div class="m-product-card" id="{{ $cardId }}">
        {{-- Image Section --}}
        <div class="m-product-card__image">
            {{-- Discount Badge --}}
            @if ($offPercentage && round($offPercentage) > 0)
                <span class="m-product-card__badge m-product-card__badge--discount">
                    -{{ round($offPercentage) }}%
                </span>
            @endif

            {{-- Stock Badge --}}
            @if (!$inStock)
                <span class="m-product-card__badge m-product-card__badge--stock">
                    {{ __('Out of Stock') }}
                </span>
            @endif

            {{-- Wishlist Button --}}
            @auth
                <button type="button" class="m-product-card__wishlist wishlist"
                    data-href="{{ route('user-wishlist-add', $actualProduct->id) }}">
                    <i class="{{ wishlistCheck($actualProduct->id) ? 'fas' : 'far' }} fa-heart"></i>
                </button>
            @else
                <a href="{{ route('user.login') }}" class="m-product-card__wishlist">
                    <i class="far fa-heart"></i>
                </a>
            @endauth

            {{-- DEBUG: Product={{ $actualProduct->id ?? 'N/A' }}, Vendor={{ $vendorUserId ?? 'N/A' }}, Images={{ count($images) }} --}}

            {{-- Product Images Container --}}
            <a href="{{ $productUrl }}" class="m-product-card__image-link">
                @foreach($images as $index => $imgSrc)
                    <img src="{{ $imgSrc }}"
                         alt="{{ $actualProduct->showName() }}"
                         class="m-product-card__img {{ $index === 0 ? 'active' : '' }}"
                         data-index="{{ $index }}"
                         loading="lazy"
                         onerror="this.onerror=null; this.src='{{ $defaultImage }}';">
                @endforeach
            </a>

            {{-- Image Indicators (dots) --}}
            @if($hasMultipleImages)
                <div class="m-product-card__indicators">
                    @foreach($images as $index => $imgSrc)
                        <span class="m-product-card__indicator {{ $index === 0 ? 'active' : '' }}"
                              data-index="{{ $index }}"></span>
                    @endforeach
                </div>
            @endif

            {{-- Quick Actions --}}
            <div class="m-product-card__actions">
                @if ($actualProduct->type != 'Listing')
                    {{-- Compare --}}
                    <button type="button" class="m-product-card__action compare_product"
                        data-href="{{ route('product.compare.add', $actualProduct->id) }}"
                        title="@lang('Compare')">
                        <i class="fas fa-exchange-alt"></i>
                    </button>

                    {{-- Quick View --}}
                    <a href="{{ $productUrl }}" class="m-product-card__action" title="@lang('View')">
                        <i class="far fa-eye"></i>
                    </a>
                @endif
            </div>
        </div>

        {{-- Content Section --}}
        <div class="m-product-card__content">
            {{-- Product Name --}}
            <h6 class="m-product-card__title">
                <a href="{{ $productUrl }}">{{ Str::limit($actualProduct->showName(), 50) }}</a>
            </h6>

            {{-- Product Info: SKU, Vendor, Brand --}}
            <x-product-info
                :product="$actualProduct"
                :mp="$merchant"
                display-mode="compact"
                :show-sku="true"
                :show-vendor="true"
                :show-quality-brand="true"
                :show-brand="false"
                :show-stock="false"
            />

            {{-- Price --}}
            <div class="m-product-card__price">
                @if($merchant)
                    <span class="m-product-card__price-current">
                        {{ method_exists($merchant, 'showPrice') ? $merchant->showPrice() : \App\Models\Product::convertPrice($merchant->price) }}
                    </span>
                    @if($merchant->previous_price > 0)
                        <span class="m-product-card__price-old">
                            {{ \App\Models\Product::convertPrice($merchant->previous_price) }}
                        </span>
                    @endif
                @else
                    <span class="m-product-card__price-current">{{ $actualProduct->showPrice() }}</span>
                    @if($actualProduct->previous_price > 0)
                        <span class="m-product-card__price-old">{{ $actualProduct->showPreviousPrice() }}</span>
                    @endif
                @endif
            </div>

            {{-- Rating --}}
            <div class="m-product-card__rating">
                <i class="fas fa-star"></i>
                <span>{{ number_format($actualProduct->ratings_avg_rating ?? 0, 1) }}</span>
                <span class="m-product-card__rating-count">({{ $actualProduct->ratings_count ?? 0 }})</span>
            </div>

            {{-- Add to Cart - Uses Unified Cart System --}}
            @if ($actualProduct->type != 'Listing' && $actualProduct->product_type != 'affiliate')
                @if ($inStock && $hasVendor && $merchant)
                    {{-- UNIFIED: All data from $merchant (MerchantProduct) --}}
                    <button type="button"
                        class="m-product-card__cart-btn m-cart-add"
                        data-merchant-product-id="{{ $merchant->id }}"
                        data-vendor-id="{{ $merchant->user_id }}"
                        data-product-id="{{ $actualProduct->id }}"
                        data-min-qty="{{ max(1, (int)($merchant->minimum_qty ?? 1)) }}"
                        data-stock="{{ (int)($merchant->stock ?? 0) }}"
                        data-preordered="{{ $merchant->preordered ? '1' : '0' }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span>@lang('Add to Cart')</span>
                    </button>
                @else
                    <button type="button" class="m-product-card__cart-btn m-product-card__cart-btn--disabled" disabled>
                        <i class="fas fa-ban"></i>
                        <span>@lang('Out of Stock')</span>
                    </button>
                @endif
            @elseif ($actualProduct->product_type == 'affiliate')
                <a href="{{ $actualProduct->affiliate_link }}" class="m-product-card__cart-btn" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>@lang('Buy Now')</span>
                </a>
            @endif
        </div>
    </div>
</div>
