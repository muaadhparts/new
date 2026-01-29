{{--
    Unified Catalog Item Card Component
    ====================================
    Single source of truth for all catalog item cards.

    Data source: CatalogItemCardDTO ($card)
    Layout: $layout = 'grid' (default) | 'list'
    CSS: public/assets/css/catalog-item-card.css
--}}

@php
    // ========================================
    // Data Normalization
    // ========================================
    $layout = $layout ?? 'grid';
    $defaultImage = asset('assets/images/noimage.png');

    if (isset($card) && $card instanceof \App\Domain\Catalog\DTOs\CatalogItemCardDTO) {
        // === Source: CatalogItemCardDTO ===
        $catalogItemId = $card->catalogItemId;
        $merchantItemId = $card->merchantItemId;
        $merchantUserId = $card->merchantId;
        $catalogItemName = $card->catalogItemName;
        $catalogItemUrl = $card->detailsUrl;
        $photo = $card->photo;
        $part_number = $card->part_number;
        // Vehicle fitment brands (multiple brands support)
        $fitmentBrands = $card->fitmentBrands ?? [];
        $fitmentCount = $card->fitmentCount ?? 0;
        $hasSingleBrand = $card->hasSingleBrand ?? false;
        $fitsMultipleBrands = $card->fitsMultipleBrands ?? false;
        $qualityBrandName = $card->qualityBrandName;
        $qualityBrandLogo = $card->qualityBrandLogo ?? null;
        $merchantName = $card->merchantName;
        $branchName = $card->branchName ?? null;
        $offPercentage = $card->offPercentage;
        $offPercentageFormatted = $card->offPercentageFormatted;
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
        // Offers count
        $offersCount = $card->offersCount ?? 1;
        $hasMultipleOffers = $card->hasMultipleOffers ?? false;
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
            @if ($offPercentageFormatted)
                <span class="catalogItem-card__badge catalogItem-card__badge--discount">
                    -{{ $offPercentageFormatted }}
                </span>
            @endif

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
                {{-- Vehicle Fitment Brands --}}
                @if($fitmentCount > 0)
                    <button type="button" class="catalog-btn catalog-btn-outline catalog-btn-sm fitment-details-btn"
                            data-catalog-item-id="{{ $catalogItemId }}"
                            data-part-number="{{ $part_number }}">
                        @if($hasSingleBrand && isset($fitmentBrands[0]))
                            @if($fitmentBrands[0]['logo'])
                                <img src="{{ $fitmentBrands[0]['logo'] }}" alt="" class="catalog-btn__logo">
                            @else
                                <i class="fas fa-car"></i>
                            @endif
                            <span>{{ $fitmentBrands[0]['name'] }}</span>
                        @else
                            <i class="fas fa-car"></i>
                            <span>{{ __('Fits') }}</span>
                            <span class="catalog-badge catalog-badge-sm">{{ $fitmentCount }}</span>
                        @endif
                    </button>
                @endif
            </div>

            {{-- Price --}}
            <div class="catalogItem-card__price">
                <span class="catalogItem-card__price-current">{{ $priceFormatted }}</span>
                @if($previousPrice > 0 && $offPercentage > 0)
                    <span class="catalogItem-card__price-old">{{ $previousPriceFormatted }}</span>
                @endif

                {{-- Offers Button (always show) --}}
                <button type="button" class="catalog-offers-btn"
                        data-catalog-item-id="{{ $catalogItemId }}"
                        data-part-number="{{ $part_number }}">
                    <i class="fas fa-tags"></i>
                    <span class="offers-count">{{ $offersCount }}</span>
                    @lang('offers')
                </button>
            </div>
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
            @if ($offPercentageFormatted)
                <span class="catalogItem-card__badge catalogItem-card__badge--discount">
                    -{{ $offPercentageFormatted }}
                </span>
            @endif

            {{-- Remove from Favorites Button --}}
            @if(isset($favorite) && $favorite && isset($favoriteId))
                <button type="button" class="catalogItem-card__delete removefavorite"
                        data-href="{{ route('user-favorite-remove', $favoriteId) }}"
                        title="@lang('Remove from Favorites')">
                    <i class="fas fa-times"></i>
                </button>
            @endif

            <a href="{{ $catalogItemUrl }}" class="catalogItem-card__media-link">
                <img src="{{ $photo }}" alt="{{ $catalogItemName }}" class="catalogItem-card__img"
                     loading="lazy" onerror="this.onerror=null; this.src='{{ $defaultImage }}';">
            </a>
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
                {{-- Vehicle Fitment Brands --}}
                @if($fitmentCount > 0)
                    <button type="button" class="catalog-btn catalog-btn-outline catalog-btn-sm fitment-details-btn"
                            data-catalog-item-id="{{ $catalogItemId }}"
                            data-part-number="{{ $part_number }}">
                        @if($hasSingleBrand && isset($fitmentBrands[0]))
                            @if($fitmentBrands[0]['logo'])
                                <img src="{{ $fitmentBrands[0]['logo'] }}" alt="" class="catalog-btn__logo">
                            @else
                                <i class="fas fa-car"></i>
                            @endif
                            <span>{{ $fitmentBrands[0]['name'] }}</span>
                        @else
                            <i class="fas fa-car"></i>
                            <span>{{ __('Fits') }}</span>
                            <span class="catalog-badge catalog-badge-sm">{{ $fitmentCount }}</span>
                        @endif
                    </button>
                @endif
            </div>

            {{-- Price --}}
            <div class="catalogItem-card__price">
                <span class="catalogItem-card__price-current">{{ $priceFormatted }}</span>
                @if($previousPrice > 0 && $offPercentage > 0)
                    <span class="catalogItem-card__price-old">{{ $previousPriceFormatted }}</span>
                @endif
            </div>

            {{-- Offers Button (always show) --}}
            <button type="button" class="catalog-offers-btn"
                    data-catalog-item-id="{{ $catalogItemId }}"
                    data-part-number="{{ $part_number }}">
                <i class="fas fa-tags"></i>
                <span class="offers-count">{{ $offersCount }}</span>
                @lang('offers')
            </button>
        </div>
    </div>
</div>
@endif
