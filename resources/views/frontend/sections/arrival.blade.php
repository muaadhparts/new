{{--
================================================================================
SECTION PARTIAL: Best Month Offers (Arrival Section)
================================================================================
Receives: $merchantItems (Collection of MerchantItem models)
================================================================================
--}}

@if(isset($merchantItems) && $merchantItems->count() > 0)
<div class="row">
    @foreach($merchantItems as $merchantItem)
    @php
        $actualCatalogItem = $merchantItem->catalogItem;
        if (!$actualCatalogItem) continue;

        $defaultImage = asset('assets/images/noimage.png');

        // Catalog item info
        $catalogItemId = $actualCatalogItem->id;
        $merchantItemId = $merchantItem->id;
        $merchantUserId = $merchantItem->user_id;
        $catalogItemName = $actualCatalogItem->showName();

        // URL
        $catalogItemUrl = route('front.catalog-item', [
            'slug' => $actualCatalogItem->slug,
            'merchant_id' => $merchantItem->user_id,
            'merchant_item_id' => $merchantItem->id
        ]);

        // Photo
        $mainPhoto = $actualCatalogItem->photo ?? null;
        $photo = $mainPhoto
            ? (filter_var($mainPhoto, FILTER_VALIDATE_URL) ? $mainPhoto : Storage::url($mainPhoto))
            : $defaultImage;

        // Brand info (from catalog item)
        $brandName = $actualCatalogItem->brand?->localized_name;
        $brandLogo = $actualCatalogItem->brand?->photo_url;

        // Quality Brand info (from merchant item)
        $qualityBrandName = $merchantItem->qualityBrand?->localized_name;
        $qualityBrandLogo = $merchantItem->qualityBrand?->logo_url;

        // Merchant info
        $merchantName = $merchantItem->user ? getLocalizedShopName($merchantItem->user) : null;

        // Price
        $price = $merchantItem->price ?? 0;
        $previousPrice = $merchantItem->previous_price ?? 0;
        $offPercentage = ($previousPrice > 0 && $previousPrice > $price)
            ? round((($previousPrice - $price) / $previousPrice) * 100)
            : 0;
        $priceFormatted = \App\Models\CatalogItem::convertPrice($price);
        $previousPriceFormatted = $previousPrice > 0 ? \App\Models\CatalogItem::convertPrice($previousPrice) : '';

        // Stock
        $stockQty = (int)($merchantItem->stock ?? 0);
        $inStock = $stockQty > 0 || $merchantItem->preordered;
        $stockText = $inStock ? __('In Stock') : __('Out of Stock');
        $minQty = max(1, (int)($merchantItem->minimum_qty ?? 1));
        $preordered = $merchantItem->preordered ?? false;

        // Catalog item type
        $catalogItemType = $actualCatalogItem->type ?? 'Physical';
        $affiliateCatalogItemType = $merchantItem->item_type ?? null;
        $affiliateLink = $merchantItem->affiliate_link ?? null;
    @endphp

    <div class="col-6 col-md-4 col-lg-3 mb-4">
        <div class="catalogItem-card" id="ci_{{ $catalogItemId }}_{{ $merchantItemId }}">
            {{-- Media Section --}}
            <div class="catalogItem-card__media">
                @if ($offPercentage > 0)
                    <span class="catalogItem-card__badge catalogItem-card__badge--discount">
                        -{{ $offPercentage }}%
                    </span>
                @endif

                @if (!$inStock)
                    <span class="catalogItem-card__badge catalogItem-card__badge--stock">
                        {{ __('Out of Stock') }}
                    </span>
                @endif

                <a href="{{ $catalogItemUrl }}" class="catalogItem-card__media-link">
                    <img src="{{ $photo }}" alt="{{ $catalogItemName }}" class="catalogItem-card__img"
                         loading="lazy" onerror="this.onerror=null; this.src='{{ $defaultImage }}';">
                </a>
            </div>

            {{-- Content Section --}}
            <div class="catalogItem-card__content">
                <h6 class="catalogItem-card__title">
                    <a href="{{ $catalogItemUrl }}">{{ Str::limit($catalogItemName, 50) }}</a>
                </h6>

                {{-- Catalog Item Info: Brand, Quality, Merchant --}}
                <div class="catalogItem-card__info">
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

                {{-- Add to Cart --}}
                @if ($catalogItemType !== 'Listing' && $affiliateCatalogItemType !== 'affiliate')
                    @if ($inStock)
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

                {{-- Shipping Quote Button --}}
                @if($catalogItemType == 'Physical')
                    <x-shipping-quote-button
                        :merchant-user-id="$merchantUserId"
                        :catalog-item-name="$catalogItemName"
                        class="mt-2"
                    />
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
