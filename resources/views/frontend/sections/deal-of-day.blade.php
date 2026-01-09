{{--
================================================================================
SECTION PARTIAL: Deal of the Day
================================================================================
Receives: $merchantItem (MerchantItem model with catalogItem loaded)
OR legacy: $catalogItem (CatalogItem model with bestMerchant loaded)
================================================================================
--}}

@php
    // Support both new (merchantItem) and legacy (catalogItem) approaches
    if (isset($merchantItem) && $merchantItem instanceof \App\Models\MerchantItem) {
        $mp = $merchantItem;
        $actualProduct = $merchantItem->catalogItem;
    } else {
        $actualProduct = $catalogItem ?? null;
        $mp = $actualProduct->bestMerchant ?? $actualProduct->merchantItems->first() ?? null;
    }

    if (!$actualProduct || !$mp) {
        return;
    }

    // Load brand if not loaded
    if (!$actualProduct->relationLoaded('brand')) {
        $actualProduct->load('brand');
    }

    $mainPhoto = filter_var($actualProduct->photo ?? '', FILTER_VALIDATE_URL)
        ? $actualProduct->photo
        : (($actualProduct->photo ?? null) ? \Illuminate\Support\Facades\Storage::url($actualProduct->photo) : asset('assets/images/noimage.png'));

    // Prices come from merchant_items
    $price = $mp->price ?? 0;
    $previousPrice = $mp->previous_price ?? null;
    $discountDate = $mp->discount_date ?? null;

    // Build catalogItem URL with merchant context
    $catalogItemUrl = route('front.catalog-item', [
        'slug' => $actualProduct->slug,
        'merchant_id' => $mp->user_id,
        'merchant_item_id' => $mp->id
    ]);

    // Brand info (from catalogItem)
    $brandName = $actualProduct->brand?->localized_name;
    $brandLogo = $actualProduct->brand?->photo_url;

    // Quality Brand info (from merchant_item)
    $qualityBrandName = $mp->qualityBrand?->localized_name;
    $qualityBrandLogo = $mp->qualityBrand?->logo_url;

    // Merchant info (from merchant_item)
    $merchantName = $mp->user ? getLocalizedShopName($mp->user) : null;

    // Stock info for Add to Cart
    $stockQty = (int)($mp->stock ?? 0);
    $inStock = $stockQty > 0 || $mp->preordered;
    $minQty = max(1, (int)($mp->minimum_qty ?? 1));
    $preordered = $mp->preordered ?? false;
    $affiliateItemType = $mp->item_type ?? null;
    $affiliateLink = $mp->affiliate_link ?? null;
@endphp

<div class="muaadh-deal-card">
    <div class="row align-items-center">
        <div class="col-md-5">
            <div class="muaadh-deal-img">
                <a href="{{ $catalogItemUrl }}">
                    <img src="{{ $mainPhoto }}" alt="{{ $actualProduct->name }}" loading="lazy">
                </a>
                @if($previousPrice && $previousPrice > $price)
                    @php
                        $discount = round((($previousPrice - $price) / $previousPrice) * 100);
                    @endphp
                    <span class="muaadh-deal-badge">-{{ $discount }}%</span>
                @endif
            </div>
        </div>
        <div class="col-md-7">
            <div class="muaadh-deal-content">
                <h3 class="muaadh-deal-title">
                    <a href="{{ $catalogItemUrl }}">{{ $actualProduct->showName() }}</a>
                </h3>

                {{-- Brand, Quality Brand & Merchant Info --}}
                <div class="muaadh-deal-meta mb-2">
                    @if($brandName)
                        <span class="badge bg-secondary me-1">
                            @if($brandLogo)
                                <img src="{{ $brandLogo }}" alt="" class="deal-brand-logo me-1" style="height: 16px; width: auto;">
                            @endif
                            {{ $brandName }}
                        </span>
                    @endif
                    @if($qualityBrandName)
                        <span class="badge bg-info text-dark me-1">
                            @if($qualityBrandLogo)
                                <img src="{{ $qualityBrandLogo }}" alt="" class="deal-quality-logo me-1" style="height: 16px; width: auto;">
                            @endif
                            {{ $qualityBrandName }}
                        </span>
                    @endif
                    @if($merchantName)
                        <span class="badge bg-primary">
                            <i class="fas fa-store me-1"></i>
                            {{ $merchantName }}
                        </span>
                    @endif
                </div>

                <div class="muaadh-deal-price">
                    <span class="muaadh-price-current">{{ \App\Models\CatalogItem::convertPrice($price) }}</span>
                    @if($previousPrice && $previousPrice > $price)
                        <span class="muaadh-price-old">{{ \App\Models\CatalogItem::convertPrice($previousPrice) }}</span>
                    @endif
                </div>

                @if($discountDate)
                <div class="muaadh-deal-countdown" id="deal-countdown-{{ $mp->id }}" data-countdown="{{ $discountDate }}">
                    <div class="muaadh-countdown-item">
                        <span class="muaadh-countdown-value days">00</span>
                        <span class="muaadh-countdown-label">@lang('Days')</span>
                    </div>
                    <div class="muaadh-countdown-item">
                        <span class="muaadh-countdown-value hours">00</span>
                        <span class="muaadh-countdown-label">@lang('Hours')</span>
                    </div>
                    <div class="muaadh-countdown-item">
                        <span class="muaadh-countdown-value minutes">00</span>
                        <span class="muaadh-countdown-label">@lang('Mins')</span>
                    </div>
                    <div class="muaadh-countdown-item">
                        <span class="muaadh-countdown-value seconds">00</span>
                        <span class="muaadh-countdown-label">@lang('Secs')</span>
                    </div>
                </div>
                @endif

                {{-- Add to Cart Button --}}
                @if ($affiliateItemType !== 'affiliate')
                    @if ($inStock)
                        <button type="button" class="muaadh-btn muaadh-btn-primary m-cart-add"
                            data-merchant-item-id="{{ $mp->id }}"
                            data-merchant-user-id="{{ $mp->user_id }}"
                            data-catalog-item-id="{{ $actualProduct->id }}"
                            data-min-qty="{{ $minQty }}"
                            data-stock="{{ $stockQty }}"
                            data-preordered="{{ $preordered ? '1' : '0' }}">
                            <i class="fas fa-shopping-cart me-2"></i>
                            @lang('Add to Cart')
                        </button>
                    @else
                        <button type="button" class="muaadh-btn muaadh-btn-secondary" disabled>
                            <i class="fas fa-ban me-2"></i>
                            @lang('Out of Stock')
                        </button>
                    @endif
                @elseif ($affiliateItemType === 'affiliate' && $affiliateLink)
                    <a href="{{ $affiliateLink }}" class="muaadh-btn muaadh-btn-primary" target="_blank">
                        <i class="fas fa-external-link-alt me-2"></i>
                        @lang('Buy Now')
                    </a>
                @else
                    <a href="{{ $catalogItemUrl }}" class="muaadh-btn muaadh-btn-primary">
                        @lang('View Details')
                    </a>
                @endif

                {{-- Shipping Quote Button --}}
                <x-shipping-quote-button
                    :merchant-user-id="$mp->user_id"
                    :catalog-item-name="$actualProduct->showName()"
                    class="mt-2"
                />
            </div>
        </div>
    </div>
</div>

{{-- Countdown Timer Script --}}
@if($discountDate)
@push('scripts')
<script>
(function() {
    var countdownEl = document.getElementById('deal-countdown-{{ $mp->id }}');
    if (!countdownEl) return;

    var endDate = new Date('{{ $discountDate }}T23:59:59').getTime();

    function updateCountdown() {
        var now = new Date().getTime();
        var distance = endDate - now;

        if (distance < 0) {
            countdownEl.querySelector('.days').textContent = '00';
            countdownEl.querySelector('.hours').textContent = '00';
            countdownEl.querySelector('.minutes').textContent = '00';
            countdownEl.querySelector('.seconds').textContent = '00';
            return;
        }

        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        countdownEl.querySelector('.days').textContent = days.toString().padStart(2, '0');
        countdownEl.querySelector('.hours').textContent = hours.toString().padStart(2, '0');
        countdownEl.querySelector('.minutes').textContent = minutes.toString().padStart(2, '0');
        countdownEl.querySelector('.seconds').textContent = seconds.toString().padStart(2, '0');
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
})();
</script>
@endpush
@endif
