{{--
================================================================================
SECTION PARTIAL: Best Month Offers (Arrival Section)
================================================================================
Receives: $merchantItems (Collection of MerchantItem models)
================================================================================
--}}

@if(isset($merchantItems) && $merchantItems->count() > 0)
<div class="row">
    @foreach($merchantItems as $mp)
    @php
        $actualProduct = $mp->catalogItem;
        if (!$actualProduct) continue;

        $defaultImage = asset('assets/images/noimage.png');

        // Product info
        $productId = $actualProduct->id;
        $merchantId = $mp->id;
        $vendorId = $mp->user_id;
        $productName = $actualProduct->showName();

        // URL
        $productUrl = route('front.catalog-item', [
            'slug' => $actualProduct->slug,
            'merchant_id' => $mp->user_id,
            'merchant_item_id' => $mp->id
        ]);

        // Photo
        $mainPhoto = $actualProduct->photo ?? null;
        $photo = $mainPhoto
            ? (filter_var($mainPhoto, FILTER_VALIDATE_URL) ? $mainPhoto : Storage::url($mainPhoto))
            : $defaultImage;

        // Brand info (from product)
        $brandName = $actualProduct->brand?->localized_name;
        $brandLogo = $actualProduct->brand?->photo_url;

        // Quality Brand info (from merchant_product)
        $qualityBrandName = $mp->qualityBrand?->localized_name;
        $qualityBrandLogo = $mp->qualityBrand?->logo_url;

        // Vendor info
        $vendorName = $mp->user ? getLocalizedShopName($mp->user) : null;

        // Price
        $price = $mp->price ?? 0;
        $previousPrice = $mp->previous_price ?? 0;
        $offPercentage = ($previousPrice > 0 && $previousPrice > $price)
            ? round((($previousPrice - $price) / $previousPrice) * 100)
            : 0;
        $priceFormatted = \App\Models\CatalogItem::convertPrice($price);
        $previousPriceFormatted = $previousPrice > 0 ? \App\Models\CatalogItem::convertPrice($previousPrice) : '';

        // Stock
        $stockQty = (int)($mp->stock ?? 0);
        $inStock = $stockQty > 0 || $mp->preordered;
        $stockText = $inStock ? __('In Stock') : __('Out of Stock');
        $minQty = max(1, (int)($mp->minimum_qty ?? 1));
        $preordered = $mp->preordered ?? false;

        // Product type
        $productType = $actualProduct->type ?? 'Physical';
        $affiliateProductType = $mp->product_type ?? null;
        $affiliateLink = $mp->affiliate_link ?? null;
    @endphp

    <div class="col-6 col-md-4 col-lg-3 mb-4">
        <div class="product-card" id="pc_{{ $productId }}_{{ $merchantId }}">
            {{-- Media Section --}}
            <div class="product-card__media">
                @if ($offPercentage > 0)
                    <span class="product-card__badge product-card__badge--discount">
                        -{{ $offPercentage }}%
                    </span>
                @endif

                @if (!$inStock)
                    <span class="product-card__badge product-card__badge--stock">
                        {{ __('Out of Stock') }}
                    </span>
                @endif

                <a href="{{ $productUrl }}" class="product-card__media-link">
                    <img src="{{ $photo }}" alt="{{ $productName }}" class="product-card__img"
                         loading="lazy" onerror="this.onerror=null; this.src='{{ $defaultImage }}';">
                </a>
            </div>

            {{-- Content Section --}}
            <div class="product-card__content">
                <h6 class="product-card__title">
                    <a href="{{ $productUrl }}">{{ Str::limit($productName, 50) }}</a>
                </h6>

                {{-- Product Info: Brand, Quality, Vendor --}}
                <div class="product-card__info">
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

                {{-- Add to Cart --}}
                @if ($productType !== 'Listing' && $affiliateProductType !== 'affiliate')
                    @if ($inStock)
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

                {{-- Shipping Quote Button --}}
                @if($productType == 'Physical')
                    <x-shipping-quote-button
                        :vendor-id="$vendorId"
                        :product-name="$productName"
                        class="mt-2"
                    />
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
