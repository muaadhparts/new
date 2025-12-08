{{--
    ✅ UNIFIED & SIMPLIFIED Price Summary Component

    Usage:
    - Step 1: Shows Products + Tax (dynamic)
    - Step 2: Shows Products + Tax + Shipping + Packing (dynamic)
    - Step 3: Shows Final Total (read-only from session)

    Required Variables:
    - $step: Current step (1, 2, or 3)
    - $productsTotal: Products total (Step 1 only)
    - $step1: Step 1 session data (Step 2 & 3)
    - $step2: Step 2 session data (Step 3 only)
    - $digital: Whether cart is digital only
    - $curr: Currency object
    - $gs: General settings
--}}

@php
    $currentStep = $step ?? 1;
    $isDigital = $digital ?? false;
@endphp

<!-- Price Details -->
<div class="summary-inner-box">
    <h6 class="summary-title">@lang('Price Details')</h6>
    <div class="details-wrapper">

        {{-- ========================================
            PRODUCTS TOTAL (All Steps)
        ======================================== --}}
        <div class="price-details">
            <span>@lang('Total MRP')</span>
            @if($currentStep == 1)
                <span class="right-side">{{ App\Models\Product::convertPrice($productsTotal) }}</span>
            @else
                <span class="right-side">{{ App\Models\Product::convertPrice($step1->products_total ?? 0) }}</span>
            @endif
        </div>

        {{-- ========================================
            COUPON/DISCOUNT (All Steps)
        ======================================== --}}
        @if (Session::has('coupon'))
            <div class="price-details">
                <span>
                    @lang('Discount')
                    @if(Session::get('coupon_percentage') > 0)
                        <span class="dpercent">({{ Session::get('coupon_percentage') }}%)</span>
                    @endif
                </span>
                @if ($gs->currency_format == 0)
                    <span class="right-side">{{ $curr->sign }}{{ Session::get('coupon') }}</span>
                @else
                    <span class="right-side">{{ Session::get('coupon') }}{{ $curr->sign }}</span>
                @endif
            </div>
        @endif

        {{-- ========================================
            TAX
        ======================================== --}}

        {{-- Step 1: Tax (Dynamic - Hidden initially, shown after location selection) --}}
        @if($currentStep == 1)
            <div class="price-details tax-display-wrapper d-none" id="tax-display">
                <span>
                    @lang('Tax')
                    <span class="tax-rate-text"></span>
                </span>
                <span class="right-side tax-amount-value">{{ App\Models\Product::convertPrice(0) }}</span>
            </div>

            <div class="price-details tax-location-wrapper d-none" id="tax-location-display">
                <small class="text-muted tax-location-text"></small>
            </div>
        @endif

        {{-- Step 2 & 3: Tax (From Session) --}}
        @if($currentStep >= 2 && isset($step1) && isset($step1->tax_rate) && $step1->tax_rate > 0)
            <div class="price-details">
                <span>@lang('Tax') ({{ $step1->tax_rate }}%)</span>
                <span class="right-side">{{ App\Models\Product::convertPrice($step1->tax_amount) }}</span>
            </div>

            @if(isset($step1->tax_location) && $step1->tax_location)
                <div class="price-details">
                    <small class="text-muted">{{ $step1->tax_location }}</small>
                </div>
            @endif
        @endif

        {{-- ========================================
            SHIPPING & PACKING
        ======================================== --}}

        {{-- Step 2: Shipping & Packing (Dynamic) --}}
        @if($currentStep == 2 && !$isDigital)
            <div class="price-details">
                <span>@lang('Shipping Cost')</span>
                <span class="right-side shipping_cost_view">{{ App\Models\Product::convertPrice(0) }}</span>
            </div>

            {{-- Free Shipping Discount (will be shown by JavaScript if applicable) --}}
            <div class="price-details free-shipping-discount-row d-none">
                <span class="text-success">
                    <i class="fas fa-gift"></i> @lang('Free Shipping Discount')
                </span>
                <span class="right-side text-success free_shipping_discount_view">-{{ $curr->sign }}0.00</span>
            </div>

            <div class="price-details">
                <span>@lang('Packaging Cost')</span>
                <span class="right-side packing_cost_view">{{ App\Models\Product::convertPrice(0) }}</span>
            </div>
        @endif

        {{-- Step 3: Shipping & Packing (From Session) --}}
        @if($currentStep == 3 && !$isDigital && isset($step2))
            @php
                $isFreeShipping = $step2->is_free_shipping ?? false;
                $originalShippingCost = $step2->original_shipping_cost ?? 0;
                $freeShippingDiscount = $step2->free_shipping_discount ?? 0;
                $actualShippingCost = $step2->shipping_cost ?? 0;
            @endphp

            <div class="price-details">
                <span>@lang('Shipping Cost')</span>
                <span class="right-side">
                    @if($isFreeShipping && $originalShippingCost > 0)
                        {{-- Show original price with strikethrough --}}
                        <span class="text-decoration-line-through text-muted me-2">
                            {{ App\Models\Product::convertPrice($originalShippingCost) }}
                        </span>
                        <span class="badge bg-success">
                            <i class="fas fa-gift"></i> @lang('Free!')
                        </span>
                    @else
                        {{ App\Models\Product::convertPrice($actualShippingCost) }}
                    @endif
                </span>
            </div>

            {{-- ✅ Free Shipping Discount Row --}}
            @if($isFreeShipping && $freeShippingDiscount > 0)
                <div class="price-details">
                    <span class="text-success">
                        <i class="fas fa-gift"></i> @lang('Free Shipping Discount')
                    </span>
                    <span class="right-side text-success">
                        -{{ App\Models\Product::convertPrice($freeShippingDiscount) }}
                    </span>
                </div>
            @endif

            @if(isset($step2->shipping_company) && $step2->shipping_company)
                <div class="price-details">
                    <small class="text-muted">{{ $step2->shipping_company }}</small>
                </div>
            @endif

            <div class="price-details">
                <span>@lang('Packaging Cost')</span>
                <span class="right-side">{{ App\Models\Product::convertPrice($step2->packing_cost ?? 0) }}</span>
            </div>

            @if(isset($step2->packing_company) && $step2->packing_company)
                <div class="price-details">
                    <small class="text-muted">{{ $step2->packing_company }}</small>
                </div>
            @endif
        @endif

    </div>

    {{-- ========================================
        FINAL PRICE
    ======================================== --}}
    <hr>
    <div class="final-price">
        <span style="font-size: 16px; font-weight: 700;">
            @if($currentStep == 1)
                @lang('Total Amount')
            @else
                @lang('Final Price')
            @endif
        </span>

        {{-- Step 1 & 2: Dynamic Total (Updated by JavaScript) --}}
        @if($currentStep == 1 || $currentStep == 2)
            @php
                // Initial display value
                $initialTotal = $productsTotal ?? ($step1->total_with_tax ?? 0);
            @endphp

            @if ($gs->currency_format == 0)
                <span class="total-amount" id="final-cost" style="font-size: 18px; font-weight: 700; color: var(--theme-primary);">
                    {{ $curr->sign }}{{ number_format($initialTotal, 2) }}
                </span>
            @else
                <span class="total-amount" id="final-cost" style="font-size: 18px; font-weight: 700; color: var(--theme-primary);">
                    {{ number_format($initialTotal, 2) }}{{ $curr->sign }}
                </span>
            @endif
        @endif

        {{-- Step 3: Final Total (From Session - Read Only) --}}
        @if($currentStep == 3 && isset($step2))
            @php
                // Use final_total if available, fallback to total for backward compatibility
                $finalTotal = $step2->final_total ?? $step2->total ?? 0;
            @endphp

            @if ($gs->currency_format == 0)
                <span class="total-amount" style="font-size: 18px; font-weight: 700; color: var(--theme-primary);">
                    {{ $curr->sign }}{{ number_format($finalTotal, 2) }}
                </span>
            @else
                <span class="total-amount" style="font-size: 18px; font-weight: 700; color: var(--theme-primary);">
                    {{ number_format($finalTotal, 2) }}{{ $curr->sign }}
                </span>
            @endif
        @endif
    </div>

    {{-- Helper Text for Step 1 --}}
    @if($currentStep == 1)
        <div class="text-muted" style="margin-top: 5px; font-size: 12px; text-align: center;">
            <small>* @lang('Tax and shipping costs will be calculated in next steps')</small>
        </div>
    @endif
</div>
