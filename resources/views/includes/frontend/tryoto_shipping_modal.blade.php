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
                        <div class="shipping-provider-section tryoto-shipping-section">
                            <div class="provider-methods-wrapper">
                                <livewire:tryoto-componet :products="$array_product" :vendor-id="$vendor_id" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Handle Tryoto Livewire component events
    document.addEventListener('DOMContentLoaded', function() {
        const modalId = '{{ $modalId }}';
        const vendorId = {{ $vendor_id }};

        // Listen for Livewire events from TryotoComponet
        @if(class_exists('Livewire\Livewire'))
        Livewire.on('shipping-updated', function(data) {
            if (data.vendorId === vendorId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    const selectedRadio = modal.querySelector('input[type="radio"][name="shipping[' + vendorId + ']"]:checked');

                    if (selectedRadio) {
                        const shippingText = document.getElementById('shipping_text' + vendorId);
                        const value = selectedRadio.value;

                        // Tryoto format: deliveryOptionId#CompanyName#price
                        if (value.includes('#')) {
                            const parts = value.split('#');
                            const company = parts[1] || '';
                            const price = parts[2] || '0';
                            const currSign = '{{ $curr->sign }}';

                            if (shippingText) {
                                shippingText.textContent = company + ': ' + currSign + price;
                            }
                        }

                        // Trigger recalculation of totals
                        if (typeof calculateTotals === 'function') {
                            calculateTotals();
                        }
                    }
                }
            }
        });
        @endif

        // Handle manual radio changes in Tryoto modal
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.addEventListener('change', function(e) {
                if (e.target.matches('input[type="radio"][name="shipping[' + vendorId + ']"]')) {
                    const shippingText = document.getElementById('shipping_text' + vendorId);
                    const value = e.target.value;

                    // Tryoto format: deliveryOptionId#CompanyName#price
                    if (value.includes('#')) {
                        const parts = value.split('#');
                        const company = parts[1] || '';
                        const price = parts[2] || '0';
                        const currSign = '{{ $curr->sign }}';

                        if (shippingText) {
                            shippingText.textContent = company + ': ' + currSign + price;
                        }
                    }

                    // Trigger recalculation of totals
                    if (typeof calculateTotals === 'function') {
                        calculateTotals();
                    }
                }
            });
        }
    });
</script>
