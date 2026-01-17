{{--
    Cart Item Row
    Variables: $item (array from CartItem::toArray()), $issue (optional validation issue)
--}}
@php
    use Illuminate\Support\Facades\Storage;

    $key = $item['key'] ?? '';
    $domKey = str_replace([':', '#', '.', ' ', '/', '\\'], '_', $key);

    // Item data
    $name = app()->getLocale() === 'ar' && !empty($item['name_ar']) ? $item['name_ar'] : ($item['name'] ?? '');
    $photo = $item['photo'] ?? '';
    $photoUrl = $photo ? Storage::url($photo) : asset('assets/images/noimage.png');
    $slug = $item['slug'] ?? '';
    $partNumber = $item['part_number'] ?? '';

    // Brand (OEM - نيسان، تويوتا...)
    $brandName = app()->getLocale() === 'ar' && !empty($item['brand_name_ar'])
        ? $item['brand_name_ar']
        : ($item['brand_name'] ?? '');
    $brandLogo = $item['brand_logo'] ?? null; // Already full URL from photo_url accessor

    // Quality Brand (أصلي، بديل...)
    $qualityBrandName = app()->getLocale() === 'ar' && !empty($item['quality_brand_name_ar'])
        ? $item['quality_brand_name_ar']
        : ($item['quality_brand_name'] ?? '');
    $qualityBrandLogo = $item['quality_brand_logo'] ?? null; // Already full URL from logo_url accessor

    // Merchant info
    $merchantId = (int) ($item['merchant_id'] ?? 0);
    $merchantItemId = (int) ($item['merchant_item_id'] ?? 0);
    $merchantName = app()->getLocale() === 'ar' && !empty($item['merchant_name_ar'])
        ? $item['merchant_name_ar']
        : ($item['merchant_name'] ?? '');

    // Pricing
    $unitPrice = (float) ($item['effective_price'] ?? $item['unit_price'] ?? 0);
    $totalPrice = (float) ($item['total_price'] ?? 0);
    $discountPercent = 0.0;
    // Calculate discount from wholesale if any
    if (!empty($item['whole_sell_qty']) && !empty($item['whole_sell_discount'])) {
        $qty = (int) ($item['qty'] ?? 1);
        foreach ($item['whole_sell_qty'] as $i => $threshold) {
            if ($qty >= (int) $threshold && isset($item['whole_sell_discount'][$i])) {
                $discountPercent = (float) $item['whole_sell_discount'][$i];
            }
        }
    }

    // Quantity & Stock
    $qty = (int) ($item['qty'] ?? 1);
    $minQty = (int) ($item['min_qty'] ?? 1);
    $stock = (int) ($item['stock'] ?? 0);
    $preordered = (bool) ($item['preordered'] ?? false);

    // Variants
    $size = $item['size'] ?? null;
    $color = $item['color'] ?? null;

    // Item URL
    $itemUrl = ($slug && $merchantId && $merchantItemId)
        ? route('front.catalog-item', ['slug' => $slug, 'merchant_id' => $merchantId, 'merchant_item_id' => $merchantItemId])
        : '#';

    // Has issue?
    $hasIssue = !empty($issue);
    $issueType = $issue['type'] ?? '';
    $issueMessage = $issue['message'] ?? '';
@endphp

<div class="m-cart__item {{ $hasIssue ? 'm-cart__item--has-issue' : '' }}"
     id="cart-row-{{ $domKey }}"
     data-cart-key="{{ $key }}"
     data-merchant-id="{{ $merchantId }}">

    {{-- Issue Banner --}}
    @if($hasIssue)
        <div class="m-cart__item-issue m-cart__item-issue--{{ $issueType }}">
            <i class="fas fa-exclamation-triangle"></i>
            <span>{{ $issueMessage }}</span>
        </div>
    @endif

    {{-- Item Image --}}
    <div class="m-cart__item-image">
        <a href="{{ $itemUrl }}">
            <img src="{{ $photoUrl }}"
                 alt="{{ $name }}"
                 loading="lazy"
                 onerror="this.onerror=null; this.src='{{ asset('assets/images/noimage.png') }}';">
        </a>
    </div>

    {{-- Item Details --}}
    <div class="m-cart__item-details">
        <a href="{{ $itemUrl }}" class="m-cart__item-name">
            {{ Str::limit($name, 60) }}
        </a>

        {{-- Part Number --}}
        <div class="m-cart__item-meta">
            @if($partNumber)
                <span class="m-cart__item-part_number">{{ $partNumber }}</span>
            @endif
        </div>

        {{-- Brand & Quality Brand Badges --}}
        <div class="m-cart__item-brands">
            @if($brandName)
                <span class="m-cart__item-brand" title="@lang('Brand')">
                    @if($brandLogo)
                        <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="m-cart__brand-logo">
                    @endif
                    <span>{{ $brandName }}</span>
                </span>
            @endif
            @if($qualityBrandName)
                <span class="m-cart__item-quality" title="@lang('Quality')">
                    @if($qualityBrandLogo)
                        <img src="{{ $qualityBrandLogo }}" alt="{{ $qualityBrandName }}" class="m-cart__quality-logo">
                    @endif
                    <span>{{ $qualityBrandName }}</span>
                </span>
            @endif
        </div>

        {{-- Variants --}}
        @if (!empty($size) || !empty($color))
            <div class="m-cart__item-variants">
                @if (!empty($color))
                    <span class="m-cart__item-color" style="--swatch-color: #{{ ltrim($color, '#') }};"></span>
                @endif
                @if (!empty($size))
                    <span class="m-cart__item-size">{{ $size }}</span>
                @endif
            </div>
        @endif

        {{-- Preorder Badge --}}
        @if ($preordered)
            <span class="m-cart__item-preorder">
                <i class="fas fa-clock"></i> @lang('Preorder')
            </span>
        @endif

        {{-- Discount Badge --}}
        @if ($discountPercent > 0)
            <span class="m-cart__item-discount-badge">
                -{{ number_format($discountPercent, 0) }}%
            </span>
        @endif

        {{-- Mobile Price --}}
        <div class="m-cart__item-price-mobile">
            {{ monetaryUnit()->convertAndFormat($unitPrice) }}
        </div>
    </div>

    {{-- Unit Price (Desktop) --}}
    <div class="m-cart__item-price">
        {{ monetaryUnit()->convertAndFormat($unitPrice) }}
    </div>

    {{-- Quantity Controls --}}
    <div class="m-cart__item-qty">
        <div class="m-cart__qty-controls">
            <button type="button"
                    class="m-cart__qty-btn m-cart__qty-btn--decrease"
                    data-action="decrease"
                    data-cart-key="{{ $key }}"
                    {{ $qty <= $minQty ? 'disabled' : '' }}>
                <i class="fas fa-minus"></i>
            </button>
            <input type="number"
                   class="m-cart__qty-input"
                   value="{{ $qty }}"
                   min="{{ $minQty }}"
                   max="{{ $preordered || $stock <= 0 ? 9999 : $stock }}"
                   data-cart-key="{{ $key }}"
                   readonly>
            <button type="button"
                    class="m-cart__qty-btn m-cart__qty-btn--increase"
                    data-action="increase"
                    data-cart-key="{{ $key }}"
                    {{ !$preordered && $stock > 0 && $qty >= $stock ? 'disabled' : '' }}>
                <i class="fas fa-plus"></i>
            </button>
        </div>

        {{-- Stock Info --}}
        @if (!$preordered && $stock > 0)
            <div class="m-cart__stock-info {{ $stock <= 5 ? 'm-cart__stock-info--low' : '' }}">
                @if ($stock <= 5)
                    <i class="fas fa-exclamation-circle"></i>
                    @lang('Only') {{ $stock }} @lang('left')
                @else
                    <i class="fas fa-check-circle"></i>
                    @lang('In Stock')
                @endif
            </div>
        @endif
    </div>

    {{-- Total Price --}}
    <div class="m-cart__item-total">
        <span class="m-cart__item-total-value" data-unit-price="{{ $unitPrice }}">
            {{ monetaryUnit()->convertAndFormat($totalPrice) }}
        </span>
    </div>

    {{-- Remove Button --}}
    <div class="m-cart__item-actions">
        <button type="button"
                class="m-cart__remove-btn"
                data-action="remove"
                data-cart-key="{{ $key }}"
                title="@lang('Remove')">
            <i class="fas fa-trash-alt"></i>
        </button>
    </div>
</div>
