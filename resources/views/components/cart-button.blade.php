{{--
    Unified Cart Button Component
    =============================
    Single source of truth for all cart operations.

    REQUIRED: merchant_item_id (everything else derived from MerchantItem)

    Usage:
    <x-cart-button :mp="$merchantItem" />
    <x-cart-button :mp="$merchantItem" mode="compact" />
    <x-cart-button :mp="$merchantItem" mode="full" :show-qty="true" />
--}}

@props([
    'mp' => null,              // MerchantItem instance (REQUIRED)
    'mode' => 'full',          // 'full' | 'compact' | 'icon-only'
    'showQty' => true,         // Show quantity selector
    'class' => '',             // Additional CSS classes
])

@php
    // STRICT: MerchantItem is REQUIRED - fail if not provided
    if (!$mp) {
        throw new \LogicException('cart-button component requires $mp (MerchantItem) to be provided');
    }

    // Extract all data from MerchantItem - NO FALLBACK
    $mpId = $mp->id;
    $merchantUserId = $mp->user_id;
    $catalogItemId = $mp->catalog_item_id;

    // Pricing
    $price = (float) $mp->price;
    $previousPrice = (float) ($mp->previous_price ?? 0);

    // Stock & Availability
    $stock = (int) ($mp->stock ?? 0);
    $preordered = (bool) ($mp->preordered ?? false);
    $inStock = $stock > 0 || $preordered;

    // Quantity constraints
    $minQty = max(1, (int) ($mp->minimum_qty ?? 1));
    $maxQty = $preordered ? 9999 : max($minQty, $stock);

    // Sizes (from MerchantItem)
    $sizes = [];
    $sizeQtys = [];
    $sizePrices = [];
    if (!empty($mp->size)) {
        $sizesRaw = is_array($mp->size) ? $mp->size : array_map('trim', explode(',', $mp->size));
        $qtysRaw = !empty($mp->size_qty) ? (is_array($mp->size_qty) ? $mp->size_qty : array_map('trim', explode(',', $mp->size_qty))) : [];
        $pricesRaw = !empty($mp->size_price) ? (is_array($mp->size_price) ? $mp->size_price : array_map('trim', explode(',', $mp->size_price))) : [];

        foreach ($sizesRaw as $i => $sz) {
            if (trim($sz) !== '') {
                $sizes[] = trim($sz);
                $sizeQtys[] = (int) ($qtysRaw[$i] ?? 0);
                $sizePrices[] = (float) ($pricesRaw[$i] ?? 0);
            }
        }
    }
    $hasSizes = count($sizes) > 0;

    // Colors (from MerchantItem)
    $colors = [];
    $colorPrices = [];
    if (!empty($mp->color_all)) {
        $colorsRaw = is_array($mp->color_all) ? $mp->color_all : array_map('trim', explode(',', $mp->color_all));
        $colorPricesRaw = !empty($mp->color_price) ? (is_array($mp->color_price) ? $mp->color_price : array_map('trim', explode(',', $mp->color_price))) : [];

        foreach ($colorsRaw as $i => $clr) {
            $clr = ltrim(trim($clr), '#');
            if ($clr !== '') {
                $colors[] = $clr;
                $colorPrices[] = (float) ($colorPricesRaw[$i] ?? 0);
            }
        }
    }
    $hasColors = count($colors) > 0;

    // Weight & Dimensions
    $weight = (float) ($mp->weight ?? 0);
    $length = (float) ($mp->length ?? 0);
    $width = (float) ($mp->width ?? 0);
    $height = (float) ($mp->height ?? 0);

    // CatalogItem info (for display only)
    $catalogItem = $mp->catalogItem;
    $productName = $catalogItem ? $catalogItem->showName() : '';
    $productType = $catalogItem ? $catalogItem->type : 'Physical';

    // Unique ID for this instance
    $uniqueId = 'cart_' . $mpId . '_' . uniqid();

    // Default selections
    $defaultSize = $hasSizes ? $sizes[0] : '';
    $defaultColor = $hasColors ? $colors[0] : '';
    $defaultQty = $minQty;

    // Find first available size with stock
    if ($hasSizes) {
        foreach ($sizes as $i => $sz) {
            if ($sizeQtys[$i] > 0) {
                $defaultSize = $sz;
                break;
            }
        }
    }
@endphp

<div class="m-cart-button {{ $class }}"
     id="{{ $uniqueId }}"
     data-mp-id="{{ $mpId }}"
     data-merchant-user-id="{{ $merchantUserId }}"
     data-catalog-item-id="{{ $catalogItemId }}"
     data-price="{{ $price }}"
     data-stock="{{ $stock }}"
     data-preordered="{{ $preordered ? '1' : '0' }}"
     data-min-qty="{{ $minQty }}"
     data-max-qty="{{ $maxQty }}"
     data-weight="{{ $weight }}"
     data-sizes="{{ json_encode($sizes) }}"
     data-size-qtys="{{ json_encode($sizeQtys) }}"
     data-size-prices="{{ json_encode($sizePrices) }}"
     data-colors="{{ json_encode($colors) }}"
     data-color-prices="{{ json_encode($colorPrices) }}"
     data-in-stock="{{ $inStock ? '1' : '0' }}"
     data-product-type="{{ $productType }}">

    @if (!$inStock)
        {{-- Out of Stock State --}}
        <button type="button" class="m-cart-button__btn m-cart-button__btn--disabled" disabled>
            <i class="fas fa-ban"></i>
            <span>@lang('Out of Stock')</span>
        </button>
    @else
        {{-- Size Selector --}}
        @if ($hasSizes && $mode === 'full')
            <div class="m-cart-button__sizes">
                <label class="m-cart-button__label">@lang('Size'):</label>
                <div class="m-cart-button__size-options">
                    @foreach ($sizes as $i => $sz)
                        @php
                            $sizeStock = $sizeQtys[$i] ?? 0;
                            $sizeAvailable = $sizeStock > 0 || $preordered;
                            $isDefault = ($sz === $defaultSize);
                        @endphp
                        <button type="button"
                                class="m-cart-button__size {{ $isDefault ? 'active' : '' }} {{ !$sizeAvailable ? 'disabled' : '' }}"
                                data-size="{{ $sz }}"
                                data-size-qty="{{ $sizeStock }}"
                                data-size-price="{{ $sizePrices[$i] ?? 0 }}"
                                {{ !$sizeAvailable ? 'disabled' : '' }}>
                            {{ $sz }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Color Selector --}}
        @if ($hasColors && $mode === 'full')
            <div class="m-cart-button__colors">
                <label class="m-cart-button__label">@lang('Color'):</label>
                <div class="m-cart-button__color-options">
                    @foreach ($colors as $i => $clr)
                        @php $isDefault = ($clr === $defaultColor); @endphp
                        <button type="button"
                                class="m-cart-button__color {{ $isDefault ? 'active' : '' }}"
                                data-color="{{ $clr }}"
                                data-color-price="{{ $colorPrices[$i] ?? 0 }}"
                                style="background-color: #{{ $clr }};"
                                title="#{{ $clr }}">
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Quantity Selector --}}
        @if ($showQty && $productType === 'Physical')
            <div class="m-cart-button__qty">
                <label class="m-cart-button__label">@lang('Qty'):</label>
                <div class="m-cart-button__qty-control">
                    <button type="button" class="m-cart-button__qty-btn m-cart-button__qty-minus" data-action="decrease">
                        <i class="fas fa-minus"></i>
                    </button>
                    <input type="text"
                           class="m-cart-button__qty-input"
                           value="{{ $defaultQty }}"
                           data-min="{{ $minQty }}"
                           data-max="{{ $maxQty }}"
                           readonly>
                    <button type="button" class="m-cart-button__qty-btn m-cart-button__qty-plus" data-action="increase">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                @if ($minQty > 1)
                    <small class="m-cart-button__min-notice">@lang('Min'): {{ $minQty }}</small>
                @endif
            </div>
        @endif

        {{-- Add to Cart Button --}}
        <button type="button" class="m-cart-button__btn m-cart-button__add" data-action="add-to-cart">
            @if ($mode === 'icon-only')
                <i class="fas fa-shopping-cart"></i>
            @elseif ($mode === 'compact')
                <i class="fas fa-cart-plus"></i>
                <span>@lang('Add')</span>
            @else
                <i class="fas fa-cart-plus"></i>
                <span>@lang('Add to Cart')</span>
            @endif
        </button>

        {{-- Loading State --}}
        <div class="m-cart-button__loading" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
    @endif
</div>
