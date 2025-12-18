{{--
    Unified Product Card Component
    ==============================
    Supports two data sources:
    1. ProductCardDTO: $card (from category, search-results, products pages)
    2. Product + MerchantProduct: $product, $mp (from wishlist, vendor, related products)

    Layout modes: $layout = 'grid' (default) | 'list'
--}}

@php
    // ========================================
    // SECTION 1: Data Normalization
    // ========================================
    // Normalize data from either ProductCardDTO or Product+MerchantProduct
    // into unified variables for the template

    $layout = $layout ?? 'grid';
    $defaultImage = asset('assets/images/noimage.png');

    // Check if we have a ProductCardDTO
    if (isset($card) && $card instanceof \App\DataTransferObjects\ProductCardDTO) {
        // === Source: ProductCardDTO ===
        $productId = $card->productId;
        $merchantId = $card->merchantId;
        $vendorId = $card->vendorId;
        $productName = $card->productName;
        $productUrl = $card->detailsUrl;
        $photo = $card->photo;
        $sku = $card->sku;
        $brandName = $card->brandName;
        $qualityBrandName = $card->qualityBrandName;
        $qualityBrandLogo = $card->qualityBrandLogo ?? null;
        $vendorName = $card->vendorName;
        $offPercentage = $card->offPercentage;
        $inStock = $card->inStock;
        $stockQty = $card->stock;
        $stockText = $card->stockText ?? ($inStock ? __('In Stock') : __('Out of Stock'));
        $stockBadgeClass = $card->stockBadgeClass ?? ($inStock ? 'bg-success' : 'bg-danger');
        $hasVendor = $card->hasVendor;
        $priceFormatted = $card->priceFormatted;
        $previousPrice = $card->previousPrice;
        $previousPriceFormatted = $card->previousPriceFormatted;
        $ratingsAvg = $card->ratingsAvg;
        $ratingsCount = $card->ratingsCount;
        $minQty = $card->minQty;
        $preordered = $card->preordered ?? false;
        $productType = $card->type;
        $affiliateProductType = $card->productType ?? null;
        $affiliateLink = $card->affiliateLink ?? null;
        $wishlistUrl = $card->wishlistUrl;
        $isInWishlist = $card->isInWishlist;
        $compareUrl = $card->compareUrl;

        // For component compatibility
        $actualProduct = null;
        $merchant = null;
        $useComponent = false;
    } else {
        // === Source: Product + MerchantProduct ===
        /** @var \App\Models\Product|\App\Models\MerchantProduct $product */
        /** @var \App\Models\MerchantProduct|null $mp */

        $isMerchantProduct = $product instanceof \App\Models\MerchantProduct;

        if ($isMerchantProduct) {
            $merchant = $product;
            $actualProduct = $product->product;
        } else {
            $actualProduct = $product;
            $merchant = $mp ?? null;
            if (!$merchant) {
                $merchant = $product->best_merchant_product;
            }
        }

        // Build normalized variables
        $productId = $actualProduct->id ?? null;
        $merchantId = $merchant->id ?? null;
        $vendorId = $merchant->user_id ?? null;
        $productName = $actualProduct->showName();

        $productSlug = $isMerchantProduct ? optional($actualProduct)->slug : $product->slug;
        $productUrl = $merchant && $productSlug
            ? route('front.product', ['slug' => $productSlug, 'vendor_id' => $merchant->user_id, 'merchant_product_id' => $merchant->id])
            : ($productSlug ? route('front.product.legacy', $productSlug) : '#');

        $mainPhoto = $actualProduct->photo ?? null;
        $photo = $mainPhoto
            ? (filter_var($mainPhoto, FILTER_VALIDATE_URL) ? $mainPhoto : Storage::url($mainPhoto))
            : $defaultImage;

        $sku = $actualProduct->sku ?? null;
        $brandName = $actualProduct->brand->name ?? null;
        $qualityBrandName = $merchant->qualityBrand->name ?? null;
        $qualityBrandLogo = $merchant->qualityBrand->photo ?? null;
        $vendorName = $merchant ? ($merchant->user->shop_name ?? $merchant->user->name ?? null) : null;

        $offPercentage = $merchant && method_exists($merchant, 'offPercentage')
            ? $merchant->offPercentage()
            : ($actualProduct && method_exists($actualProduct, 'offPercentage') ? $actualProduct->offPercentage() : 0);

        $stockQty = $merchant ? (int)($merchant->stock ?? 0) : 0;
        $inStock = $stockQty > 0 || ($merchant && $merchant->preordered);
        $stockText = $inStock ? __('In Stock') : __('Out of Stock');
        $stockBadgeClass = $inStock ? 'bg-success' : 'bg-danger';
        $hasVendor = $merchant && $merchant->user_id > 0;

        if ($merchant) {
            $priceFormatted = method_exists($merchant, 'showPrice') ? $merchant->showPrice() : \App\Models\Product::convertPrice($merchant->price);
            $previousPrice = $merchant->previous_price ?? 0;
            $previousPriceFormatted = $previousPrice > 0 ? \App\Models\Product::convertPrice($previousPrice) : '';
        } else {
            $priceFormatted = $actualProduct->showPrice();
            $previousPrice = $actualProduct->previous_price ?? 0;
            $previousPriceFormatted = $previousPrice > 0 ? $actualProduct->showPreviousPrice() : '';
        }

        $ratingsAvg = $actualProduct->ratings_avg_rating ?? 0;
        $ratingsCount = $actualProduct->ratings_count ?? 0;
        $minQty = max(1, (int)($merchant->minimum_qty ?? 1));
        $preordered = $merchant->preordered ?? false;
        $productType = $actualProduct->type ?? 'Physical';
        $affiliateProductType = $actualProduct->product_type ?? null;
        $affiliateLink = $actualProduct->affiliate_link ?? null;
        $wishlistUrl = route('user-wishlist-add', $actualProduct->id);
        $isInWishlist = isset($wishlistProductIds) ? $wishlistProductIds->contains($actualProduct->id) : false;
        $compareUrl = route('product.compare.add', $actualProduct->id);

        $useComponent = true;
    }

    $cardId = 'pc_' . ($productId ?? uniqid()) . '_' . ($merchantId ?? '0');
@endphp


{{-- ========================================
     SECTION 2: LIST VIEW
     ======================================== --}}
@if($layout === 'list')
<div class="col-sm-6 col-md-6 col-lg-12">
    <div class="single-product-list-view">
        {{-- Image Wrapper --}}
        <div class="img-wrapper">
            @if ($offPercentage && round($offPercentage) > 0)
                <span class="product-badge">-{{ round($offPercentage) }}%</span>
            @endif

            {{-- Wishlist Button --}}
            @auth
                <a href="javascript:;" class="wishlist" data-href="{{ $wishlistUrl }}">
                    <div class="add-to-wishlist-btn {{ $isInWishlist ? 'active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M11.9932 5.13581C9.9938 2.7984 6.65975 2.16964 4.15469 4.31001C1.64964 6.45038 1.29697 10.029 3.2642 12.5604C4.89982 14.6651 9.84977 19.1041 11.4721 20.5408C11.6536 20.7016 11.7444 20.7819 11.8502 20.8135C11.9426 20.8411 12.0437 20.8411 12.1361 20.8135C12.2419 20.7819 12.3327 20.7016 12.5142 20.5408C14.1365 19.1041 19.0865 14.6651 20.7221 12.5604C22.6893 10.029 22.3797 6.42787 19.8316 4.31001C17.2835 2.19216 13.9925 2.7984 11.9932 5.13581Z"
                                stroke="#030712" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </a>
            @else
                <a href="{{ route('user.login') }}">
                    <div class="add-to-wishlist-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd"
                                d="M11.9932 5.13581C9.9938 2.7984 6.65975 2.16964 4.15469 4.31001C1.64964 6.45038 1.29697 10.029 3.2642 12.5604C4.89982 14.6651 9.84977 19.1041 11.4721 20.5408C11.6536 20.7016 11.7444 20.7819 11.8502 20.8135C11.9426 20.8411 12.0437 20.8411 12.1361 20.8135C12.2419 20.7819 12.3327 20.7016 12.5142 20.5408C14.1365 19.1041 19.0865 14.6651 20.7221 12.5604C22.6893 10.029 22.3797 6.42787 19.8316 4.31001C17.2835 2.19216 13.9925 2.7984 11.9932 5.13581Z"
                                stroke="#030712" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </a>
            @endauth

            <img class="product-img" src="{{ $photo }}" alt="{{ $productName }}"
                 onerror="this.onerror=null; this.src='{{ $defaultImage }}';">
        </div>

        {{-- Details Wrapper --}}
        <div class="details-wrapper">
            <a href="{{ $productUrl }}" class="product-title">{{ $productName }}</a>

            {{-- Product Info Badges --}}
            <div class="product-info-badges my-2">
                @if($sku)
                    <span class="badge bg-light text-dark me-1">
                        <i class="fas fa-barcode me-1"></i>{{ $sku }}
                    </span>
                @endif

                @if($brandName)
                    <span class="badge bg-secondary me-1">{{ $brandName }}</span>
                @endif

                @if($qualityBrandName)
                    <span class="badge bg-info text-dark me-1">
                        @if($qualityBrandLogo)
                            <img src="{{ $qualityBrandLogo }}" alt="" style="height: 14px;" class="me-1">
                        @endif
                        {{ $qualityBrandName }}
                    </span>
                @endif

                @if($vendorName)
                    <span class="badge bg-primary me-1">
                        <i class="fas fa-store me-1"></i>{{ $vendorName }}
                    </span>
                @endif

                <span class="badge {{ $stockBadgeClass }}">{{ $stockText }}</span>
            </div>

            {{-- Rating --}}
            @if($ratingsCount > 0)
                <div class="rating-stars mb-2">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="{{ $i <= round($ratingsAvg) ? 'fas' : 'far' }} fa-star text-warning"></i>
                    @endfor
                    <span class="ms-1">({{ $ratingsCount }})</span>
                </div>
            @endif

            {{-- Price --}}
            <div class="product-price-wrap">
                <span class="current-price">{{ $priceFormatted }}</span>
                @if($previousPrice > 0 && $offPercentage > 0)
                    <span class="old-price text-muted text-decoration-line-through ms-2">
                        {{ $previousPriceFormatted }}
                    </span>
                @endif
            </div>

            {{-- Add to Cart --}}
            <div class="mt-3">
                @if($productType !== 'Listing' && $affiliateProductType !== 'affiliate')
                    @if($inStock && $hasVendor && $merchantId)
                        <button class="template-btn cart-btn m-cart-add"
                            data-product-id="{{ $productId }}"
                            data-merchant-product-id="{{ $merchantId }}"
                            data-vendor-id="{{ $vendorId }}"
                            data-min-qty="{{ $minQty }}"
                            data-stock="{{ $stockQty }}"
                            data-preordered="{{ $preordered ? '1' : '0' }}">
                            <i class="fas fa-cart-plus me-1"></i> @lang('Add to Cart')
                        </button>
                    @else
                        <button class="template-btn btn-secondary" disabled>
                            <i class="fas fa-times me-1"></i> @lang('Out of Stock')
                        </button>
                    @endif
                @elseif($affiliateProductType === 'affiliate' && $affiliateLink)
                    <a href="{{ $affiliateLink }}" target="_blank" class="template-btn">
                        <i class="fas fa-external-link-alt me-1"></i> @lang('Buy Now')
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>


{{-- ========================================
     SECTION 3: GRID VIEW
     ======================================== --}}
@else
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
                    data-href="{{ $wishlistUrl }}">
                    <i class="{{ $isInWishlist ? 'fas' : 'far' }} fa-heart"></i>
                </button>
            @else
                <a href="{{ route('user.login') }}" class="m-product-card__wishlist">
                    <i class="far fa-heart"></i>
                </a>
            @endauth

            {{-- Product Image --}}
            <a href="{{ $productUrl }}" class="m-product-card__image-link">
                <img src="{{ $photo }}"
                     alt="{{ $productName }}"
                     class="m-product-card__img active"
                     loading="lazy"
                     onerror="this.onerror=null; this.src='{{ $defaultImage }}';">
            </a>

            {{-- Quick Actions --}}
            @if ($productType !== 'Listing')
                <div class="m-product-card__actions">
                    {{-- Compare --}}
                    <button type="button" class="m-product-card__action compare_product"
                        data-href="{{ $compareUrl }}"
                        title="@lang('Compare')">
                        <i class="fas fa-exchange-alt"></i>
                    </button>

                    {{-- Quick View --}}
                    <a href="{{ $productUrl }}" class="m-product-card__action" title="@lang('View')">
                        <i class="far fa-eye"></i>
                    </a>
                </div>
            @endif
        </div>

        {{-- Content Section --}}
        <div class="m-product-card__content">
            {{-- Product Name --}}
            <h6 class="m-product-card__title">
                <a href="{{ $productUrl }}">{{ Str::limit($productName, 50) }}</a>
            </h6>

            {{-- Product Info: SKU, Vendor, Brand --}}
            @if($useComponent && isset($actualProduct))
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
            @else
                <div class="m-product-card__info">
                    @if($sku)
                        <span class="m-product-card__sku">{{ $sku }}</span>
                    @endif
                    @if($vendorName)
                        <span class="m-product-card__vendor">
                            <i class="fas fa-store"></i> {{ $vendorName }}
                        </span>
                    @endif
                    @if($qualityBrandName)
                        <span class="m-product-card__quality">{{ $qualityBrandName }}</span>
                    @endif
                </div>
            @endif

            {{-- Price --}}
            <div class="m-product-card__price">
                <span class="m-product-card__price-current">{{ $priceFormatted }}</span>
                @if($previousPrice > 0 && $offPercentage > 0)
                    <span class="m-product-card__price-old">{{ $previousPriceFormatted }}</span>
                @endif
            </div>

            {{-- Rating --}}
            <div class="m-product-card__rating">
                <i class="fas fa-star"></i>
                <span>{{ number_format($ratingsAvg, 1) }}</span>
                <span class="m-product-card__rating-count">({{ $ratingsCount }})</span>
            </div>

            {{-- Add to Cart --}}
            @if ($productType !== 'Listing' && $affiliateProductType !== 'affiliate')
                @if ($inStock && $hasVendor && $merchantId)
                    <button type="button"
                        class="m-product-card__cart-btn m-cart-add"
                        data-merchant-product-id="{{ $merchantId }}"
                        data-vendor-id="{{ $vendorId }}"
                        data-product-id="{{ $productId }}"
                        data-min-qty="{{ $minQty }}"
                        data-stock="{{ $stockQty }}"
                        data-preordered="{{ $preordered ? '1' : '0' }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span>@lang('Add to Cart')</span>
                    </button>
                @else
                    <button type="button" class="m-product-card__cart-btn m-product-card__cart-btn--disabled" disabled>
                        <i class="fas fa-ban"></i>
                        <span>@lang('Out of Stock')</span>
                    </button>
                @endif
            @elseif ($affiliateProductType === 'affiliate' && $affiliateLink)
                <a href="{{ $affiliateLink }}" class="m-product-card__cart-btn" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>@lang('Buy Now')</span>
                </a>
            @endif
        </div>
    </div>
</div>
@endif
