{{--
    Unified Price Summary Component for Checkout
    Used in Step 1, Step 2, and Step 3

    Required Variables:
    - $productsTotal: Products total (Total MRP) - ALWAYS products only, never includes shipping/tax
    - $step (optional): Current step number (1, 2, or 3)
    - $step1 (optional): Step 1 session data (for tax in step 2/3)
    - $step2 (optional): Step 2 session data (for shipping/packing/total in step 3)
    - $digital: Whether cart contains only digital products
    - $curr: Current currency object
    - $gs: General settings object

    IMPORTANT - Single Source of Truth:
    - Total MRP: Always uses $productsTotal (products only)
    - Final Price Step 2: Dynamic calculation
    - Final Price Step 3: ALWAYS uses $step2->total (pre-calculated in step2 submit)
--}}

@php
    $currentStep = $step ?? 1;
    $showShipping = !$digital && $currentStep >= 2;
    $showTax = isset($step1) && isset($step1->tax_rate) && $step1->tax_rate > 0;

    // ✅ FIXED: Show final total in ALL steps (not just step 2 & 3)
    // Step 1 shows products total (tax will be added dynamically via JS)
    // Step 2/3 show complete totals
    $showFinalTotal = true;

    // Use productsTotal if provided, otherwise fallback to totalPrice for backward compatibility
    $productsPriceDisplay = $productsTotal ?? $totalPrice;
@endphp

<!-- Price Details -->
<div class="summary-inner-box">
    <h6 class="summary-title">@lang('Price Details')</h6>
    <div class="details-wrapper">
        <!-- Total MRP - ALWAYS shows products total only (no shipping, no tax) -->
        <div class="price-details">
            <span>@lang('Total MRP')</span>
            <span class="right-side cart-total">{{ App\Models\Product::convertPrice($productsPriceDisplay) }}</span>
        </div>

        {{-- Coupon/Discount --}}
        @if (Session::has('coupon'))
            <div class="price-details">
                <span>
                    @lang('Discount')
                    <span class="dpercent">
                        {{ Session::get('coupon_percentage') == 0 ? '' : '(' . Session::get('coupon_percentage') . '%)' }}
                    </span>
                </span>
                @if ($gs->currency_format == 0)
                    <span id="discount" class="right-side">{{ $curr->sign }}{{ Session::get('coupon') }}</span>
                @else
                    <span id="discount" class="right-side">{{ Session::get('coupon') }}{{ $curr->sign }}</span>
                @endif
            </div>
        @else
            <div class="price-details d-none">
                <span>@lang('Discount') <span class="dpercent"></span></span>
                <span id="discount" class="right-side">{{ $curr->sign }}{{ Session::get('coupon') }}</span>
            </div>
        @endif

        {{-- Tax Display - Step 1 (Dynamic) --}}
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

        {{-- Tax Display - Step 2 & 3 (From Session) --}}
        @if($currentStep >= 2 && $showTax)
            @php
                $taxRate = $step1->tax_rate ?? 0;
                $taxAmount = $step1->tax_amount ?? 0;
                $taxLocation = $step1->tax_location ?? '';
            @endphp
            <div class="price-details">
                <span>@lang('Tax') ({{ $taxRate }}%)</span>
                <span class="right-side">{{ App\Models\Product::convertPrice($taxAmount) }}</span>
            </div>
            @if($taxLocation)
                <div class="price-details">
                    <small class="text-muted">{{ $taxLocation }}</small>
                </div>
            @endif
        @endif

        {{-- Step 2: Dynamic Shipping & Packing (Before Selection) --}}
        @if($currentStep == 2 && !$digital)
            <div class="price-details">
                <span>@lang('Shipping Cost')</span>
                <span class="right-side shipping_cost_view">{{ App\Models\Product::convertPrice(0) }}</span>
            </div>

            <div class="price-details">
                <span>@lang('Packaging Cost')</span>
                <span class="right-side packing_cost_view">{{ App\Models\Product::convertPrice(0) }}</span>
            </div>

            {{-- Placeholder for tax (dynamic update via JS) --}}
            <div class="price-details tax_show d-none">
                <span>@lang('Tax')</span>
                <span class="right-side original_tax">0</span>
            </div>
        @endif

        {{-- Step 3: Fixed Shipping & Packing (From Step2 Session) --}}
        @if($currentStep == 3 && !$digital && isset($step2))
            @php
                $shipping_cost = $step2->shipping_cost ?? 0;
                $packing_cost = $step2->packing_cost ?? 0;
                $shipping_company = $step2->shipping_company ?? null;
            @endphp

            <div class="price-details">
                <span>@lang('Shipping Cost')</span>
                <span class="right-side">{{ App\Models\Product::convertPrice($shipping_cost) }}</span>
            </div>

            @if($shipping_company)
                <div class="price-details">
                    <small class="text-muted">{{ $shipping_company }}</small>
                </div>
            @endif

            <div class="price-details">
                <span>@lang('Packaging Cost')</span>
                <span class="right-side">{{ App\Models\Product::convertPrice($packing_cost) }}</span>
            </div>
        @endif
    </div>

    {{-- Final Price Display - ALL STEPS --}}
    @if($showFinalTotal)
        <hr>
        <div class="final-price">
            <span style="font-size: 16px; font-weight: 700;">
                @if($currentStep == 1)
                    @lang('Total Amount')
                @else
                    @lang('Final Price')
                @endif
            </span>

            @if($currentStep == 3 && isset($step2))
                {{-- Step 3: Use step2->total (pre-calculated in step2 submit) --}}
                @if (Session::has('coupon_total'))
                    @if ($gs->currency_format == 0)
                        <span class="total-amount" style="font-size: 18px; font-weight: 700; color: #EE1243;">{{ $curr->sign }}{{ $step2->total }}</span>
                    @else
                        <span class="total-amount" style="font-size: 18px; font-weight: 700; color: #EE1243;">{{ $step2->total }}{{ $curr->sign }}</span>
                    @endif
                @elseif(Session::has('coupon_total1'))
                    <span class="total-amount" style="font-size: 18px; font-weight: 700; color: #EE1243;">{{ Session::get('coupon_total1') }}</span>
                @else
                    <span class="total-amount" style="font-size: 18px; font-weight: 700; color: #EE1243;">{{ App\Models\Product::convertPrice($step2->total) }}</span>
                @endif
            @else
                {{-- Step 1 & 2: Dynamic total (updated via JavaScript) --}}
                {{-- Step 1: Shows products total initially, then updates with tax --}}
                {{-- Step 2: Shows products + tax, then updates with shipping/packing --}}
                @if (Session::has('coupon_total'))
                    @if ($gs->currency_format == 0)
                        <span class="total-amount" id="final-cost" style="font-size: 18px; font-weight: 700; color: #EE1243;">{{ $curr->sign }}{{ $productsPriceDisplay }}</span>
                    @else
                        <span class="total-amount" id="final-cost" style="font-size: 18px; font-weight: 700; color: #EE1243;">{{ $productsPriceDisplay }}{{ $curr->sign }}</span>
                    @endif
                @elseif(Session::has('coupon_total1'))
                    <span class="total-amount" id="final-cost" style="font-size: 18px; font-weight: 700; color: #EE1243;">{{ Session::get('coupon_total1') }}</span>
                @else
                    <span class="total-amount" id="final-cost" style="font-size: 18px; font-weight: 700; color: #EE1243;">{{ App\Models\Product::convertPrice($productsPriceDisplay) }}</span>
                @endif
            @endif
        </div>

        @if($currentStep == 1)
            <div class="text-muted" style="margin-top: 5px; font-size: 12px; text-align: center;">
                <small>* @lang('Tax and shipping costs will be calculated after selecting your location')</small>
            </div>
        @endif
    @endif
</div>
