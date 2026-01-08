<div class="modal fade gs-modal" id="{{ $modalId }}" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog xsend-message-modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content send-message-modal-content form-group">
            <div class="modal-header w-100">
                <h4 class="title" id="exampleModalLongTitle">{{ $providerLabel }}</h4>
                <button type="button" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-regular fa-circle-xmark gs-modal-close-btn"></i>
                </button>
            </div>
            <div class="packeging-area">
                <div class="summary-inner-box">
                    <div class="inputs-wrapper">
                        @if($methods->count() > 0)
                            @foreach($methods as $index => $data)
                                <div class="gs-radio-wrapper">
                                    <input type="radio" class="shipping" ref="{{ $merchant_id }}"
                                           data-price="{{ round($data->price * $curr->value, 2) }}"
                                           data-free-above="{{ round(($data->free_above ?? 0) * $curr->value, 2) }}"
                                           view="{{ $curr->sign }}{{ round($data->price * $curr->value, 2) }}"
                                           data-form="{{ $data->title }}"
                                           id="{{ $provider }}-shipping-{{ $merchant_id }}-{{ $data->id }}"
                                           name="shipping[{{ $merchant_id }}]" value="{{ $data->id }}">

                                    <label class="icon-label" for="{{ $provider }}-shipping-{{ $merchant_id }}-{{ $data->id }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                            <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" fill="#FDFDFD" />
                                            <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" stroke="#EE1243" />
                                            <circle cx="10" cy="10" r="4" fill="#EE1243" />
                                        </svg>
                                    </label>

                                    <label for="{{ $provider }}-shipping-{{ $merchant_id }}-{{ $data->id }}">
                                        <span class="shipping-title">{{ $data->title }}</span>
                                        <span class="shipping-price-display">
                                            @if($data->price != 0)
                                                + {{ $curr->sign }}{{ round($data->price * $curr->value, 2) }}
                                            @endif
                                        </span>
                                        <small class="d-block">{{ $data->subtitle }}</small>
                                        @if(($data->free_above ?? 0) > 0)
                                            <small class="text-success d-block free-shipping-hint">
                                                @lang('Free shipping if purchase above') {{ $curr->sign }}{{ round($data->free_above * $curr->value, 2) }}
                                            </small>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        @else
                            <p>@lang('No Shipping Method Available')</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const modalId = '{{ $modalId }}';
    const merchantId = {{ $merchant_id }};
    const currSign = '{{ $curr->sign }}';

    @php
        $merchantCatalogitemsTotal = 0;
        if (isset($array_product)) {
            foreach ($array_product as $catalogItem) {
                $merchantCatalogitemsTotal += $catalogItem['price'] ?? 0;
            }
        }
    @endphp
    const merchantCartTotal = {{ round($merchantCatalogitemsTotal * $curr->value, 2) }};

    function initShippingModal() {
        const modal = document.getElementById(modalId);
        if (!modal) {
            setTimeout(initShippingModal, 100);
            return;
        }

        const shippingRadios = modal.querySelectorAll('input.shipping[ref="' + merchantId + '"]');

        shippingRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (!this.checked) return;

                const originalPrice = parseFloat(this.getAttribute('data-price')) || 0;
                const freeAbove = parseFloat(this.getAttribute('data-free-above')) || 0;
                const title = this.getAttribute('data-form');

                // Check if free shipping applies
                let finalPrice = originalPrice;
                let isFreeShipping = (freeAbove > 0 && merchantCartTotal >= freeAbove);
                if (isFreeShipping) finalPrice = 0;

                // Update shipping text display
                const shippingText = document.getElementById('shipping_text' + merchantId);
                if (shippingText) {
                    if (isFreeShipping) {
                        shippingText.innerHTML = '<span class="text-success"><i class="fas fa-gift"></i> ' + title + ' (@lang("Free!"))</span>';
                    } else {
                        shippingText.textContent = title + ': ' + currSign + originalPrice.toFixed(2);
                    }
                }

                // ✅ Update PriceSummary directly
                if (typeof window.PriceSummary !== 'undefined') {
                    window.PriceSummary.updateShipping(finalPrice, originalPrice, isFreeShipping);
                }

                // Also call global functions for backward compatibility
                if (typeof window.getShipping === 'function') {
                    window.getShipping();
                }
            });
        });

        // Trigger update when modal opens if already selected
        modal.addEventListener('shown.bs.modal', function() {
            const checkedRadio = modal.querySelector('input.shipping[ref="' + merchantId + '"]:checked');
            if (checkedRadio) {
                checkedRadio.dispatchEvent(new Event('change'));
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initShippingModal);
    } else {
        initShippingModal();
    }
})();
</script>
