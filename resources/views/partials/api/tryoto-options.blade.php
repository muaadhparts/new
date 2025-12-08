{{-- resources/views/partials/api/tryoto-options.blade.php --}}
{{-- API-based Tryoto shipping options partial (No Livewire) --}}

{{-- Variables from ShippingApiController:
     $curr, $vendorId, $deliveryCompany, $weight, $freeAbove, $vendorProductsTotal
--}}

@php
    $freeAboveValue = $freeAbove ?? 0;
    $vendorTotal = $vendorProductsTotal ?? 0;
    $isFreeShipping = ($freeAboveValue > 0 && $vendorTotal >= $freeAboveValue);
@endphp

<div class="tryoto-options" data-vendor-id="{{ $vendorId ?? 0 }}">
    {{-- ✅ Show Free Shipping Alert if applicable --}}
    @if($isFreeShipping)
        <div class="alert alert-success mb-3">
            <i class="fas fa-gift me-2"></i>
            <strong>@lang('Free Shipping!')</strong>
            @lang('Your order qualifies for free shipping (above') {{ $curr->sign }}{{ $freeAboveValue }})
        </div>
    @elseif($freeAboveValue > 0)
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle me-2"></i>
            @lang('Free shipping on orders above') {{ $curr->sign }}{{ $freeAboveValue }}
            <br>
            <small>@lang('Your current order'): {{ $curr->sign }}{{ $vendorTotal }}</small>
        </div>
    @endif

    @if(isset($deliveryCompany) && count($deliveryCompany) > 0)
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="width: 60px;">@lang('shipping.select')</th>
                    <th>@lang('shipping.service')</th>
                    <th>@lang('shipping.price')</th>
                    <th class="text-center" style="width: 100px;">@lang('shipping.logo')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deliveryCompany as $index => $company)
                    @php
                        $inputId = 'shipping-' . ($company['deliveryOptionId'] ?? $index) . '-' . $index;
                        $value = ($company['deliveryOptionId'] ?? '') . '#' . ($company['deliveryCompanyName'] ?? '') . '#' . ($company['price'] ?? 0);
                        $price = (float)($company['price'] ?? 0);
                        $convertedPrice = round($price * $curr->value, 2);
                        // Display price (0 if free shipping applies)
                        $displayPrice = $isFreeShipping ? 0 : $convertedPrice;
                    @endphp

                    <tr>
                        <!-- Radio Input -->
                        <td class="text-center">
                            <input type="radio"
                                   class="form-check-input shipping-option"
                                   ref="{{ $vendorId ?? 0 }}"
                                   data-vendor="{{ $vendorId ?? 0 }}"
                                   data-price="{{ $convertedPrice }}"
                                   data-free-above="{{ $freeAboveValue }}"
                                   data-view="{{ $convertedPrice }} {{ $curr->sign }}"
                                   data-company="{{ $company['deliveryCompanyName'] ?? '' }}"
                                   data-logo="{{ $company['logo'] ?? '' }}"
                                   data-service="{{ $company['avgDeliveryTime'] ?? '' }}"
                                   id="{{ $inputId }}"
                                   name="shipping[{{ $vendorId ?? 0 }}]"
                                   value="{{ $value }}">
                        </td>

                        <!-- Company Name -->
                        <td>
                            <label for="{{ $inputId }}" class="d-block cursor-pointer">
                                <p class="mb-1 fw-semibold">{{ $company['deliveryCompanyName'] ?? '' }}</p>
                                @if(!empty($company['avgDeliveryTime']))
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ $company['avgDeliveryTime'] }}
                                    </small>
                                @endif
                            </label>
                        </td>

                        <!-- Price -->
                        <td>
                            @if($isFreeShipping)
                                <span class="text-decoration-line-through text-muted me-1">
                                    {{ $curr->sign }}{{ $convertedPrice }}
                                </span>
                                <span class="badge bg-success">
                                    <i class="fas fa-gift"></i> @lang('Free!')
                                </span>
                            @elseif($price > 0)
                                <span class="fw-bold text-success">
                                    + {{ $curr->sign }}{{ $convertedPrice }}
                                </span>
                            @else
                                <span class="badge bg-success">@lang('Free')</span>
                            @endif
                        </td>

                        <!-- Company Logo -->
                        <td class="text-center">
                            @if(!empty($company['logo']))
                                <img src="{{ $company['logo'] }}"
                                     alt="{{ $company['deliveryCompanyName'] ?? '' }}"
                                     class="img-fluid rounded border"
                                     style="max-width: 80px; max-height: 50px; object-fit: contain;">
                            @else
                                <i class="fas fa-truck fa-2x text-muted"></i>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if(isset($weight) && $weight > 0)
            <div class="text-muted small mt-2">
                <i class="fas fa-weight-hanging me-1"></i>
                @lang('shipping.chargeable_weight'): {{ $weight }} @lang('kg')
            </div>
        @endif
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            @lang('shipping.no_options_available')
        </div>
    @endif
</div>

<style>
.tryoto-options .cursor-pointer {
    cursor: pointer;
}

.tryoto-options .form-check-input {
    cursor: pointer;
    width: 1.2em;
    height: 1.2em;
}

.tryoto-options .form-check-input:checked {
    background-color: var(--theme-primary);
    border-color: var(--theme-primary);
}

.tryoto-options tr:hover {
    background-color: var(--theme-bg-light);
}
</style>

<script>
(function() {
    // Handle shipping option selection
    document.querySelectorAll('.shipping-option').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var vendorId = this.dataset.vendor;
            var price = this.dataset.price;
            var company = this.dataset.company;
            var service = this.dataset.service;

            // Dispatch custom event for parent to handle
            var event = new CustomEvent('shippingSelected', {
                detail: {
                    vendorId: vendorId,
                    price: price,
                    company: company,
                    service: service,
                    value: this.value
                }
            });
            document.dispatchEvent(event);

            // Update any shipping display elements
            var displayEl = document.querySelector('[data-shipping-display="' + vendorId + '"]');
            if (displayEl) {
                displayEl.textContent = this.dataset.view;
            }
        });
    });
})();
</script>
