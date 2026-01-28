{{-- resources/views/partials/catalog-item.blade.php - Quick View Modal --}}
{{-- DISPLAY ONLY - All data comes from QuickViewDTO --}}
{{-- Uses catalog-unified.css for styling --}}

<div class="catalog-quickview ill-catalogItem" data-catalog-item-id="{{ $quickView->catalogItemId }}" data-merchant-user-id="{{ $quickView->merchantUserId }}">
    <div class="row g-3 g-md-4">
        {{-- Image Column --}}
        <div class="col-12 col-md-5">
            <div class="catalog-quickview-image">
                @if($quickView->mainPhoto)
                    <img src="{{ $quickView->mainPhoto }}"
                         alt="{{ $quickView->catalogItemName }}"
                         class="catalog-quickview-main-img"
                         loading="lazy">
                @endif

                {{-- Photo Thumbnails --}}
                @if(count($quickView->merchantGalleries) > 0)
                    <div class="catalog-quickview-gallery">
                        @foreach($quickView->merchantGalleries as $gallery)
                            <img src="{{ $gallery['photo_url'] ?? '' }}"
                                 alt="{{ $quickView->catalogItemName }}"
                                 class="catalog-quickview-thumb"
                                 loading="lazy">
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Details Column --}}
        <div class="col-12 col-md-7">
            {{-- Catalog Item Name --}}
            <h4 class="catalog-quickview-name">
                <x-catalog-item-name
                    :name="$quickView->catalogItemName"
                    :part-number="$quickView->partNumber"
                    :url="$quickView->catalogItemUrl"
                    target="_blank" />
            </h4>

            {{-- Rating --}}
            @if($quickView->avgRating)
                <div class="catalog-quickview-rating">
                    <div class="catalog-star-rating">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="star {{ $i <= $quickView->roundedRating ? 'filled' : '' }}">★</span>
                        @endfor
                    </div>
                    <span class="catalog-rating-value">{{ $quickView->formattedRating }}</span>
                </div>
            @endif

            {{-- Price --}}
            <div class="catalog-quickview-price">
                <span class="catalog-price-current">{!! $quickView->priceHtml !!}</span>
                @if($quickView->prevPriceHtml)
                    <span class="catalog-price-old">{!! $quickView->prevPriceHtml !!}</span>
                @endif
            </div>

            {{-- Quality Brand --}}
            @if($quickView->qualityBrand)
                <div class="catalog-quickview-brand">
                    @if($quickView->qualityBrand->logo)
                        <img src="{{ $quickView->qualityBrand->logo_url }}"
                             alt="{{ $quickView->qualityBrand->localized_name }}"
                             class="catalog-brand-logo">
                    @endif
                    <span class="catalog-brand-name">{{ $quickView->qualityBrand->localized_name }}</span>
                </div>
            @endif

            {{-- Merchant Info --}}
            @if($quickView->merchant)
                <div class="catalog-quickview-merchant">
                    <span class="catalog-merchant-label">@lang('Sold by'):</span>
                    <span class="catalog-merchant-name">{{ $quickView->merchant->localized_shop_name }}</span>
                </div>
            @endif

            {{-- Branch Info --}}
            @if($quickView->branch)
                <div class="catalog-quickview-branch">
                    <span class="catalog-branch-label">@lang('Ships from'):</span>
                    <span class="catalog-branch-name">{{ $quickView->branch->display_name }}</span>
                </div>
            @endif

            {{-- Stock Status --}}
            <div class="catalog-quickview-stock">
                @if($quickView->inStock)
                    <span class="m-badge m-badge--success">@lang('In Stock')</span>
                @elseif($quickView->preordered)
                    <span class="m-badge m-badge--info">@lang('Pre-order')</span>
                @else
                    <span class="m-badge m-badge--danger">@lang('Out of Stock')</span>
                @endif
            </div>

            {{-- Fitment Brands --}}
            @if($quickView->fitmentCount > 0)
                <div class="catalog-quickview-fitment">
                    <span class="catalog-fitment-label">@lang('Fits'):</span>
                    <div class="catalog-fitment-brands">
                        @foreach($quickView->fitmentBrands as $brand)
                            @if($brand)
                                <span class="catalog-fitment-brand">
                                    @if($brand->photo_url ?? null)
                                        <img src="{{ $brand->photo_url }}"
                                             alt="{{ $brand->localized_name }}"
                                             class="catalog-fitment-logo">
                                    @endif
                                    {{ $brand->localized_name }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Add to Cart Section --}}
            @if($quickView->canBuy && $quickView->merchantItemId)
                <div class="catalog-quickview-cart mt-3">
                    <div class="d-flex align-items-center gap-2">
                        <input type="number"
                               class="form-control catalog-qty-input"
                               id="quick-qty-{{ $quickView->merchantItemId }}"
                               value="{{ $quickView->minQty }}"
                               min="{{ $quickView->minQty }}"
                               max="{{ $quickView->stock }}"
                               style="width: 80px;">
                        <button type="button"
                                class="m-btn m-btn--primary catalog-add-to-cart"
                                data-merchant-item-id="{{ $quickView->merchantItemId }}"
                                data-min-qty="{{ $quickView->minQty }}">
                            <i class="icofont-cart me-1"></i>
                            @lang('Add to Cart')
                        </button>
                    </div>
                </div>
            @endif

            {{-- View Full Details Link --}}
            @if($quickView->catalogItemUrl)
                <div class="catalog-quickview-link mt-3">
                    <a href="{{ $quickView->catalogItemUrl }}" class="m-btn m-btn--outline-primary">
                        @lang('View Full Details')
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
