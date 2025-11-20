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
    $showFinalTotal = $currentStep >= 2;

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

                // DEBUG: Log in component
                \Log::info('Component Step3 - Packing Display', [
                    'step2_exists' => isset($step2),
                    'shipping_cost' => $shipping_cost,
                    'packing_cost' => $packing_cost,
                    'packing_cost_raw' => $step2->packing_cost ?? 'NOT IN STEP2',
                ]);
            @endphp

            {{-- DEBUG: Visual Debug Box --}}
            <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 10px; margin: 10px 0; border-radius: 5px;">
                <strong style="color: #856404;">🔍 DEBUG - Step2 Session Data:</strong><br>
                <small style="color: #856404;">
                    step2 exists: <strong>{{ isset($step2) ? 'YES' : 'NO' }}</strong><br>
                    shipping_cost: <strong>{{ $step2->shipping_cost ?? 'NOT SET' }}</strong><br>
                    <span style="color: #d9534f; font-weight: bold;">packing_cost: {{ $step2->packing_cost ?? 'NOT SET' }}</span><br>
                    tax_amount: <strong>{{ $step2->tax_amount ?? 'NOT SET' }}</strong><br>
                    total: <strong>{{ $step2->total ?? 'NOT SET' }}</strong>
                </small>
            </div>

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

    {{-- Final Price (Step 2 & 3 Only) --}}
    @if($showFinalTotal)
        <hr>
        <div class="final-price">
            <span>@lang('Final Price')</span>
            @if($currentStep == 3 && isset($step2))
                {{-- Step 3: Use step2->total --}}
                @if (Session::has('coupon_total'))
                    @if ($gs->currency_format == 0)
                        <span class="total-amount">{{ $curr->sign }}{{ $step2->total }}</span>
                    @else
                        <span class="total-amount">{{ $step2->total }}{{ $curr->sign }}</span>
                    @endif
                @elseif(Session::has('coupon_total1'))
                    <span class="total-amount">{{ Session::get('coupon_total1') }}</span>
                @else
                    <span class="total-amount">{{ App\Models\Product::convertPrice($step2->total) }}</span>
                @endif
            @else
                {{-- Step 2: Dynamic total calculation (updated via JavaScript) --}}
                {{-- Uses productsTotal as base, shipping/packing/tax added dynamically --}}
                @if (Session::has('coupon_total'))
                    @if ($gs->currency_format == 0)
                        <span class="total-amount" id="final-cost">{{ $curr->sign }}{{ $productsPriceDisplay }}</span>
                    @else
                        <span class="total-amount" id="final-cost">{{ $productsPriceDisplay }}{{ $curr->sign }}</span>
                    @endif
                @elseif(Session::has('coupon_total1'))
                    <span class="total-amount" id="final-cost">{{ Session::get('coupon_total1') }}</span>
                @else
                    <span class="total-amount" id="final-cost">{{ App\Models\Product::convertPrice($productsPriceDisplay) }}</span>
                @endif
            @endif
        </div>
    @endif
</div>
