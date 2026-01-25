{{-- resources/views/partials/catalog-item.blade.php - Quick View Modal --}}
{{-- Uses catalog-unified.css for styling --}}

@php
    // Use QuickViewDTO when available (pre-computed in Controller)
    if (isset($quickView) && $quickView instanceof \App\Domain\Catalog\DTOs\QuickViewDTO) {
        $catalogItem = $quickView->catalogItem;
        $mp = $quickView->merchantItem;
        $merchantUserId = $quickView->merchantUserId;
        $mainPhoto = $quickView->mainPhoto;
        $priceHtml = $quickView->priceHtml;
        $prevHtml = $quickView->prevPriceHtml;
        $avg = $quickView->avgRating;
        $count = $quickView->reviewCount;
        $qualityBrand = $quickView->qualityBrand;
        $merchant = $quickView->merchant;
        $minQty = $quickView->minQty;
        $stock = $quickView->stock;
        $inStock = $quickView->inStock;
        $preordered = $quickView->preordered;
        $canBuy = $quickView->canBuy;
    } else {
        // Legacy fallback: compute data inline (for backward compatibility)
        $mp = $mp ?? null;
        $merchantUserId = (int) (request()->get('user') ?? ($catalogItem->merchant_user_id ?? $catalogItem->user_id ?? 0));

        $mainPhoto = filter_var($catalogItem->photo ?? '', FILTER_VALIDATE_URL)
            ? $catalogItem->photo
            : (($catalogItem->photo ?? null) ? \Illuminate\Support\Facades\Storage::url($catalogItem->photo) : asset('assets/images/noimage.png'));

        $rawPrice = $catalogItem->price ?? null;
        $rawPrev  = $catalogItem->previous_price ?? null;
        $forceMerchant = request()->has('user') || isset($catalogItem->merchant_user_id);

        if ($forceMerchant) {
            $priceHtml = $rawPrice !== null ? \App\Domain\Catalog\Models\CatalogItem::convertPrice($rawPrice) : '-';
            $prevHtml  = $rawPrev  !== null ? \App\Domain\Catalog\Models\CatalogItem::convertPrice($rawPrev)  : null;
        } else {
            $priceHtml = method_exists($catalogItem, 'showPrice')
                ? $catalogItem->showPrice()
                : (\App\Domain\Catalog\Models\CatalogItem::convertPrice($rawPrice ?? 0));
            $prevHtml  = (method_exists($catalogItem, 'showPreviousPrice') && $catalogItem->showPreviousPrice())
                ? $catalogItem->showPreviousPrice()
                : ($rawPrev !== null ? \App\Domain\Catalog\Models\CatalogItem::convertPrice($rawPrev) : null);
        }

        $avg = $catalogItem->catalog_reviews_avg_rating ?? null;
        $count = null;
        $qualityBrand = $mp?->qualityBrand;
        $merchant = $mp?->user;
        $minQty = max(1, (int)($mp?->minimum_qty ?? 1));
        $stock = (int)($mp?->stock ?? ($catalogItem->stock ?? 999));
        $inStock = $stock > 0;
        $preordered = (bool)($mp?->preordered ?? 0);
        $canBuy = $inStock || $preordered;
    }
@endphp

<div class="catalog-quickview ill-catalogItem" data-catalog-item-id="{{ $catalogItem->id }}" data-merchant-user-id="{{ $merchantUserId }}">
    <div class="row g-3 g-md-4">
        {{-- Image Column --}}
        <div class="col-12 col-md-5">
            <div class="catalog-quickview-image">
                @if($mainPhoto)
                    <img src="{{ $mainPhoto }}"
                         alt="{{ $catalogItem->name ?? $catalogItem->part_number }}"
                         class="catalog-quickview-main-img"
                         loading="lazy">
                @endif

                {{-- Photo Thumbnails (merchant-specific) --}}
                @php
                    $merchantGalleries = $catalogItem->merchantPhotosForMerchant($merchantUserId, 4);
                @endphp
                @if($merchantGalleries->count() > 0)
                    <div class="catalog-quickview-gallery">
                        @foreach($merchantGalleries as $gallery)
                            @php
                                $gUrl = filter_var($gallery->photo ?? '', FILTER_VALIDATE_URL)
                                    ? $gallery->photo
                                    : asset('assets/images/merchant-photos/'.$gallery->photo);
                            @endphp
                            <img src="{{ $gUrl }}"
                                 alt="{{ $catalogItem->name ?? '' }}"
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
                <x-catalog-item-name :catalog-item="$catalogItem" :merchant-user-id="$merchantUserId" target="_blank" />
            </h4>

            {{-- Rating --}}
            @if(!empty($avg))
                <div class="catalog-quickview-rating">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fa{{ $i <= round($avg) ? 's' : 'r' }} fa-star"></i>
                    @endfor
                    <span class="catalog-quickview-rating-text">{{ number_format($avg, 1) }}</span>
                    @if($count)
                        <span class="catalog-quickview-rating-count">({{ $count }})</span>
                    @endif
                </div>
            @endif

            {{-- Price --}}
            <div class="catalog-quickview-price">
                <span class="catalog-quickview-price-current">{!! $priceHtml !!}</span>
                @if($prevHtml)
                    <del class="catalog-quickview-price-old">{!! $prevHtml !!}</del>
                @endif
            </div>

            {{-- CatalogItem Info Table --}}
            <div class="catalog-quickview-info">
                <table class="catalog-info-table">
                    <tbody>
                        {{-- PART_NUMBER --}}
                        @if($catalogItem->part_number)
                            <tr>
                                <td class="catalog-info-label"><i class="fas fa-barcode"></i> @lang('PART_NUMBER')</td>
                                <td class="catalog-info-value"><code>{{ $catalogItem->part_number }}</code></td>
                            </tr>
                        @endif

                        {{-- Vehicle Fitment Brands (from catalog_item_fitments) --}}
                        @php
                            $catalogItemForFitment = $mp?->catalogItem ?? $catalogItem;
                            $fitments = $catalogItemForFitment?->fitments ?? collect();
                            $fitmentBrands = $fitments->map(fn($f) => $f->brand)->filter()->unique('id')->values();
                            $fitmentCount = $fitmentBrands->count();
                        @endphp
                        @if($fitmentCount > 0)
                            <tr>
                                <td class="catalog-info-label"><i class="fas fa-car"></i> @lang('Vehicle Compatibility')</td>
                                <td class="catalog-info-value">
                                    <button type="button" class="catalog-btn catalog-btn-outline catalog-btn-sm fitment-details-btn"
                                            data-catalog-item-id="{{ $catalogItemForFitment->id }}"
                                            data-part-number="{{ $catalogItemForFitment->part_number }}">
                                        @if($fitmentCount === 1 && $fitmentBrands->first()->photo_url)
                                            <img src="{{ $fitmentBrands->first()->photo_url }}" alt="" class="catalog-btn__logo">
                                        @else
                                            <i class="fas fa-car"></i>
                                        @endif
                                        @if($fitmentCount === 1)
                                            <span>{{ getLocalizedBrandName($fitmentBrands->first()) }}</span>
                                        @else
                                            <span>{{ __('Fits') }}</span>
                                            <span class="catalog-badge catalog-badge-sm">{{ $fitmentCount }}</span>
                                        @endif
                                    </button>
                                </td>
                            </tr>
                        @endif

                        {{-- Quality Brand with Logo --}}
                        @if($qualityBrand)
                            <tr>
                                <td class="catalog-info-label"><i class="fas fa-certificate"></i> @lang('Quality')</td>
                                <td class="catalog-info-value">
                                    <span class="catalog-quality-badge">
                                        @if($qualityBrand->logo)
                                            <img src="{{ $qualityBrand->logo_url }}"
                                                 alt="{{ getLocalizedQualityName($qualityBrand) }}"
                                                 class="catalog-quality-badge__logo">
                                        @endif
                                        {{ getLocalizedQualityName($qualityBrand) }}
                                    </span>
                                </td>
                            </tr>
                        @endif

                        {{-- Merchant --}}
                        @if($merchant)
                            <tr>
                                <td class="catalog-info-label"><i class="fas fa-store"></i> @lang('Merchant')</td>
                                <td class="catalog-info-value">{{ getLocalizedShopName($merchant) }}</td>
                            </tr>
                        @endif

                        {{-- Branch --}}
                        @if($mp?->merchantBranch)
                            <tr>
                                <td class="catalog-info-label"><i class="fas fa-warehouse"></i> @lang('Branch')</td>
                                <td class="catalog-info-value">{{ $mp->merchantBranch->warehouse_name }}</td>
                            </tr>
                        @endif

                        {{-- Stock --}}
                        <tr>
                            <td class="catalog-info-label"><i class="fas fa-boxes"></i> @lang('Stock')</td>
                            <td class="catalog-info-value">
                                @if($inStock)
                                    <span class="catalog-badge catalog-badge-success">{{ $stock }} @lang('Available')</span>
                                @elseif($preordered)
                                    <span class="catalog-badge catalog-badge-warning">@lang('Preorder')</span>
                                @else
                                    <span class="catalog-badge catalog-badge-danger">@lang('Out of Stock')</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Quantity Selector --}}
            @if($canBuy)
                <div class="catalog-quickview-quantity">
                    <label class="catalog-quickview-qty-label">@lang('Quantity'):</label>
                    <div class="catalog-quickview-qty-control">
                        <button type="button" class="catalog-quickview-qty-btn modal-qtminus" data-min="{{ $minQty }}">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number"
                               name="quantity"
                               value="{{ $minQty }}"
                               min="{{ $minQty }}"
                               class="catalog-quickview-qty-input ill-qty modal-qty-input"
                               data-min="{{ $minQty }}"
                               data-stock="{{ $stock }}"
                               data-preordered="{{ $preordered }}"
                               readonly>
                        <button type="button" class="catalog-quickview-qty-btn modal-qtplus" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    @if($minQty > 1)
                        <small class="catalog-quickview-qty-hint">@lang('Min'): {{ $minQty }}</small>
                    @endif
                </div>
            @endif

            {{-- Action Buttons --}}
            <div class="catalog-quickview-actions">
                @if($canBuy && $mp)
                    {{-- UNIFIED: Use m-cart-add with merchant_item_id --}}
                    <button type="button"
                            class="catalog-quickview-btn catalog-quickview-btn-cart m-cart-add"
                            data-merchant-item-id="{{ $mp->id }}"
                            data-merchant-user-id="{{ $merchantUserId }}"
                            data-catalog-item-id="{{ $catalogItem->id }}"
                            data-min-qty="{{ $minQty }}"
                            data-stock="{{ $stock }}"
                            data-preordered="{{ $preordered }}"
                            data-qty-input=".ill-qty">
                        <i class="fas fa-cart-plus"></i> @lang('Add To Cart')
                    </button>

                    <button type="button"
                            class="catalog-quickview-btn catalog-quickview-btn-buy m-cart-add"
                            data-merchant-item-id="{{ $mp->id }}"
                            data-merchant-user-id="{{ $merchantUserId }}"
                            data-catalog-item-id="{{ $catalogItem->id }}"
                            data-min-qty="{{ $minQty }}"
                            data-stock="{{ $stock }}"
                            data-preordered="{{ $preordered }}"
                            data-qty-input=".ill-qty"
                            data-redirect="/merchant-cart">
                        <i class="fas fa-bolt"></i> @lang('Buy Now')
                    </button>
                @else
                    <button type="button" class="catalog-quickview-btn catalog-quickview-btn-disabled" disabled>
                        <i class="fas fa-times-circle"></i> @lang('Out of Stock')
                    </button>
                @endif

                {{-- View Details Link --}}
                @if($catalogItem->part_number)
                    <a href="{{ route('front.part-result', $catalogItem->part_number) }}"
                       class="catalog-quickview-btn catalog-quickview-btn-details"
                       target="_blank">
                        <i class="fas fa-external-link-alt"></i> @lang('View Details')
                    </a>
                @endif
            </div>

            {{-- Shipping Quote Button --}}
            @if($mp)
                <div class="mt-3">
                    <x-shipping-quote-button
                        :merchant-user-id="$merchantUserId"
                        :catalog-item-name="$catalogItem->name ?? $catalogItem->part_number"
                        class="w-100"
                    />
                </div>
            @endif
        </div>
    </div>
</div>

{{-- JavaScript moved to illustrated.js for proper event delegation --}}
