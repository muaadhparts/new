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
        $catalogItemId = $card->catalogItemId;
        $merchantItemId = $card->merchantItemId;
        $merchantUserId = $card->merchantId;
        $catalogItemName = $card->catalogItemName;
        $catalogItemUrl = $card->detailsUrl;
        $photo = $card->photo;
        $part_number = $card->part_number;
        $brandName = $card->brandName;
        $brandLogo = $card->brandLogo ?? null;
        $qualityBrandName = $card->qualityBrandName;
        $qualityBrandLogo = $card->qualityBrandLogo ?? null;
        $merchantName = $card->merchantName;
        $offPercentage = $card->offPercentage;
        $inStock = $card->inStock;
        $stockQty = $card->stock;
        $stockText = $card->stockText ?? ($inStock ? __('In Stock') : __('Out of Stock'));
        $hasMerchant = $card->hasMerchant;
        $priceFormatted = $card->priceFormatted;
        $previousPrice = $card->previousPrice;
        $previousPriceFormatted = $card->previousPriceFormatted;
        $ratingsAvg = $card->catalogReviewsAvg;
        $ratingsCount = $card->catalogReviewsCount;
        $minQty = $card->minQty;
        $preordered = $card->preordered ?? false;
        $catalogItemType = 'Physical'; // All catalog items are Physical in this EPC
        $affiliateCatalogItemType = $card->itemType ?? null;
        $affiliateLink = $card->affiliateLink ?? null;
        $favoriteUrl = $card->favoriteUrl ?? null;
        $isInFavorites = $card->isInFavorites ?? false;
        $compareUrl = $card->compareUrl;
    } else {
        // === Source: CatalogItem + MerchantItem ===
        $isMerchantItem = $catalogItem instanceof \App\Models\MerchantItem;

        if ($isMerchantItem) {
            $merchantItem = $catalogItem;
            $actualCatalogItem = $catalogItem->catalogItem;
        } else {
            $actualCatalogItem = $catalogItem;
            $merchantItem = $mp ?? $catalogItem->best_merchant_item ?? null;
        }

        // Skip if catalog item no longer exists
        if (!$actualCatalogItem) {
            return;
        }

        $catalogItemId = $actualCatalogItem->id;
        $merchantItemId = $merchantItem->id ?? null;
        $merchantUserId = $merchantItem->user_id ?? null;
        $catalogItemName = $actualCatalogItem->showName();

        $catalogItemSlug = $isMerchantItem ? optional($actualCatalogItem)->slug : $catalogItem->slug;
        $catalogItemUrl = $merchantItem && $catalogItemSlug
            ? route('front.catalog-item', ['slug' => $catalogItemSlug, 'merchant_id' => $merchantItem->user_id, 'merchant_item_id' => $merchantItem->id])
            : ($catalogItemSlug ? route('front.catalog-item.legacy', $catalogItemSlug) : '#');

        $mainPhoto = $actualCatalogItem->photo ?? null;
        $photo = $mainPhoto
            ? (filter_var($mainPhoto, FILTER_VALIDATE_URL) ? $mainPhoto : Storage::url($mainPhoto))
            : $defaultImage;

        $part_number = $actualCatalogItem->part_number ?? null;
        $brandName = $actualCatalogItem->brand?->localized_name;
        $brandLogo = $actualCatalogItem->brand?->photo_url;
        $qualityBrandName = $merchantItem?->qualityBrand?->localized_name;
        $qualityBrandLogo = $merchantItem?->qualityBrand?->logo_url;
        $merchantName = $merchantItem?->user ? getLocalizedShopName($merchantItem->user) : null;

        $offPercentage = $merchantItem && method_exists($merchantItem, 'offPercentage')
            ? $merchantItem->offPercentage()
            : ($actualCatalogItem && method_exists($actualCatalogItem, 'offPercentage') ? $actualCatalogItem->offPercentage() : 0);

        $stockQty = $merchantItem ? (int)($merchantItem->stock ?? 0) : 0;
        $inStock = $stockQty > 0 || ($merchantItem && $merchantItem->preordered);
        $stockText = $inStock ? __('In Stock') : __('Out of Stock');
        $hasMerchant = $merchantItem && $merchantItem->user_id > 0;

        if ($merchantItem) {
            $priceFormatted = method_exists($merchantItem, 'showPrice') ? $merchantItem->showPrice() : \App\Models\CatalogItem::convertPrice($merchantItem->price);
            $previousPrice = $merchantItem->previous_price ?? 0;
            $previousPriceFormatted = $previousPrice > 0 ? \App\Models\CatalogItem::convertPrice($previousPrice) : '';
        } else {
            $priceFormatted = $actualCatalogItem->showPrice();
            $previousPrice = $actualCatalogItem->previous_price ?? 0;
            $previousPriceFormatted = $previousPrice > 0 ? $actualCatalogItem->showPreviousPrice() : '';
        }

        $ratingsAvg = $actualCatalogItem->catalog_reviews_avg_rating ?? 0;
        $ratingsCount = $actualCatalogItem->catalog_reviews_count ?? 0;
        $minQty = max(1, (int)($merchantItem->minimum_qty ?? 1));
        $preordered = $merchantItem->preordered ?? false;
        $catalogItemType = 'Physical'; // All catalog items are Physical
        // item_type and affiliate_link are now on merchant_items, not catalog_items
        $affiliateCatalogItemType = $merchantItem->item_type ?? null;
        $affiliateLink = $merchantItem->affiliate_link ?? null;
        $favoriteUrl = route('user-favorite-add', $actualCatalogItem->id);
        $isInFavorites = isset($favoriteProductIds) && $favoriteProductIds->contains($actualCatalogItem->id);
        $compareUrl = route('catalog-item.compare.add', $actualCatalogItem->id);
    }

    $cardId = 'ci_' . ($catalogItemId ?? uniqid()) . '_' . ($merchantItemId ?? '0');
    $cardClass = $layout === 'list' ? 'catalogItem-card catalogItem-card--list' : 'catalogItem-card';
@endphp


{{-- ========================================
     LIST VIEW
     ======================================== --}}
@if($layout === 'list')
<div class="col-12">
    <div class="{{ $cardClass }}" id="{{ $cardId }}">
        {{-- Media Section --}}
        <div class="catalogItem-card__media">
            @if ($offPercentage && round($offPercentage) > 0)
                <span class="catalogItem-card__badge catalogItem-card__badge--discount">
                    -{{ round($offPercentage) }}%
                </span>
            @endif

            @if (!$inStock)
                <span class="catalogItem-card__badge catalogItem-card__badge--stock">
                    {{ __('Out of Stock') }}
                </span>
            @endif

            @auth
                <a href="javascript:;" class="catalogItem-card__favorite favorite {{ $isInFavorites ? 'active' : '' }}" data-href="{{ $favoriteUrl }}">
                    <i class="{{ $isInFavorites ? 'fas' : 'far' }} fa-heart"></i>
                </a>
            @else
                <a href="{{ route('user.login') }}" class="catalogItem-card__favorite">
                    <i class="far fa-heart"></i>
                </a>
            @endauth

            <a href="{{ $catalogItemUrl }}" class="catalogItem-card__media-link">
                <img src="{{ $photo }}" alt="{{ $catalogItemName }}" class="catalogItem-card__img"
                     loading="lazy" onerror="this.onerror=null; this.src='{{ $defaultImage }}';">
            </a>
        </div>

        {{-- Content Section --}}
        <div class="catalogItem-card__content">
            <h6 class="catalogItem-card__name">
                <a href="{{ $catalogItemUrl }}">{{ $catalogItemName }}</a>
            </h6>

            {{-- Catalog Item Info Badges --}}
            <div class="catalogItem-card__info-badges">
                @if($part_number)
                    <span class="badge bg-light text-dark">
                        <i class="fas fa-barcode me-1"></i>{{ $part_number }}
                    </span>
                @endif
                @if($brandName)
                    <span class="badge bg-secondary">
                        @if($brandLogo)
                            <img src="{{ $brandLogo }}" alt="" class="catalogItem-card__brand-logo me-1">
                        @endif
                        {{ $brandName }}
                    </span>
                @endif
                @if($qualityBrandName)
                    <span class="badge bg-info text-dark">
                        @if($qualityBrandLogo)
                            <img src="{{ $qualityBrandLogo }}" alt="" class="catalogItem-card__quality-logo me-1">
                        @endif
                        {{ $qualityBrandName }}
                    </span>
                @endif
                @if($merchantName)
                    <span class="badge bg-primary">
                        <i class="fas fa-store me-1"></i>{{ $merchantName }}
                    </span>
                @endif
                <span class="badge {{ $inStock ? 'bg-success' : 'bg-danger' }}">{{ $stockText }}</span>
            </div>

            {{-- Rating --}}
            @if($ratingsCount > 0)
                <div class="catalogItem-card__rating">
                    <div class="catalogItem-card__rating-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="{{ $i <= round($ratingsAvg) ? 'fas' : 'far' }} fa-star"></i>
                        @endfor
                    </div>
                    <span class="catalogItem-card__rating-count">({{ $ratingsCount }})</span>
                </div>
            @endif

            {{-- Price --}}
            <div class="catalogItem-card__price">
                <span class="catalogItem-card__price-current">{{ $priceFormatted }}</span>
                @if($previousPrice > 0 && $offPercentage > 0)
                    <span class="catalogItem-card__price-old">{{ $previousPriceFormatted }}</span>
                @endif
            </div>

            {{-- Shipping Quote Button --}}
            @if($merchantUserId)
                <x-shipping-quote-button :merchant-user-id="$merchantUserId" :catalog-item-name="$catalogItemName" class="mt-2" />
            @endif

            {{-- Add to Cart --}}
            @if($affiliateCatalogItemType !== 'affiliate')
                @if($inStock && $hasMerchant && $merchantItemId)
                    <button type="button" class="catalogItem-card__cart-btn m-cart-add"
                        data-catalog-item-id="{{ $catalogItemId }}"
                        data-merchant-item-id="{{ $merchantItemId }}"
                        data-merchant-user-id="{{ $merchantUserId }}"
                        data-min-qty="{{ $minQty }}"
                        data-stock="{{ $stockQty }}"
                        data-preordered="{{ $preordered ? '1' : '0' }}">
                        <i class="fas fa-cart-plus"></i>
                        <span>@lang('Add to Cart')</span>
                    </button>
                @else
                    <button type="button" class="catalogItem-card__cart-btn catalogItem-card__cart-btn--disabled" disabled>
                        <i class="fas fa-times"></i>
                        <span>@lang('Out of Stock')</span>
                    </button>
                @endif
            @elseif($affiliateCatalogItemType === 'affiliate' && $affiliateLink)
                <a href="{{ $affiliateLink }}" target="_blank" class="catalogItem-card__cart-btn">
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
        <div class="catalogItem-card__media">
            @if ($offPercentage && round($offPercentage) > 0)
                <span class="catalogItem-card__badge catalogItem-card__badge--discount">
                    -{{ round($offPercentage) }}%
                </span>
            @endif

            @if (!$inStock)
                <span class="catalogItem-card__badge catalogItem-card__badge--stock">
                    {{ __('Out of Stock') }}
                </span>
            @endif

            @auth
                @if(isset($favorite) && $favorite && isset($favoriteId))
                    {{-- Delete button for favorites page --}}
                    <button type="button" class="catalogItem-card__delete removefavorite"
                        data-href="{{ route('user-favorite-remove', $favoriteId) }}"
                        name="@lang('Remove from Favorites')">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                @else
                    <button type="button" class="catalogItem-card__favorite favorite {{ $isInFavorites ? 'active' : '' }}" data-href="{{ $favoriteUrl }}">
                        <i class="{{ $isInFavorites ? 'fas' : 'far' }} fa-heart"></i>
                    </button>
                @endif
            @else
                <a href="{{ route('user.login') }}" class="catalogItem-card__favorite">
                    <i class="far fa-heart"></i>
                </a>
            @endauth

            <a href="{{ $catalogItemUrl }}" class="catalogItem-card__media-link">
                <img src="{{ $photo }}" alt="{{ $catalogItemName }}" class="catalogItem-card__img"
                     loading="lazy" onerror="this.onerror=null; this.src='{{ $defaultImage }}';">
            </a>

            <div class="catalogItem-card__actions">
                <button type="button" class="catalogItem-card__action compare_product"
                    data-href="{{ $compareUrl }}" name="@lang('Compare')">
                    <i class="fas fa-exchange-alt"></i>
                </button>
                <a href="{{ $catalogItemUrl }}" class="catalogItem-card__action" name="@lang('View')">
                    <i class="far fa-eye"></i>
                </a>
            </div>
        </div>

        {{-- Content Section --}}
        <div class="catalogItem-card__content">
            <h6 class="catalogItem-card__name">
                <a href="{{ $catalogItemUrl }}">{{ Str::limit($catalogItemName, 50) }}</a>
            </h6>

            {{-- Catalog Item Info --}}
            <div class="catalogItem-card__info">
                @if($part_number)
                    <span class="catalogItem-card__sku">{{ $part_number }}</span>
                @endif
                @if($brandName)
                    <span class="catalogItem-card__brand">
                        @if($brandLogo)
                            <img src="{{ $brandLogo }}" alt="" class="catalogItem-card__brand-logo">
                        @endif
                        {{ $brandName }}
                    </span>
                @endif
                @if($qualityBrandName)
                    <span class="catalogItem-card__quality">
                        @if($qualityBrandLogo)
                            <img src="{{ $qualityBrandLogo }}" alt="" class="catalogItem-card__quality-logo">
                        @endif
                        {{ $qualityBrandName }}
                    </span>
                @endif
                @if($merchantName)
                    <span class="catalogItem-card__merchant">
                        <i class="fas fa-store"></i> {{ $merchantName }}
                    </span>
                @endif
                <span class="catalogItem-card__stock {{ $inStock ? 'catalogItem-card__stock--in' : 'catalogItem-card__stock--out' }}">
                    {{ $stockText }}
                </span>
            </div>

            {{-- Price --}}
            <div class="catalogItem-card__price">
                <span class="catalogItem-card__price-current">{{ $priceFormatted }}</span>
                @if($previousPrice > 0 && $offPercentage > 0)
                    <span class="catalogItem-card__price-old">{{ $previousPriceFormatted }}</span>
                @endif
            </div>

            {{-- Rating --}}
            <div class="catalogItem-card__rating">
                <i class="fas fa-star"></i>
                <span>{{ number_format($ratingsAvg, 1) }}</span>
                <span class="catalogItem-card__rating-count">({{ $ratingsCount }})</span>
            </div>

            {{-- Shipping Quote Button --}}
            @if($merchantUserId)
                <x-shipping-quote-button :merchant-user-id="$merchantUserId" :catalog-item-name="$catalogItemName" class="mt-2" />
            @endif

            {{-- Add to Cart --}}
            @if ($affiliateCatalogItemType !== 'affiliate')
                @if ($inStock && $hasMerchant && $merchantItemId)
                    <button type="button" class="catalogItem-card__cart-btn m-cart-add"
                        data-merchant-item-id="{{ $merchantItemId }}"
                        data-merchant-user-id="{{ $merchantUserId }}"
                        data-catalog-item-id="{{ $catalogItemId }}"
                        data-min-qty="{{ $minQty }}"
                        data-stock="{{ $stockQty }}"
                        data-preordered="{{ $preordered ? '1' : '0' }}">
                        <i class="fas fa-shopping-cart"></i>
                        <span>@lang('Add to Cart')</span>
                    </button>
                @else
                    <button type="button" class="catalogItem-card__cart-btn catalogItem-card__cart-btn--disabled" disabled>
                        <i class="fas fa-ban"></i>
                        <span>@lang('Out of Stock')</span>
                    </button>
                @endif
            @elseif ($affiliateCatalogItemType === 'affiliate' && $affiliateLink)
                <a href="{{ $affiliateLink }}" class="catalogItem-card__cart-btn" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>@lang('Buy Now')</span>
                </a>
            @endif
        </div>
    </div>
</div>
@endif
