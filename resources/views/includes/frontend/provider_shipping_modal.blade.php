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
                                    <input type="radio" class="shipping" ref="{{ $vendor_id }}"
                                           data-price="{{ round($data->price * $curr->value, 2) }}"
                                           data-free-above="{{ round(($data->free_above ?? 0) * $curr->value, 2) }}"
                                           view="{{ $curr->sign }}{{ round($data->price * $curr->value, 2) }}"
                                           data-form="{{ $data->title }}"
                                           id="{{ $provider }}-shipping-{{ $vendor_id }}-{{ $data->id }}"
                                           name="shipping[{{ $vendor_id }}]" value="{{ $data->id }}">

                                    <label class="icon-label" for="{{ $provider }}-shipping-{{ $vendor_id }}-{{ $data->id }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                            <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" fill="#FDFDFD" />
                                            <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" stroke="#EE1243" />
                                            <circle cx="10" cy="10" r="4" fill="#EE1243" />
                                        </svg>
                                    </label>

                                    <label for="{{ $provider }}-shipping-{{ $vendor_id }}-{{ $data->id }}">
                                        <span class="shipping-title">{{ $data->title }}</span>
                                        <span class="shipping-price-display">
                                            @if($data->price != 0)
                                                + {{ $curr->sign }}{{ round($data->price * $curr->value, 2) }}
                                            @endif
                                        </span>
                                        <small class="d-block">{{ $data->subtitle }}</small>
                                        @if(($data->free_above ?? 0) > 0)
                                            <small class="text-success d-block free-shipping-hint">
                                                @lang('Free shipping if order above') {{ $curr->sign }}{{ round($data->free_above * $curr->value, 2) }}
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
    // Handle shipping selection for {{ $provider }} provider
    document.addEventListener('DOMContentLoaded', function() {
        const modalId = '{{ $modalId }}';
        const vendorId = {{ $vendor_id }};
        const modal = document.getElementById(modalId);

        // ✅ Get vendor's products total from page
        @php
            $vendorProductsTotal = 0;
            if (isset($array_product)) {
                foreach ($array_product as $product) {
                    $vendorProductsTotal += $product['price'] ?? 0;
                }
            }
        @endphp
        const vendorCartTotal = {{ round($vendorProductsTotal * $curr->value, 2) }};

        if (modal) {
            const shippingRadios = modal.querySelectorAll('input.shipping[ref="{{ $vendor_id }}"]');

            shippingRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        const shippingText = document.getElementById('shipping_text' + vendorId);
                        const originalPrice = parseFloat(this.getAttribute('data-price')) || 0;
                        const freeAbove = parseFloat(this.getAttribute('data-free-above')) || 0;
                        const title = this.getAttribute('data-form');
                        const currSign = '{{ $curr->sign }}';

                        // ✅ Check if free shipping applies
                        let finalPrice = originalPrice;
                        let isFreeShipping = false;

                        if (freeAbove > 0 && vendorCartTotal >= freeAbove) {
                            finalPrice = 0;
                            isFreeShipping = true;
                        }

                        // ✅ Update the data-price attribute dynamically for total calculation
                        this.setAttribute('data-effective-price', finalPrice);

                        // ✅ Update display text
                        if (isFreeShipping) {
                            shippingText.innerHTML = '<span class="text-success"><i class="fas fa-gift"></i> ' + title + ' (@lang("Free!"))</span>';
                        } else {
                            shippingText.textContent = title + ': ' + currSign + originalPrice.toFixed(2);
                        }

                        // ✅ Trigger global recalculation
                        if (typeof getShipping === 'function') {
                            getShipping();
                        }
                        if (typeof updateFinalTotal === 'function') {
                            updateFinalTotal();
                        }
                    }
                });
            });

            // Update on modal open if already selected
            modal.addEventListener('shown.bs.modal', function() {
                const checkedRadio = modal.querySelector('input.shipping[ref="{{ $vendor_id }}"]:checked');
                if (checkedRadio) {
                    checkedRadio.dispatchEvent(new Event('change'));
                }
            });
        }
    });
</script>
