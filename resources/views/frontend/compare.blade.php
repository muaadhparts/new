@extends('layouts.front')

@push('css')
<style>
    .gs-compare-wrapper {
        padding: 60px 0;
    }
    .gs-compare-wrapper .compare-table {
        width: 100%;
        border-collapse: collapse;
        background: var(--color-surface, #fff);
        border-radius: 8px;
        overflow: hidden;
        box-shadow: var(--shadow-sm, 0 2px 10px rgba(0,0,0,0.08));
    }
    .gs-compare-wrapper .compare-table tr {
        border-bottom: 1px solid var(--theme-border-light, #e8e0d0);
    }
    .gs-compare-wrapper .compare-table tr:last-child {
        border-bottom: none;
    }
    .gs-compare-wrapper .compare-table td {
        padding: 16px 20px;
        vertical-align: middle;
        text-align: center;
        min-width: 200px;
    }
    .gs-compare-wrapper .compare-table td:first-child {
        background: var(--theme-bg-light, #f8f9fa);
        text-align: start;
        font-weight: 600;
        min-width: 160px;
        max-width: 160px;
        color: var(--theme-text-primary);
    }
    .gs-compare-wrapper .compare-table .td-title {
        margin: 0;
        font-size: 14px;
        font-weight: 600;
        color: var(--theme-text-primary);
    }
    .gs-compare-wrapper .compare-table .product-img-wrapper {
        display: inline-block;
        position: relative;
        cursor: zoom-in;
    }
    .gs-compare-wrapper .compare-table .product-img-wrapper:hover .zoom-icon {
        opacity: 1;
    }
    .gs-compare-wrapper .compare-table .product-img-wrapper .zoom-icon {
        position: absolute;
        bottom: 8px;
        right: 8px;
        background: var(--overlay-dark, rgba(0,0,0,0.6));
        color: var(--color-surface, #fff);
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .gs-compare-wrapper .compare-table .product-img {
        width: 100px;
        height: 100px;
        object-fit: contain;
        border-radius: 8px;
        border: 1px solid var(--theme-border-light, #e8e0d0);
        padding: 8px;
        background: var(--color-surface, #fff);
        transition: transform 0.3s;
    }
    .gs-compare-wrapper .compare-table .product-img-wrapper:hover .product-img {
        border-color: var(--theme-primary);
    }
    /* Image Modal */
    .compare-img-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: var(--overlay-dark-strong, rgba(0,0,0,0.9));
        cursor: zoom-out;
    }
    .compare-img-modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .compare-img-modal .modal-content {
        max-width: 90%;
        max-height: 90%;
        object-fit: contain;
        border-radius: 8px;
        animation: zoomIn 0.3s ease;
    }
    .compare-img-modal .close-modal {
        position: absolute;
        top: 20px;
        right: 30px;
        color: var(--color-surface, #fff);
        font-size: 35px;
        font-weight: bold;
        cursor: pointer;
        transition: 0.3s;
        z-index: 10000;
    }
    .compare-img-modal .close-modal:hover {
        color: var(--theme-danger, #c53030);
    }
    .compare-img-modal .img-caption {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        color: var(--color-surface, #fff);
        font-size: 16px;
        text-align: center;
        background: var(--overlay-dark, rgba(0,0,0,0.5));
        padding: 10px 20px;
        border-radius: 6px;
    }
    @keyframes zoomIn {
        from { transform: scale(0.8); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
    .gs-compare-wrapper .compare-table .product-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--theme-text-primary);
        margin: 0;
        line-height: 1.4;
        transition: color 0.3s;
    }
    .gs-compare-wrapper .compare-table .product-title:hover {
        color: var(--theme-primary);
    }
    .gs-compare-wrapper .compare-table .product-price {
        font-size: 18px;
        font-weight: 700;
        color: var(--theme-primary);
    }
    .gs-compare-wrapper .compare-table .table-value {
        font-size: 14px;
        color: var(--theme-text-secondary, #555);
    }
    .gs-compare-wrapper .compare-table .btn-remove {
        background: var(--theme-danger, #c53030);
        color: var(--color-surface, #fff);
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }
    .gs-compare-wrapper .compare-table .btn-remove:hover {
        background: var(--theme-danger-hover, #9b2c2c);
        color: var(--color-surface, #fff);
    }
    .gs-compare-wrapper .compare-table .btn-cart {
        background: var(--theme-primary);
        color: var(--color-surface, #fff);
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s;
        display: inline-block;
        text-decoration: none;
    }
    .gs-compare-wrapper .compare-table .btn-cart:hover {
        opacity: 0.9;
        color: var(--color-surface, #fff);
    }
    .gs-compare-wrapper .compare-table .btn-cart.disabled {
        background: var(--theme-text-muted, #6c757d);
        cursor: not-allowed;
    }
    .gs-compare-wrapper .compare-table .stock-available {
        color: var(--theme-success, #10b981);
        font-weight: 500;
    }
    .gs-compare-wrapper .compare-table .stock-out {
        color: var(--theme-danger, #c53030);
        font-weight: 500;
    }
    .gs-compare-wrapper .compare-table .stock-unlimited {
        color: var(--theme-info, #2c7a7b);
        font-weight: 500;
    }
    .gs-compare-wrapper .compare-empty {
        text-align: center;
        padding: 80px 20px;
    }
    .gs-compare-wrapper .compare-empty i {
        font-size: 80px;
        color: var(--theme-border, #d4c4a8);
        margin-bottom: 30px;
    }
    .gs-compare-wrapper .compare-empty h2 {
        color: var(--theme-text-primary);
        margin-bottom: 15px;
    }
    .gs-compare-wrapper .compare-empty p {
        color: var(--theme-text-muted, #7a6f5f);
        margin-bottom: 30px;
    }
    @media (max-width: 768px) {
        .gs-compare-wrapper .compare-table td {
            padding: 12px 15px;
            min-width: 150px;
        }
        .gs-compare-wrapper .compare-table td:first-child {
            min-width: 120px;
            max-width: 120px;
        }
        .gs-compare-wrapper .compare-table .product-img {
            width: 80px;
            height: 80px;
        }
    }
</style>
@endpush

@section('content')
    <section class="gs-breadcrumb-section">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-title">@lang('Compare')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="{{ route('catalog-item.compare') }}">@lang('Compare')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <div class="gs-compare-wrapper muaadh-section-gray">
        <div class="container">
            @if (isset($products) && count($products) > 0)
                @php
                    $processedProducts = [];
                    foreach ($products as $mpId => $product) {
                        $merchantItem = $product['merchant_item'] ?? $product['merchant_product'] ?? null;
                        $productItem = $product['item'];

                        if ($merchantItem) {
                            $compareProductUrl = $productItem->slug
                                ? route('front.catalog-item', ['slug' => $productItem->slug, 'merchant_id' => $merchantItem->user_id, 'merchant_item_id' => $merchantItem->id])
                                : '#';
                        } else {
                            $compareProductUrl = $productItem->slug ? route('front.catalog-item.legacy', $productItem->slug) : '#';
                        }

                        $processedProducts[$mpId] = [
                            'item' => $productItem,
                            'merchant_item' => $merchantItem,
                            'url' => $compareProductUrl,
                            'merchant_item_id' => $mpId,
                        ];
                    }
                @endphp

                <div class="table-responsive">
                    <table class="compare-table">
                        {{-- Image Row --}}
                        <tr>
                            <td><h6 class="td-title">@lang('Product Image')</h6></td>
                            @foreach ($processedProducts as $mpId => $product)
                                @php
                                    $imgSrc = filter_var($product['item']->photo, FILTER_VALIDATE_URL)
                                        ? $product['item']->photo
                                        : ($product['item']->photo ? \Illuminate\Support\Facades\Storage::url($product['item']->photo) : asset('assets/images/noimage.png'));
                                @endphp
                                <td>
                                    <div class="product-img-wrapper" data-img="{{ $imgSrc }}" data-title="{{ getLocalizedProductName($product['item']) }}">
                                        <img class="product-img" src="{{ $imgSrc }}" alt="{{ getLocalizedProductName($product['item']) }}">
                                        <span class="zoom-icon"><i class="fas fa-search-plus"></i></span>
                                    </div>
                                </td>
                            @endforeach
                        </tr>

                        {{-- Name Row --}}
                        <tr>
                            <td><h6 class="td-title">@lang('Product Name')</h6></td>
                            @foreach ($processedProducts as $mpId => $product)
                                <td>
                                    <a href="{{ $product['url'] }}">
                                        <h6 class="product-title">{{ getLocalizedProductName($product['item']) }}</h6>
                                    </a>
                                </td>
                            @endforeach
                        </tr>

                        {{-- Price Row --}}
                        <tr>
                            <td><h6 class="td-title">@lang('Price')</h6></td>
                            @foreach ($processedProducts as $mpId => $product)
                                <td>
                                    <span class="product-price">
                                        @if ($product['merchant_item'])
                                            {{ PriceHelper::showPrice($product['merchant_item']->price) }}
                                        @else
                                            {{ $product['item']->showPrice() }}
                                        @endif
                                    </span>
                                </td>
                            @endforeach
                        </tr>

                        {{-- Vendor Row --}}
                        <tr>
                            <td><h6 class="td-title">@lang('Vendor')</h6></td>
                            @foreach ($processedProducts as $mpId => $product)
                                <td>
                                    <span class="table-value">{{ $product['merchant_item'] && $product['merchant_item']->user ? getLocalizedShopName($product['merchant_item']->user) : __('N/A') }}</span>
                                </td>
                            @endforeach
                        </tr>

                        {{-- Brand Row --}}
                        <tr>
                            <td><h6 class="td-title">@lang('Brand')</h6></td>
                            @foreach ($processedProducts as $mpId => $product)
                                <td>
                                    <span class="table-value">{{ $product['item']->brand ? getLocalizedBrandName($product['item']->brand) : __('N/A') }}</span>
                                </td>
                            @endforeach
                        </tr>

                        {{-- Quality Brand Row --}}
                        <tr>
                            <td><h6 class="td-title">@lang('Quality Brand')</h6></td>
                            @foreach ($processedProducts as $mpId => $product)
                                <td>
                                    <span class="table-value">{{ $product['merchant_item'] && $product['merchant_item']->qualityBrand ? $product['merchant_item']->qualityBrand->display_name : __('N/A') }}</span>
                                </td>
                            @endforeach
                        </tr>

                        {{-- SKU Row --}}
                        <tr>
                            <td><h6 class="td-title">@lang('SKU')</h6></td>
                            @foreach ($processedProducts as $mpId => $product)
                                <td>
                                    <span class="table-value" style="font-family: monospace;">{{ $product['item']->sku ?? __('N/A') }}</span>
                                </td>
                            @endforeach
                        </tr>

                        {{-- Stock Row --}}
                        <tr>
                            <td><h6 class="td-title">@lang('Stock')</h6></td>
                            @foreach ($processedProducts as $mpId => $product)
                                @php
                                    $compareStock = $product['merchant_item'] ? $product['merchant_item']->stock : null;
                                    if ($compareStock === null || $compareStock === '') {
                                        $stockText = __('Unlimited');
                                        $stockClass = 'stock-unlimited';
                                    } elseif ($compareStock == 0) {
                                        $stockText = __('Out Of Stock');
                                        $stockClass = 'stock-out';
                                    } else {
                                        $stockText = $compareStock . ' ' . __('Available');
                                        $stockClass = 'stock-available';
                                    }
                                @endphp
                                <td><span class="table-value {{ $stockClass }}">{{ $stockText }}</span></td>
                            @endforeach
                        </tr>

                        {{-- Rating Row --}}
                        <tr>
                            <td><h6 class="td-title">@lang('Rating')</h6></td>
                            @foreach ($processedProducts as $mpId => $product)
                                @php
                                    $productWithRatings = App\Models\CatalogItem::withCount('catalogReviews')
                                        ->withAvg('catalogReviews', 'rating')
                                        ->find($product['item']->id);
                                    $avgRating = $productWithRatings->catalog_reviews_avg_rating ?? 0;
                                    $ratingCount = $productWithRatings->catalog_reviews_count ?? 0;
                                @endphp
                                <td>
                                    <span class="table-value">
                                        <i class="fas fa-star text-warning"></i>
                                        {{ number_format($avgRating, 1) }}
                                        <small class="text-muted">({{ $ratingCount }})</small>
                                    </span>
                                </td>
                            @endforeach
                        </tr>

                        {{-- Description Row --}}
                        <tr>
                            <td><h6 class="td-title">@lang('Description')</h6></td>
                            @foreach ($processedProducts as $mpId => $product)
                                <td>
                                    <span class="table-value" style="text-align: start; display: block;">
                                        {{ Str::limit(strip_tags($product['item']->details), 150) }}
                                    </span>
                                </td>
                            @endforeach
                        </tr>

                        {{-- Add to Cart Row --}}
                        <tr>
                            <td><h6 class="td-title">@lang('Action')</h6></td>
                            @foreach ($processedProducts as $mpId => $product)
                                <td>
                                    @php
                                        $mp = $product['merchant_item'];
                                    @endphp
                                    {{-- product_type and affiliate_link are now on merchant_items --}}
                                    @if ($mp && $mp->product_type == 'affiliate' && $mp->affiliate_link)
                                        <a href="{{ $mp->affiliate_link }}" target="_blank" class="btn-cart">
                                            @lang('Buy Now')
                                        </a>
                                    @else
                                        @php
                                            $stockEmpty = $mp
                                                ? (($mp->stock ?? 0) <= 0 && !$mp->preordered)
                                                : $product['item']->emptyStock();
                                        @endphp
                                        @if ($stockEmpty)
                                            <span class="btn-cart disabled">@lang('Out Of Stock')</span>
                                        @else
                                            @if ($product['item']->type != 'Listing' && $mp)
                                                {{-- UNIFIED: Use m-cart-add with merchant_item_id --}}
                                                <button type="button"
                                                    class="m-cart-add btn-cart"
                                                    data-merchant-item-id="{{ $mp->id }}"
                                                    data-vendor-id="{{ $mp->user_id }}"
                                                    data-min-qty="{{ max(1, (int)($mp->minimum_qty ?? 1)) }}">
                                                    <i class="fas fa-shopping-cart"></i> @lang('Add To Cart')
                                                </button>
                                            @endif
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                        </tr>

                        {{-- Shipping Quote Row --}}
                        <tr>
                            <td><h6 class="td-title">@lang('Shipping')</h6></td>
                            @foreach ($processedProducts as $mpId => $product)
                                <td>
                                    @if ($product['item']->type == 'Physical' && $product['merchant_item'])
                                        <x-shipping-quote-button
                                            :vendor-id="$product['merchant_item']->user_id"
                                            :product-name="getLocalizedProductName($product['item'])"
                                        />
                                    @else
                                        <span class="table-value">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>

                        {{-- Remove Row --}}
                        <tr>
                            <td><h6 class="td-title">@lang('Remove')</h6></td>
                            @foreach ($processedProducts as $mpId => $product)
                                <td>
                                    <a href="{{ route('catalog-item.compare.remove', $mpId) }}" class="btn-remove">
                                        <i class="fas fa-trash-alt"></i> @lang('Remove')
                                    </a>
                                </td>
                            @endforeach
                        </tr>
                    </table>
                </div>
            @else
                <div class="compare-empty">
                    <i class="fas fa-exchange-alt"></i>
                    <h2>@lang('Nothing to Compare')</h2>
                    <p>@lang('Add products to compare their features and find the best one for you.')</p>
                    <a href="{{ route('front.index') }}" class="template-btn">@lang('Continue Shopping')</a>
                </div>
            @endif
        </div>
    </div>

    {{-- Image Zoom Modal --}}
    <div class="compare-img-modal" id="compareImgModal">
        <span class="close-modal">&times;</span>
        <img class="modal-content" id="modalImg">
        <div class="img-caption" id="modalCaption"></div>
    </div>
@endsection

@push('js')
<script>
$(document).ready(function() {
    var modal = $('#compareImgModal');
    var modalImg = $('#modalImg');
    var captionText = $('#modalCaption');

    // Open modal on image click
    $('.product-img-wrapper').on('click', function() {
        var imgSrc = $(this).data('img');
        var title = $(this).data('title');

        modal.addClass('active');
        modalImg.attr('src', imgSrc);
        captionText.text(title);

        // Prevent body scroll
        $('body').css('overflow', 'hidden');
    });

    // Close modal on X click
    $('.close-modal').on('click', function() {
        modal.removeClass('active');
        $('body').css('overflow', '');
    });

    // Close modal on background click
    modal.on('click', function(e) {
        if (e.target === this) {
            modal.removeClass('active');
            $('body').css('overflow', '');
        }
    });

    // Close modal on ESC key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && modal.hasClass('active')) {
            modal.removeClass('active');
            $('body').css('overflow', '');
        }
    });
});
</script>
@endpush
