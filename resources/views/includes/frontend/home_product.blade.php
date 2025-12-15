@php
    /** @var \App\Models\Product|\App\Models\MerchantProduct $product */
    /** @var \App\Models\MerchantProduct|null $mp */

    // Check if $product is actually a MerchantProduct instance
    $isMerchantProduct = $product instanceof \App\Models\MerchantProduct;

    // Determine merchant product
    if ($isMerchantProduct) {
        $merchant = $product;
        $actualProduct = $product->product;
    } else {
        $actualProduct = $product;
        $merchant = $mp ?? null;

        if (!$merchant) {
            $merchant = $product->merchantProducts()
                ->where('status', 1)
                ->whereHas('user', fn($u) => $u->where('is_vendor', 2))
                ->orderByRaw('CASE WHEN (stock IS NULL OR stock = 0) THEN 1 ELSE 0 END ASC')
                ->orderBy('price')
                ->first();
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

    // Build images array (main photo + galleries)
    $images = [];
    $mainPhoto = $actualProduct->photo ?? null;
    $defaultImage = asset('assets/images/noimage.png');

    // Add main photo first
    if ($mainPhoto) {
        $images[] = filter_var($mainPhoto, FILTER_VALIDATE_URL)
            ? $mainPhoto
            : Storage::url($mainPhoto);
    }

    // Add gallery images (limit to 4 total including main)
    if ($actualProduct->relationLoaded('galleries')) {
        $galleries = $actualProduct->galleries;
    } else {
        $galleries = $actualProduct->galleries()->take(3)->get();
    }

    foreach ($galleries as $gallery) {
        if (count($images) >= 4) break;
        if ($gallery->photo) {
            $images[] = filter_var($gallery->photo, FILTER_VALIDATE_URL)
                ? $gallery->photo
                : asset('assets/images/galleries/' . $gallery->photo);
        }
    }

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

            {{-- Add to Cart --}}
            @if ($actualProduct->type != 'Listing' && $actualProduct->product_type != 'affiliate')
                @if ($inStock && $hasVendor)
                    @php
                        $addCartUrl = $merchant
                            ? route('merchant.cart.add', $merchant->id) . '?user=' . $merchant->user_id
                            : route('product.cart.add', $actualProduct->id);
                        $qtyInputId = 'hp_' . ($merchant ? $merchant->id : $actualProduct->id);
                    @endphp
                    <button type="button"
                        class="m-product-card__cart-btn add_cart_click hp-add-cart"
                        data-href="{{ $addCartUrl }}"
                        data-merchant-product="{{ $merchant->id ?? '' }}"
                        data-qty-prefix="{{ $qtyInputId }}"
                        data-cross-href="{{ route('front.show.cross.product', $actualProduct->id) }}">
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
