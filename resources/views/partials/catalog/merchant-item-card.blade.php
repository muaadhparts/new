{{--
    Merchant Item Card for Unified Catalog Tree
    Variables:
    - $item: MerchantItem with joined catalog_item and parts data
    - $layout: 'list' or 'grid'
    - $class: optional CSS class for grid columns
--}}
@php
    $locale = app()->getLocale();
    $gs = cache()->remember('muaadhsettings', now()->addDay(), fn () => DB::table('muaadhsettings')->first());

    // Get item data - use joined columns for efficiency, fallback to relationship
    $catalogItem = $item->catalogItem ?? null;
    $merchantItem = $item;

    // Names - prefer joined columns
    $catalogItemName = $item->catalog_item_name ?? ($catalogItem ? $catalogItem->name : __('Unknown Part'));
    $catalogItemNameAr = $item->catalog_item_name_ar ?? ($catalogItem ? $catalogItem->name_ar : null);
    $name = $locale === 'ar' ? ($catalogItemNameAr ?: $catalogItemName) : $catalogItemName;

    // Part label from dynamic parts table (already joined)
    $partLabel = $locale === 'ar'
        ? ($item->part_label_ar ?: $item->part_label_en ?: $name)
        : ($item->part_label_en ?: $name);

    // SKU - prefer joined column
    $sku = $item->sku ?? ($catalogItem ? $catalogItem->sku : '');

    // Slug - prefer joined column
    $slug = $item->catalog_item_slug ?? ($catalogItem ? $catalogItem->slug : null);

    // Image - prefer joined columns
    $photoPath = $item->photo ?? ($catalogItem ? $catalogItem->photo : null);
    $thumbnailPath = $item->thumbnail ?? ($catalogItem ? $catalogItem->thumbnail : null);

    $photo = $photoPath
        ? asset('assets/images/catalog_items/' . $photoPath)
        : asset('assets/images/noimage.png');

    $thumbnail = $thumbnailPath
        ? asset('assets/images/thumbnails/' . $thumbnailPath)
        : $photo;

    // Price with commission
    $price = (float) $merchantItem->price;
    $finalPrice = $price + (float) $gs->fixed_commission + ($price * (float) $gs->percentage_commission / 100);

    // Stock
    $stock = $merchantItem->stock ?? 0;
    $inStock = $stock > 0;

    // Merchant - use eager loaded relationship
    $merchant = $merchantItem->user ?? null;
    $merchantName = $merchant ? ($merchant->shop_name ?: $merchant->name) : __('Unknown Seller');

    // Quality Brand - use eager loaded relationship
    $qualityBrand = $merchantItem->qualityBrand ?? null;
    $qualityName = $qualityBrand
        ? ($locale === 'ar' ? ($qualityBrand->name_ar ?: $qualityBrand->name) : $qualityBrand->name)
        : null;

    // URL - use joined slug
    $itemUrl = $slug
        ? route('front.catalog-item', [
            'slug' => $slug,
            'merchant_id' => $merchantItem->user_id,
            'merchant_item_id' => $merchantItem->id
        ])
        : '#';

    // Condition
    $isUsed = $merchantItem->product_condition == 1;
    $conditionLabel = $isUsed ? __('Used') : __('New');
@endphp

@if($layout === 'list')
    {{-- List View --}}
    <div class="col-12">
        <div class="m-product-card m-product-card--list">
            <div class="m-product-card__image-wrapper">
                <a href="{{ $itemUrl }}">
                    <img src="{{ $thumbnail }}" alt="{{ $name }}" class="m-product-card__image" loading="lazy">
                </a>
                @if($isUsed)
                    <span class="m-product-card__badge m-product-card__badge--warning">{{ $conditionLabel }}</span>
                @endif
            </div>
            <div class="m-product-card__content">
                <div class="m-product-card__header">
                    <a href="{{ $itemUrl }}" class="m-product-card__title">{{ Str::limit($partLabel, 60) }}</a>
                    @if($sku)
                        <span class="m-product-card__sku">{{ $sku }}</span>
                    @endif
                </div>
                <div class="m-product-card__meta">
                    @if($qualityName)
                        <span class="m-product-card__quality">{{ $qualityName }}</span>
                    @endif
                    <span class="m-product-card__seller">{{ $merchantName }}</span>
                </div>
                <div class="m-product-card__footer">
                    <div class="m-product-card__price-wrapper">
                        <span class="m-product-card__price">{{ \PriceHelper::showCurrencyPrice($finalPrice) }}</span>
                    </div>
                    <div class="m-product-card__stock {{ $inStock ? 'm-product-card__stock--available' : 'm-product-card__stock--out' }}">
                        @if($inStock)
                            <i class="fas fa-check-circle"></i> @lang('In Stock') ({{ $stock }})
                        @else
                            <i class="fas fa-times-circle"></i> @lang('Out of Stock')
                        @endif
                    </div>
                    <div class="m-product-card__actions">
                        <a href="{{ $itemUrl }}" class="m-btn m-btn--primary m-btn--sm">
                            <i class="fas fa-eye"></i> @lang('View')
                        </a>
                        @if($inStock)
                            <button type="button" class="m-btn m-btn--outline m-btn--sm add-to-cart"
                                    data-merchant-item-id="{{ $merchantItem->id }}">
                                <i class="fas fa-cart-plus"></i> @lang('Add')
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    {{-- Grid View --}}
    <div class="{{ $class ?? 'col-6 col-md-4 col-lg-3' }}">
        <div class="m-product-card m-product-card--grid">
            <div class="m-product-card__image-wrapper">
                <a href="{{ $itemUrl }}">
                    <img src="{{ $thumbnail }}" alt="{{ $name }}" class="m-product-card__image" loading="lazy">
                </a>
                @if($isUsed)
                    <span class="m-product-card__badge m-product-card__badge--warning">{{ $conditionLabel }}</span>
                @endif
                @if(!$inStock)
                    <span class="m-product-card__badge m-product-card__badge--danger">@lang('Out of Stock')</span>
                @endif
            </div>
            <div class="m-product-card__content">
                <a href="{{ $itemUrl }}" class="m-product-card__title">{{ Str::limit($partLabel, 40) }}</a>
                @if($sku)
                    <span class="m-product-card__sku">{{ $sku }}</span>
                @endif
                @if($qualityName)
                    <span class="m-product-card__quality">{{ $qualityName }}</span>
                @endif
                <div class="m-product-card__price-wrapper">
                    <span class="m-product-card__price">{{ \PriceHelper::showCurrencyPrice($finalPrice) }}</span>
                </div>
                <div class="m-product-card__actions">
                    <a href="{{ $itemUrl }}" class="m-btn m-btn--primary m-btn--sm w-100">
                        <i class="fas fa-eye"></i> @lang('View')
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif
