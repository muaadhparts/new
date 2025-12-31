{{--
    Unified Catalog Item Card Component
    ====================================
    Single source of truth for all catalog item cards.

    Data sources:
    1. CatalogItemCardDTO: $card (from category, search-results, catalog items)
    2. CatalogItem + MerchantItem: $catalogItem, $mp (from favorites, merchant, related)

    Layout: $layout = 'grid' (default) | 'list'

    CSS: public/assets/css/catalog-item-card.css
--}}

@php
    // ========================================
    // Data Normalization
    // ========================================
    $layout = $layout ?? 'grid';
    $defaultImage = asset('assets/images/noimage.png');

    if (isset($card) && $card instanceof \App\DataTransferObjects\CatalogItemCardDTO) {
        // === Source: CatalogItemCardDTO ===
        $productId = $card->productId;
        $merchantId = $card->merchantId;
        $vendorId = $card->vendorId;
        $productName = $card->productName;
        $productUrl = $card->detailsUrl;
        $photo = $card->photo;
        $sku = $card->sku;
        $brandName = $card->brandName;
        $brandLogo = $card->brandLogo ?? null;
        $qualityBrandName = $card->qualityBrandName;
        $qualityBrandLogo = $card->qualityBrandLogo ?? null;
        $vendorName = $card->vendorName;
        $offPercentage = $card->offPercentage;
        $inStock = $card->inStock;
        $stockQty = $card->stock;
        $stockText = $card->stockText ?? ($inStock ? __('In Stock') : __('Out of Stock'));
        $hasVendor = $card->hasVendor;
        $priceFormatted = $card->priceFormatted;
        $previousPrice = $card->previousPrice;
        $previousPriceFormatted = $card->previousPriceFormatted;
        $ratingsAvg = $card->catalogReviewsAvg;
        $ratingsCount = $card->catalogReviewsCount;
        $minQty = $card->minQty;
        $preordered = $card->preordered ?? false;
        $productType = $card->type;
        $affiliateProductType = $card->productType ?? null;
        $affiliateLink = $card->affiliateLink ?? null;
        $favoriteUrl = $card->favoriteUrl ?? null;
        $isInFavorites = $card->isInFavorites ?? false;
        $compareUrl = $card->compareUrl;
    } else {
        // === Source: CatalogItem + MerchantItem ===
        $isMerchantItem = $catalogItem instanceof \App\Models\MerchantItem;

        if ($isMerchantItem) {
            $merchant = $catalogItem;
            $actualCatalogItem = $catalogItem->catalogItem;
        } else {
            $actualCatalogItem = $catalogItem;
            $merchant = $mp ?? $catalogItem->best_merchant_item ?? null;
        }

        $productId = $actualCatalogItem->id ?? null;
        $merchantId = $merchant->id ?? null;
        $vendorId = $merchant->user_id ?? null;
        $productName = $actualCatalogItem->showName();

        $productSlug = $isMerchantItem ? optional($actualCatalogItem)->slug : $catalogItem->slug;
        $productUrl = $merchant && $productSlug
            ? route('front.catalog-item', ['slug' => $productSlug, 'vendor_id' => $merchant->user_id, 'merchant_item_id' => $merchant->id])
            : ($productSlug ? route('front.catalog-item.legacy', $productSlug) : '#');

        $mainPhoto = $actualCatalogItem->photo ?? null;
        $photo = $mainPhoto
            ? (filter_var($mainPhoto, FILTER_VALIDATE_URL) ? $mainPhoto : Storage::url($mainPhoto))
            : $defaultImage;

        $sku = $actualCatalogItem->sku ?? null;
        $brandName = $actualCatalogItem->brand?->localized_name;
        $brandLogo = $actualCatalogItem->brand?->photo_url;
        $qualityBrandName = $merchant?->qualityBrand?->localized_name;
        $qualityBrandLogo = $merchant?->qualityBrand?->logo_url;
        $vendorName = $merchant?->user ? getLocalizedShopName($merchant->user) : null;

        $offPercentage = $merchant && method_exists($merchant, 'offPercentage')
            ? $merchant->offPercentage()
            : ($actualCatalogItem && method_exists($actualCatalogItem, 'offPercentage') ? $actualCatalogItem->offPercentage() : 0);

        $stockQty = $merchant ? (int)($merchant->stock ?? 0) : 0;
        $inStock = $stockQty > 0 || ($merchant && $merchant->preordered);
        $stockText = $inStock ? __('In Stock') : __('Out of Stock');
        $hasVendor = $merchant && $merchant->user_id > 0;

        if ($merchant) {
            $priceFormatted = method_exists($merchant, 'showPrice') ? $merchant->showPrice() : \App\Models\CatalogItem::convertPrice($merchant->price);
            $previousPrice = $merchant->previous_price ?? 0;
            $previousPriceFormatted = $previousPrice > 0 ? \App\Models\CatalogItem::convertPrice($previousPrice) : '';
        } else {
            $priceFormatted = $actualCatalogItem->showPrice();
            $previousPrice = $actualCatalogItem->previous_price ?? 0;
            $previousPriceFormatted = $previousPrice > 0 ? $actualCatalogItem->showPreviousPrice() : '';
        }

        $ratingsAvg = $actualCatalogItem->catalog_reviews_avg_rating ?? 0;
        $ratingsCount = $actualCatalogItem->catalog_reviews_count ?? 0;
        $minQty = max(1, (int)($merchant->minimum_qty ?? 1));
        $preordered = $merchant->preordered ?? false;
        $productType = $actualCatalogItem->type ?? 'Physical';
        // product_type and affiliate_link are now on merchant_items, not catalog_items
        $affiliateProductType = $merchant->product_type ?? null;
        $affiliateLink = $merchant->affiliate_link ?? null;
        $favoriteUrl = route('user-favorite-add', $actualCatalogItem->id);
        $isInFavorites = isset($favoriteProductIds) && $favoriteProductIds->contains($actualCatalogItem->id);
        $compareUrl = route('catalog-item.compare.add', $actualCatalogItem->id);
    }

    $cardId = 'pc_' . ($productId ?? uniqid()) . '_' . ($merchantId ?? '0');
    $cardClass = $layout === 'list' ? 'product-card product-card--list' : 'product-card';
@endphp


{{-- ========================================
     LIST VIEW
     ======================================== --}}
@if($layout === 'list')
<div class="col-12">
    <div class="{{ $cardClass }}" id="{{ $cardId }}">
        {{-- Media Section --}}
        <div class="product-card__media">
            @if ($offPercentage && round($offPercentage) > 0)
                <span class="product-card__badge product-card__badge--discount">
                    -{{ round($offPercentage) }}%
                </span>
            @endif

            @if (!$inStock)
                <span class="product-card__badge product-card__badge--stock">
                    {{ __('Out of Stock') }}
                </span>
            @endif

            @auth
                <a href="javascript:;" class="product-card__favorite favorite {{ $isInFavorites ? 'active' : '' }}" data-href="{{ $favoriteUrl }}">
                    <i class="{{ $isInFavorites ? 'fas' : 'far' }} fa-heart"></i>
                </a>
            @else
                <a href="{{ route('user.login') }}" class="product-card__favorite">
                    <i class="far fa-heart"></i>
                </a>
            @endauth

            <a href="{{ $productUrl }}" class="product-card__media-link">
                <img src="{{ $photo }}" alt="{{ $productName }}" class="product-card__img"
                     loading="lazy" onerror="this.onerror=null; this.src='{{ $defaultImage }}';">
            </a>
        </div>

        {{-- Content Section --}}
        <div class="product-card__content">
            <h6 class="product-card__title">
                <a href="{{ $productUrl }}">{{ $productName }}</a>
            </h6>

            {{-- Product Info Badges --}}
            <div class="product-card__info-badges">
                @if($sku)
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-barcode me-1"></i>{{ $sku }}
                    </span>
                @endif
                @if($brandName)
                    <span class="badge bg-secondary">
                        @if($brandLogo)
                            <img src="{{ $brandLogo }}" alt="" class="product-card__brand-logo me-1">
                        @endif
                        {{ $brandName }}
                    </span>
                @endif
                @if($qualityBrandName)
                    <span class="badge bg-info text-dark">
                        @if($qualityBrandLogo)
                            <img src="{{ $qualityBrandLogo }}" alt="" class="product-card__quality-logo me-1">
                        @endif
                        {{ $qualityBrandName }}
                    </span>
                @endif
                @if($vendorName)
                    <span class="badge bg-primary">
                        <i class="fas fa-store me-1"></i>{{ $vendorName }}
                    </span>
                @endif
                <span class="badge {{ $inStock ? 'bg-success' : 'bg-danger' }}">{{ $stockText }}</span>
            </div>

            {{-- Rating --}}
            @if($ratingsCount > 0)
                <div class="product-card__rating">
                    <div class="product-card__rating-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="{{ $i <= round($ratingsAvg) ? 'fas' : 'far' }} fa-star"></i>
                        @endfor
                    </div>
                    <span class="product-card__rating-count">({{ $ratingsCount }})</span>
                </div>
            @endif

            {{-- Price --}}
            <div class="product-card__price">
                <span class="product-card__price-current">{{ $priceFormatted }}</span>
                @if($previousPrice > 0 && $offPercentage > 0)
                    <span class="product-card__price-old">{{ $previousPriceFormatted }}</span>
                @endif
            </div>

            {{-- Shipping Quote Button --}}
            @if($productType === 'Physical' && $vendorId)
                <x-shipping-quote-button :vendor-id="$vendorId" :product-name="$productName" class="mt-2" />
            @endif

            {{-- Add to Cart --}}
            @if($productType !== 'Listing' && $affiliateProductType !== 'affiliate')
                @if($inStock && $hasVendor && $merchantId)
                    <button type="button" class="product-card__cart-btn m-cart-add"
                        data-catalog-item-id="{{ $productId }}"
                        data-merchant-item-id="{{ $merchantId }}"
                        data-vendor-id="{{ $vendorId }}"
                        data-min-qty="{{ $minQty }}"
                        data-stock="{{ $stockQty }}"
                        data-preordered="{{ $preordered ? '1' : '0' }}">
                        <i class="fas fa-cart-plus"></i>
                        <span>@lang('Add to Cart')</span>
                    </button>
                @else
                    <button type="button" class="product-card__cart-btn product-card__cart-btn--disabled" disabled>
                        <i class="fas fa-times"></i>
                        <span>@lang('Out of Stock')</span>
                    </button>
                @endif
            @elseif($affiliateProductType === 'affiliate' && $affiliateLink)
                <a href="{{ $affiliateLink }}" target="_blank" class="product-card__cart-btn">
                    <i class="fas fa-external-link-alt"></i>
                    <span>@lang('Buy Now')</span>
                </a>
            @endif
        </div>
    </div>
</div>


{{-- ========================================
     GRID VIEW
     ======================================== --}}
@else
<div class="{{ $class ?? 'col-6 col-md-4 col-lg-3' }}">
    <div class="{{ $cardClass }}" id="{{ $cardId }}">
        {{-- Media Section --}}
        <div class="product-card__media">
            @if ($offPercentage && round($offPercentage) > 0)
                <span class="product-card__badge product-card__badge--discount">
                    -{{ round($offPercentage) }}%
                </span>
            @endif

            @if (!$inStock)
                <span class="product-card__badge product-card__badge--stock">
                    {{ __('Out of Stock') }}
                </span>
            @endif

            @auth
                @if(isset($favorite) && $favorite && isset($favoriteId))
                    {{-- Delete button for favorites page --}}
                    <button type="button" class="product-card__delete removefavorite"
                        data-href="{{ route('user-favorite-remove', $favoriteId) }}"
                        title="@lang('Remove from Favorites')">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                @else
                    <button type="button" class="product-card__favorite favorite {{ $isInFavorites ? 'active' : '' }}" data-href="{{ $favoriteUrl }}">
                        <i class="{{ $isInFavorites ? 'fas' : 'far' }} fa-heart"></i>
                    </button>
                @endif
            @else
                <a href="{{ route('user.login') }}" class="product-card__favorite">
                    <i class="far fa-heart"></i>
                </a>
            @endauth

            <a href="{{ $productUrl }}" class="product-card__media-link">
                <img src="{{ $photo }}" alt="{{ $productName }}" class="product-card__img"
                     loading="lazy" onerror="this.onerror=null; this.src='{{ $defaultImage }}';">
            </a>

            @if ($productType !== 'Listing')
                <div class="product-card__actions">
                    <button type="button" class="product-card__action compare_product"
                        data-href="{{ $compareUrl }}" title="@lang('Compare')">
                        <i class="fas fa-exchange-alt"></i>
                    </button>
                    <a href="{{ $productUrl }}" class="product-card__action" title="@lang('View')">
                        <i class="far fa-eye"></i>
                    </a>
                </div>
            @endif
        </div>

        {{-- Content Section --}}
        <div class="product-card__content">
            <h6 class="product-card__title">
                <a href="{{ $productUrl }}">{{ Str::limit($productName, 50) }}</a>
            </h6>

            {{-- Product Info --}}
            <div class="product-card__info">
                @if($sku)
                    <span class="product-card__sku">{{ $sku }}</span>
                @endif
                @if($brandName)
                    <span class="product-card__brand">
                        @if($brandLogo)
                            <img src="{{ $brandLogo }}" alt="" class="product-card__brand-logo">
                        @endif
                        {{ $brandName }}
                    </span>
                @endif
                @if($qualityBrandName)
                    <span class="product-card__quality">
                        @if($qualityBrandLogo)
                            <img src="{{ $qualityBrandLogo }}" alt="" class="product-card__quality-logo">
                        @endif
                        {{ $qualityBrandName }}
                    </span>
                @endif
                @if($vendorName)
                    <span class="product-card__vendor">
                        <i class="fas fa-store"></i> {{ $vendorName }}
                    </span>
                @endif
                <span class="product-card__stock {{ $inStock ? 'product-card__stock--in' : 'product-card__stock--out' }}">
                    {{ $stockText }}
                </span>
            </div>

            {{-- Price --}}
            <div class="product-card__price">
                <span class="product-card__price-current">{{ $priceFormatted }}</span>
                @if($previousPrice > 0 && $offPercentage > 0)
                    <span class="product-card__price-old">{{ $previousPriceFormatted }}</span>
                @endif
            </div>

            {{-- Rating --}}
            <div class="product-card__rating">
                <i class="fas fa-star"></i>
                <span>{{ number_format($ratingsAvg, 1) }}</span>
                <span class="product-card__rating-count">({{ $ratingsCount }})</span>
            </div>

            {{-- Shipping Quote Button --}}
            @if($productType === 'Physical' && $vendorId)
                <x-shipping-quote-button :vendor-id="$vendorId" :product-name="$productName" class="mt-2" />
            @endif

            {{-- Add to Cart --}}
            @if ($productType !== 'Listing' && $affiliateProductType !== 'affiliate')
                @if ($inStock && $hasVendor && $merchantId)
                    <button type="button" class="product-card__cart-btn m-cart-add"
                        data-merchant-item-id="{{ $merchantId }}"
                        data-vendor-id="{{ $vendorId }}"
                        data-catalog-item-id="{{ $productId }}"
                        data-min-qty="{{ $minQty }}"
                        data-stock="{{ $stockQty }}"
                        data-preordered="{{ $preordered ? '1' : '0' }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span>@lang('Add to Cart')</span>
                    </button>
                @else
                    <button type="button" class="product-card__cart-btn product-card__cart-btn--disabled" disabled>
                        <i class="fas fa-ban"></i>
                        <span>@lang('Out of Stock')</span>
                    </button>
                @endif
            @elseif ($affiliateProductType === 'affiliate' && $affiliateLink)
                <a href="{{ $affiliateLink }}" class="product-card__cart-btn" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>@lang('Buy Now')</span>
                </a>
            @endif
        </div>
    </div>
</div>
@endif
