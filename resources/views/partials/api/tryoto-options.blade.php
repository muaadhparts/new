{{-- resources/views/partials/api/tryoto-options.blade.php --}}
{{-- API-based Tryoto shipping options partial - Grid Layout --}}

{{-- Variables from ShippingApiController:
     $curr, $merchantUserId, $deliveryCompany, $weight, $freeAbove, $merchantProductsTotal
--}}

@php
    $freeAboveValue = $freeAbove ?? 0;
    $merchantTotal = $merchantProductsTotal ?? $merchantCatalogitemsTotal ?? 0;
    $isFreeShipping = ($freeAboveValue > 0 && $merchantTotal >= $freeAboveValue);
    $merchantUserIdValue = $merchantUserId ?? $merchantId ?? 0;
@endphp

<div class="tryoto-options-container" data-merchant-user-id="{{ $merchantUserIdValue }}">

    {{-- ✅ Free Shipping Alert --}}
    @if($isFreeShipping)
        <div class="tryoto-free-alert tryoto-free-alert--success">
            <div class="tryoto-free-alert__icon">
                <i class="fas fa-gift"></i>
            </div>
            <div class="tryoto-free-alert__content">
                <strong>@lang('Free Shipping!')</strong>
                <span>@lang('Your order qualifies for free shipping (above') {{ $curr->sign }}{{ number_format($freeAboveValue, 2) }})</span>
            </div>
        </div>
    @elseif($freeAboveValue > 0)
        <div class="tryoto-free-alert tryoto-free-alert--info">
            <div class="tryoto-free-alert__icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="tryoto-free-alert__content">
                <strong>@lang('Free shipping on orders above') {{ $curr->sign }}{{ number_format($freeAboveValue, 2) }}</strong>
                <span>@lang('Your current order'): {{ $curr->sign }}{{ number_format($merchantTotal, 2) }}</span>
            </div>
        </div>
    @endif

    @if(isset($deliveryCompany) && count($deliveryCompany) > 0)
        {{-- Grid Header --}}
        <div class="tryoto-grid tryoto-grid--header">
            <div class="tryoto-col tryoto-col--radio"></div>
            <div class="tryoto-col tryoto-col--logo">@lang('shipping.logo')</div>
            <div class="tryoto-col tryoto-col--service">@lang('shipping.service')</div>
            <div class="tryoto-col tryoto-col--price">@lang('shipping.price')</div>
        </div>

        {{-- Grid Body --}}
        <div class="tryoto-grid-body">
            @foreach($deliveryCompany as $index => $company)
                @php
                    $inputId = 'tryoto-shipping-' . ($merchantId ?? 0) . '-' . ($company['deliveryOptionId'] ?? $index);
                    $value = ($company['deliveryOptionId'] ?? '') . '#' . ($company['deliveryCompanyName'] ?? '') . '#' . ($company['price'] ?? 0);
                    $price = (float)($company['price'] ?? 0);
                    $convertedPrice = round($price * $curr->value, 2);
                @endphp

                <label for="{{ $inputId }}" class="tryoto-grid tryoto-grid--row">
                    {{-- Radio Button --}}
                    <div class="tryoto-col tryoto-col--radio">
                        <input type="radio"
                               class="tryoto-radio shipping-option"
                               ref="{{ $merchantId ?? 0 }}"
                               data-merchant="{{ $merchantId ?? 0 }}"
                               data-price="{{ $convertedPrice }}"
                               data-free-above="{{ $freeAboveValue }}"
                               data-view="{{ $convertedPrice }} {{ $curr->sign }}"
                               data-company="{{ $company['deliveryCompanyName'] ?? '' }}"
                               data-logo="{{ $company['logo'] ?? '' }}"
                               data-service="{{ $company['avgDeliveryTime'] ?? '' }}"
                               id="{{ $inputId }}"
                               name="shipping[{{ $merchantId ?? 0 }}]"
                               value="{{ $value }}">
                        <span class="tryoto-radio-custom"></span>
                    </div>

                    {{-- Logo --}}
                    <div class="tryoto-col tryoto-col--logo">
                        @if(!empty($company['logo']))
                            <img src="{{ $company['logo'] }}"
                                 alt="{{ $company['deliveryCompanyName'] ?? '' }}"
                                 class="tryoto-logo"
                                 loading="lazy">
                        @else
                            <div class="tryoto-logo-placeholder">
                                <i class="fas fa-truck"></i>
                            </div>
                        @endif
                    </div>

                    {{-- Service Info --}}
                    <div class="tryoto-col tryoto-col--service">
                        <span class="tryoto-company-name">{{ $company['deliveryCompanyName'] ?? '' }}</span>
                        @if(!empty($company['avgDeliveryTime']))
                            <span class="tryoto-delivery-time">
                                <i class="fas fa-clock"></i>
                                {{ $company['avgDeliveryTime'] }}
                            </span>
                        @endif
                    </div>

                    {{-- Price --}}
                    <div class="tryoto-col tryoto-col--price">
                        @if($isFreeShipping)
                            <div class="tryoto-price-wrapper tryoto-price-wrapper--free">
                                <span class="tryoto-price-original">{{ $curr->sign }}{{ number_format($convertedPrice, 2) }}</span>
                                <span class="tryoto-price-free">
                                    <i class="fas fa-gift"></i>
                                    @lang('Free!')
                                </span>
                            </div>
                        @elseif($price > 0)
                            <span class="tryoto-price">+ {{ $curr->sign }}{{ number_format($convertedPrice, 2) }}</span>
                        @else
                            <span class="tryoto-price-free">
                                <i class="fas fa-gift"></i>
                                @lang('Free')
                            </span>
                        @endif
                    </div>
                </label>
            @endforeach
        </div>

        {{-- Weight Info --}}
        @if(isset($weight) && $weight > 0)
            <div class="tryoto-weight-info">
                <i class="fas fa-weight-hanging"></i>
                @lang('shipping.chargeable_weight'): {{ $weight }} @lang('kg')
            </div>
        @endif

    @else
        <div class="tryoto-empty">
            <i class="fas fa-box-open"></i>
            <span>@lang('shipping.no_options_available')</span>
        </div>
    @endif
</div>

<style>
/* ========================================
   Tryoto Shipping Options - Grid Layout
   RTL & LTR Support
======================================== */

.tryoto-options-container {
    --tryoto-primary: var(--theme-primary, #006c35);
    --tryoto-success: var(--theme-success, #10b981);
    --tryoto-info: var(--theme-info, #2c7a7b);
    --tryoto-border: var(--theme-border, #d4c4a8);
    --tryoto-bg: var(--theme-bg-light, #faf8f5);
    --tryoto-bg-hover: var(--theme-bg-gray, #f5f2ec);
    --tryoto-bg-selected: var(--theme-primary-light, #e8f5ed);
    --tryoto-text: var(--theme-text-primary, #1a1510);
    --tryoto-text-muted: var(--theme-text-muted, #7a6f5f);
    --tryoto-radius: 8px;
    font-family: inherit;
}

/* Free Shipping Alert */
.tryoto-free-alert {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 16px;
    border-radius: var(--tryoto-radius);
    margin-bottom: 16px;
}

.tryoto-free-alert--success {
    background: linear-gradient(135deg, var(--theme-success-light, #ecfdf5) 0%, var(--theme-success-light, #d1fae5) 100%);
    border: 1px solid var(--theme-success, #10b981);
}

.tryoto-free-alert--info {
    background: linear-gradient(135deg, var(--theme-info-light, #e8f5ed) 0%, var(--theme-primary-light, #e8f5ed) 100%);
    border: 1px solid var(--theme-info, #2c7a7b);
}

.tryoto-free-alert__icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.tryoto-free-alert--success .tryoto-free-alert__icon {
    background: var(--tryoto-success);
    color: white;
}

.tryoto-free-alert--info .tryoto-free-alert__icon {
    background: var(--tryoto-info);
    color: white;
}

.tryoto-free-alert__content {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.tryoto-free-alert__content strong {
    font-size: 14px;
    color: var(--tryoto-text);
}

.tryoto-free-alert__content span {
    font-size: 13px;
    color: var(--tryoto-text-muted);
}

/* Grid Layout */
.tryoto-grid {
    display: grid;
    grid-template-columns: 40px 70px 1fr 120px;
    gap: 12px;
    align-items: center;
    padding: 12px 16px;
}

[dir="rtl"] .tryoto-grid {
    direction: rtl;
}

.tryoto-grid--header {
    background: var(--tryoto-bg);
    border-radius: var(--tryoto-radius) var(--tryoto-radius) 0 0;
    border: 1px solid var(--tryoto-border);
    border-bottom: none;
    font-size: 12px;
    font-weight: 600;
    color: var(--tryoto-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.tryoto-grid-body {
    border: 1px solid var(--tryoto-border);
    border-radius: 0 0 var(--tryoto-radius) var(--tryoto-radius);
    overflow: hidden;
}

.tryoto-grid--row {
    cursor: pointer;
    transition: all 0.2s ease;
    border-bottom: 1px solid var(--tryoto-border);
    margin: 0;
}

.tryoto-grid--row:last-child {
    border-bottom: none;
}

.tryoto-grid--row:hover {
    background: var(--tryoto-bg-hover);
}

.tryoto-grid--row:has(.tryoto-radio:checked) {
    background: var(--tryoto-bg-selected);
    box-shadow: inset 3px 0 0 var(--tryoto-primary);
}

[dir="rtl"] .tryoto-grid--row:has(.tryoto-radio:checked) {
    box-shadow: inset -3px 0 0 var(--tryoto-primary);
}

/* Columns */
.tryoto-col {
    display: flex;
    align-items: center;
}

.tryoto-col--radio {
    justify-content: center;
    position: relative;
}

.tryoto-col--logo {
    justify-content: center;
}

.tryoto-col--service {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
    min-width: 0;
}

.tryoto-col--price {
    justify-content: flex-end;
}

[dir="rtl"] .tryoto-col--price {
    justify-content: flex-start;
}

/* Custom Radio Button */
.tryoto-radio {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.tryoto-radio-custom {
    width: 20px;
    height: 20px;
    border: 2px solid var(--tryoto-border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    background: white;
}

.tryoto-radio-custom::after {
    content: '';
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--tryoto-primary);
    transform: scale(0);
    transition: transform 0.2s ease;
}

.tryoto-radio:checked + .tryoto-radio-custom {
    border-color: var(--tryoto-primary);
}

.tryoto-radio:checked + .tryoto-radio-custom::after {
    transform: scale(1);
}

.tryoto-radio:focus + .tryoto-radio-custom {
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
}

/* Logo */
.tryoto-logo {
    width: 50px;
    height: 40px;
    object-fit: contain;
    border-radius: 4px;
    background: white;
    padding: 4px;
    border: 1px solid var(--tryoto-border);
}

.tryoto-logo-placeholder {
    width: 50px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--tryoto-bg);
    border-radius: 4px;
    color: var(--tryoto-text-muted);
    font-size: 18px;
}

/* Service Info */
.tryoto-company-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--tryoto-text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.tryoto-delivery-time {
    font-size: 12px;
    color: var(--tryoto-text-muted);
    display: flex;
    align-items: center;
    gap: 4px;
}

.tryoto-delivery-time i {
    font-size: 10px;
}

/* Price */
.tryoto-price {
    font-size: 14px;
    font-weight: 700;
    color: var(--tryoto-success);
    white-space: nowrap;
}

.tryoto-price-wrapper {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 2px;
}

[dir="rtl"] .tryoto-price-wrapper {
    align-items: flex-start;
}

.tryoto-price-wrapper--free .tryoto-price-original {
    font-size: 12px;
    color: var(--tryoto-text-muted);
    text-decoration: line-through;
}

.tryoto-price-free {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--tryoto-success);
    color: white;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.tryoto-price-free i {
    font-size: 10px;
}

/* Weight Info */
.tryoto-weight-info {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 12px;
    padding: 8px 12px;
    background: var(--tryoto-bg);
    border-radius: var(--tryoto-radius);
    font-size: 12px;
    color: var(--tryoto-text-muted);
}

/* Empty State */
.tryoto-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 40px 20px;
    background: var(--tryoto-bg);
    border-radius: var(--tryoto-radius);
    border: 1px dashed var(--tryoto-border);
}

.tryoto-empty i {
    font-size: 40px;
    color: var(--tryoto-text-muted);
    opacity: 0.5;
}

.tryoto-empty span {
    font-size: 14px;
    color: var(--tryoto-text-muted);
}

/* ========================================
   Responsive - Mobile
======================================== */
@media (max-width: 576px) {
    .tryoto-grid {
        grid-template-columns: 32px 50px 1fr 80px;
        gap: 8px;
        padding: 10px 12px;
    }

    .tryoto-grid--header {
        font-size: 10px;
    }

    .tryoto-logo {
        width: 40px;
        height: 32px;
    }

    .tryoto-logo-placeholder {
        width: 40px;
        height: 32px;
        font-size: 14px;
    }

    .tryoto-company-name {
        font-size: 13px;
    }

    .tryoto-delivery-time {
        font-size: 11px;
    }

    .tryoto-price {
        font-size: 13px;
    }

    .tryoto-price-free {
        padding: 3px 8px;
        font-size: 11px;
    }

    .tryoto-free-alert {
        padding: 10px 12px;
    }

    .tryoto-free-alert__icon {
        width: 30px;
        height: 30px;
        font-size: 12px;
    }

    .tryoto-free-alert__content strong {
        font-size: 13px;
    }

    .tryoto-free-alert__content span {
        font-size: 12px;
    }
}
</style>

<script>
(function() {
    // Handle shipping option selection
    document.querySelectorAll('.tryoto-options-container .shipping-option').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var merchantId = this.dataset.merchant;
            var price = this.dataset.price;
            var company = this.dataset.company;
            var service = this.dataset.service;

            // Dispatch custom event for parent to handle
            var event = new CustomEvent('shippingSelected', {
                detail: {
                    merchantId: merchantId,
                    price: price,
                    company: company,
                    service: service,
                    value: this.value
                }
            });
            document.dispatchEvent(event);

            // Update any shipping display elements
            var displayEl = document.querySelector('[data-shipping-display="' + merchantId + '"]');
            if (displayEl) {
                displayEl.textContent = this.dataset.view;
            }
        });
    });
})();
</script>
