{{-- resources/views/partials/product.blade.php - Quick View Modal --}}
{{-- Uses catalog-unified.css for styling --}}

@php
    /**
     * اختيار البائع لعمليات السلة/العرض في المودال:
     * - أولًا من ?user= في الاستعلام
     * - أو من product->vendor_user_id (إذا حقنه الكنترولر)
     * - أو من product->user_id كـ fallback أخير
     */
    $vendorId = (int) (request()->get('user') ?? ($product->vendor_user_id ?? $product->user_id ?? 0));

    // صورة أساسية
    $mainPhoto = filter_var($product->photo ?? '', FILTER_VALIDATE_URL)
        ? $product->photo
        : (($product->photo ?? null) ? \Illuminate\Support\Facades\Storage::url($product->photo) : asset('assets/images/noimage.png'));

    // MerchantProduct من الكنترولر
    $mp = $mp ?? null;
    $brand = $brand ?? null;

    // السعر
    $rawPrice = $product->price ?? null;
    $rawPrev  = $product->previous_price ?? null;

    $forceVendor = request()->has('user') || isset($product->vendor_user_id);
    if ($forceVendor) {
        $priceHtml = $rawPrice !== null ? \App\Models\Product::convertPrice($rawPrice) : '-';
        $prevHtml  = $rawPrev  !== null ? \App\Models\Product::convertPrice($rawPrev)  : null;
    } else {
        $priceHtml = method_exists($product, 'showPrice')
            ? $product->showPrice()
            : (\App\Models\Product::convertPrice($rawPrice ?? 0));
        $prevHtml  = (method_exists($product, 'showPreviousPrice') && $product->showPreviousPrice())
            ? $product->showPreviousPrice()
            : ($rawPrev !== null ? \App\Models\Product::convertPrice($rawPrev) : null);
    }

    // تقييمات
    $avg   = $product->ratings_avg_rating ?? null;
    $count = class_exists('App\\Models\\Rating') && method_exists('App\\Models\\Rating', 'ratingCount')
        ? \App\Models\Rating::ratingCount($product->id)
        : null;

    // Quality Brand
    $qualityBrand = $mp?->qualityBrand;

    // Vendor
    $vendor = $mp?->user;

    // الحد الأدنى للكمية
    $minQty = $mp ? (int)($mp->minimum_qty ?? 1) : 1;
    if ($minQty < 1) $minQty = 1;

    // المخزون
    $stock = $mp ? (int)($mp->stock ?? 999) : (int)($product->stock ?? 999);
    $inStock = $stock > 0;

    // Preorder
    $preordered = $mp ? (int)($mp->preordered ?? 0) : 0;

    // حالة التوفر
    $canBuy = $inStock || $preordered;
@endphp

<div class="catalog-quickview ill-product" data-product-id="{{ $product->id }}" data-user="{{ $vendorId }}">
    <div class="row g-3 g-md-4">
        {{-- Image Column --}}
        <div class="col-12 col-md-5">
            <div class="catalog-quickview-image">
                @if($mainPhoto)
                    <img src="{{ $mainPhoto }}"
                         alt="{{ $product->name ?? $product->sku }}"
                         class="catalog-quickview-main-img"
                         loading="lazy">
                @endif

                {{-- Gallery Thumbnails (vendor-specific) --}}
                @php
                    $vendorGalleries = $product->galleriesForVendor($vendorId, 4);
                @endphp
                @if($vendorGalleries->count() > 0)
                    <div class="catalog-quickview-gallery">
                        @foreach($vendorGalleries as $gallery)
                            @php
                                $gUrl = filter_var($gallery->photo ?? '', FILTER_VALIDATE_URL)
                                    ? $gallery->photo
                                    : asset('assets/images/galleries/'.$gallery->photo);
                            @endphp
                            <img src="{{ $gUrl }}"
                                 alt="{{ $product->name ?? '' }}"
                                 class="catalog-quickview-thumb"
                                 loading="lazy">
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Details Column --}}
        <div class="col-12 col-md-7">
            {{-- Product Name --}}
            <h4 class="catalog-quickview-title">
                <x-product-name :product="$product" :vendor-id="$vendorId" target="_blank" />
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

            {{-- Product Info Table --}}
            <div class="catalog-quickview-info">
                <table class="catalog-info-table">
                    <tbody>
                        {{-- SKU --}}
                        @if($product->sku)
                            <tr>
                                <td class="catalog-info-label"><i class="fas fa-barcode"></i> @lang('SKU')</td>
                                <td class="catalog-info-value"><code>{{ $product->sku }}</code></td>
                            </tr>
                        @endif

                        {{-- Brand --}}
                        @if($product->brand)
                            <tr>
                                <td class="catalog-info-label"><i class="fas fa-tag"></i> @lang('Brand')</td>
                                <td class="catalog-info-value">{{ getLocalizedBrandName($product->brand) }}</td>
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

                        {{-- Vendor --}}
                        @if($vendor)
                            <tr>
                                <td class="catalog-info-label"><i class="fas fa-store"></i> @lang('Vendor')</td>
                                <td class="catalog-info-value">{{ getLocalizedShopName($vendor) }}</td>
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
            @if(($product->type ?? 'Physical') === 'Physical' && $canBuy)
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
                    {{-- UNIFIED: Use m-cart-add with merchant_product_id --}}
                    <button type="button"
                            class="catalog-quickview-btn catalog-quickview-btn-cart m-cart-add"
                            data-merchant-product-id="{{ $mp->id }}"
                            data-vendor-id="{{ $vendorId }}"
                            data-product-id="{{ $product->id }}"
                            data-min-qty="{{ $minQty }}"
                            data-stock="{{ $stock }}"
                            data-preordered="{{ $preordered }}"
                            data-qty-input=".ill-qty">
                        <i class="fas fa-cart-plus"></i> @lang('Add To Cart')
                    </button>

                    <button type="button"
                            class="catalog-quickview-btn catalog-quickview-btn-buy m-cart-add"
                            data-merchant-product-id="{{ $mp->id }}"
                            data-vendor-id="{{ $vendorId }}"
                            data-product-id="{{ $product->id }}"
                            data-min-qty="{{ $minQty }}"
                            data-stock="{{ $stock }}"
                            data-preordered="{{ $preordered }}"
                            data-qty-input=".ill-qty"
                            data-redirect="/cart">
                        <i class="fas fa-bolt"></i> @lang('Buy Now')
                    </button>
                @else
                    <button type="button" class="catalog-quickview-btn catalog-quickview-btn-disabled" disabled>
                        <i class="fas fa-times-circle"></i> @lang('Out of Stock')
                    </button>
                @endif

                {{-- View Details Link --}}
                @if($mp)
                    <a href="{{ route('front.product', ['slug' => $product->slug, 'vendor_id' => $vendorId, 'merchant_product_id' => $mp->id]) }}"
                       class="catalog-quickview-btn catalog-quickview-btn-details"
                       target="_blank">
                        <i class="fas fa-external-link-alt"></i> @lang('View Details')
                    </a>
                @endif
            </div>

            {{-- Shipping Quote Button --}}
            @if(($product->type ?? 'Physical') == 'Physical' && $mp)
                <div class="mt-3">
                    <x-shipping-quote-button
                        :vendor-id="$vendorId"
                        :product-name="$product->name ?? $product->sku"
                        class="w-100"
                    />
                </div>
            @endif
        </div>
    </div>
</div>

{{-- JavaScript moved to illustrated.js for proper event delegation --}}
