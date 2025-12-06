{{-- resources/views/partials/product.blade.php - Quick View Modal --}}

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

<div class="qv-modal ill-product" data-product-id="{{ $product->id }}" data-user="{{ $vendorId }}">
    <div class="row g-3 g-md-4">
        {{-- Image Column --}}
        <div class="col-12 col-md-5">
            <div class="qv-image-wrapper">
                @if($mainPhoto)
                    <img src="{{ $mainPhoto }}"
                         alt="{{ $product->name ?? $product->sku }}"
                         class="qv-main-image"
                         loading="lazy">
                @endif

                {{-- Gallery Thumbnails --}}
                @if(!empty($product->galleries) && count($product->galleries) > 0)
                    <div class="qv-gallery">
                        @foreach($product->galleries->take(4) as $gallery)
                            @php
                                $gUrl = filter_var($gallery->photo ?? '', FILTER_VALIDATE_URL)
                                    ? $gallery->photo
                                    : asset('assets/images/galleries/'.$gallery->photo);
                            @endphp
                            <img src="{{ $gUrl }}"
                                 alt="{{ $product->name ?? '' }}"
                                 class="qv-thumb"
                                 loading="lazy">
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Details Column --}}
        <div class="col-12 col-md-7">
            {{-- Product Name --}}
            <h4 class="qv-title">
                <x-product-name :product="$product" :vendor-id="$vendorId" target="_blank" />
            </h4>

            {{-- Rating --}}
            @if(!empty($avg))
                <div class="qv-rating">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fa{{ $i <= round($avg) ? 's' : 'r' }} fa-star"></i>
                    @endfor
                    <span class="qv-rating-text">{{ number_format($avg, 1) }}</span>
                    @if($count)
                        <span class="qv-rating-count">({{ $count }})</span>
                    @endif
                </div>
            @endif

            {{-- Price --}}
            <div class="qv-price">
                <span class="qv-price-current">{!! $priceHtml !!}</span>
                @if($prevHtml)
                    <del class="qv-price-old">{!! $prevHtml !!}</del>
                @endif
            </div>

            {{-- Product Info Table --}}
            <div class="qv-info">
                <table class="qv-info-table">
                    <tbody>
                        {{-- SKU --}}
                        @if($product->sku)
                            <tr>
                                <td class="qv-info-label"><i class="fas fa-barcode"></i> @lang('SKU')</td>
                                <td class="qv-info-value"><code>{{ $product->sku }}</code></td>
                            </tr>
                        @endif

                        {{-- Brand --}}
                        @if($product->brand)
                            <tr>
                                <td class="qv-info-label"><i class="fas fa-tag"></i> @lang('Brand')</td>
                                <td class="qv-info-value">{{ getLocalizedBrandName($product->brand) }}</td>
                            </tr>
                        @endif

                        {{-- Quality Brand with Logo --}}
                        @if($qualityBrand)
                            <tr>
                                <td class="qv-info-label"><i class="fas fa-certificate"></i> @lang('Quality')</td>
                                <td class="qv-info-value">
                                    <div class="qv-quality">
                                        @if($qualityBrand->logo)
                                            <img src="{{ $qualityBrand->logo_url }}"
                                                 alt="{{ getLocalizedQualityName($qualityBrand) }}"
                                                 class="qv-quality-logo">
                                        @endif
                                        <span>{{ getLocalizedQualityName($qualityBrand) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endif

                        {{-- Vendor --}}
                        @if($vendor)
                            <tr>
                                <td class="qv-info-label"><i class="fas fa-store"></i> @lang('Vendor')</td>
                                <td class="qv-info-value">{{ $vendor->shop_name ?: $vendor->name }}</td>
                            </tr>
                        @endif

                        {{-- Stock --}}
                        <tr>
                            <td class="qv-info-label"><i class="fas fa-boxes"></i> @lang('Stock')</td>
                            <td class="qv-info-value">
                                @if($inStock)
                                    <span class="badge bg-success">{{ $stock }} @lang('Available')</span>
                                @elseif($preordered)
                                    <span class="badge bg-warning text-dark">@lang('Preorder')</span>
                                @else
                                    <span class="badge bg-danger">@lang('Out of Stock')</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Quantity Selector --}}
            @if(($product->type ?? 'Physical') === 'Physical' && $canBuy)
                <div class="qv-quantity">
                    <label class="qv-qty-label">@lang('Quantity'):</label>
                    <div class="qv-qty-control">
                        <button type="button" class="qv-qty-btn modal-qtminus" data-min="{{ $minQty }}">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number"
                               name="quantity"
                               value="{{ $minQty }}"
                               min="{{ $minQty }}"
                               class="qv-qty-input ill-qty modal-qty-input"
                               data-min="{{ $minQty }}"
                               data-stock="{{ $stock }}"
                               data-preordered="{{ $preordered }}"
                               readonly>
                        <button type="button" class="qv-qty-btn modal-qtplus" data-stock="{{ $stock }}" data-preordered="{{ $preordered }}">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    @if($minQty > 1)
                        <small class="qv-qty-hint">@lang('Min'): {{ $minQty }}</small>
                    @endif
                </div>
            @endif

            {{-- Action Buttons --}}
            <div class="qv-actions">
                @if($canBuy)
                    @if($mp)
                        <button type="button"
                                class="btn qv-btn-cart ill-add-to-cart"
                                data-id="{{ $product->id }}"
                                data-mp-id="{{ $mp->id }}"
                                data-user="{{ $vendorId }}"
                                data-addnum-url="{{ route('merchant.cart.add', $mp->id) }}">
                            <i class="fas fa-cart-plus"></i> @lang('Add To Cart')
                        </button>

                        <button type="button"
                                class="btn qv-btn-buy ill-buy-now"
                                data-id="{{ $product->id }}"
                                data-mp-id="{{ $mp->id }}"
                                data-user="{{ $vendorId }}"
                                data-addtonum-url="{{ route('merchant.cart.add', $mp->id) }}"
                                data-carts-url="{{ url('/carts') }}">
                            <i class="fas fa-bolt"></i> @lang('Buy Now')
                        </button>
                    @else
                        <button type="button"
                                class="btn qv-btn-cart ill-add-to-cart"
                                data-id="{{ $product->id }}"
                                data-user="{{ $vendorId }}"
                                data-addnum-url="{{ url('/addnumcart') }}">
                            <i class="fas fa-cart-plus"></i> @lang('Add To Cart')
                        </button>

                        <button type="button"
                                class="btn qv-btn-buy ill-buy-now"
                                data-id="{{ $product->id }}"
                                data-user="{{ $vendorId }}"
                                data-addtonum-url="{{ url('/addtonumcart') }}"
                                data-carts-url="{{ url('/carts') }}">
                            <i class="fas fa-bolt"></i> @lang('Buy Now')
                        </button>
                    @endif
                @else
                    <button type="button" class="btn qv-btn-disabled" disabled>
                        <i class="fas fa-times-circle"></i> @lang('Out of Stock')
                    </button>
                @endif

                {{-- View Details Link --}}
                @if($mp)
                    <a href="{{ route('front.product', ['slug' => $product->slug, 'vendor_id' => $vendorId, 'merchant_product_id' => $mp->id]) }}"
                       class="btn qv-btn-details"
                       target="_blank">
                        <i class="fas fa-external-link-alt"></i> @lang('View Details')
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Styles --}}
<style>
/* ========== Quick View Modal Styles ========== */
.qv-modal {
    padding: 0;
}

/* Image Section */
.qv-image-wrapper {
    text-align: center;
}

.qv-main-image {
    width: 100%;
    max-height: 300px;
    object-fit: contain;
    border-radius: 8px;
    background: #f8f9fa;
    padding: 10px;
}

.qv-gallery {
    display: flex;
    gap: 8px;
    margin-top: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.qv-thumb {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 4px;
    border: 2px solid #e0e0e0;
    cursor: pointer;
    transition: border-color 0.2s;
}

.qv-thumb:hover {
    border-color: var(--primary-color, #007bff);
}

/* Title */
.qv-title {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 8px;
    line-height: 1.4;
}

/* Rating */
.qv-rating {
    margin-bottom: 10px;
    color: #f5a623;
    font-size: 0.9rem;
}

.qv-rating i {
    margin-right: 2px;
}

.qv-rating-text {
    color: #333;
    font-weight: 600;
    margin-left: 5px;
}

.qv-rating-count {
    color: #888;
    font-size: 0.85rem;
}

/* Price */
.qv-price {
    margin-bottom: 15px;
}

.qv-price-current {
    font-size: 1.5rem;
    font-weight: 700;
    color: #28a745;
}

.qv-price-old {
    font-size: 1rem;
    color: #999;
    margin-left: 10px;
}

/* Info Table */
.qv-info {
    margin-bottom: 15px;
}

.qv-info-table {
    width: 100%;
    font-size: 0.9rem;
}

.qv-info-table tr {
    border-bottom: 1px solid #f0f0f0;
}

.qv-info-table tr:last-child {
    border-bottom: none;
}

.qv-info-label {
    padding: 8px 10px 8px 0;
    color: #666;
    width: 100px;
    white-space: nowrap;
}

.qv-info-label i {
    width: 16px;
    text-align: center;
    margin-right: 6px;
    color: #888;
}

.qv-info-value {
    padding: 8px 0;
    color: #333;
}

.qv-quality {
    display: flex;
    align-items: center;
    gap: 8px;
}

.qv-quality-logo {
    max-height: 24px;
    max-width: 60px;
    object-fit: contain;
}

/* Quantity */
.qv-quantity {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.qv-qty-label {
    font-weight: 500;
    color: #555;
}

.qv-qty-control {
    display: flex;
    align-items: center;
    border: 1px solid #ddd;
    border-radius: 6px;
    overflow: hidden;
}

.qv-qty-btn {
    width: 36px;
    height: 36px;
    border: none;
    background: #f5f5f5;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}

.qv-qty-btn:hover {
    background: #e0e0e0;
}

.qv-qty-input {
    width: 50px;
    height: 36px;
    text-align: center;
    border: none;
    font-weight: 600;
    font-size: 1rem;
}

.qv-qty-hint {
    color: #888;
    font-size: 0.8rem;
}

/* Actions */
.qv-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.qv-btn-cart,
.qv-btn-buy,
.qv-btn-details,
.qv-btn-disabled {
    flex: 1;
    min-width: 120px;
    padding: 10px 15px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: all 0.2s;
}

.qv-btn-cart {
    background: #007bff;
    color: #fff;
    border: none;
}

.qv-btn-cart:hover {
    background: #0056b3;
    color: #fff;
}

.qv-btn-buy {
    background: #28a745;
    color: #fff;
    border: none;
}

.qv-btn-buy:hover {
    background: #1e7e34;
    color: #fff;
}

.qv-btn-details {
    background: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
    text-decoration: none;
}

.qv-btn-details:hover {
    background: #e9ecef;
    color: #333;
}

.qv-btn-disabled {
    background: #e9ecef;
    color: #999;
    border: none;
    cursor: not-allowed;
}

/* Mobile Responsive */
@media (max-width: 767px) {
    .qv-main-image {
        max-height: 200px;
    }

    .qv-title {
        font-size: 1.1rem;
    }

    .qv-price-current {
        font-size: 1.3rem;
    }

    .qv-info-label {
        width: 80px;
        font-size: 0.85rem;
    }

    .qv-actions {
        flex-direction: column;
    }

    .qv-btn-cart,
    .qv-btn-buy,
    .qv-btn-details {
        min-width: 100%;
    }
}

/* RTL Support */
[dir="rtl"] .qv-info-label {
    padding: 8px 0 8px 10px;
}

[dir="rtl"] .qv-info-label i {
    margin-right: 0;
    margin-left: 6px;
}

[dir="rtl"] .qv-price-old {
    margin-left: 0;
    margin-right: 10px;
}

[dir="rtl"] .qv-rating-text {
    margin-left: 0;
    margin-right: 5px;
}
</style>

{{-- JavaScript moved to illustrated.js for proper event delegation --}}
