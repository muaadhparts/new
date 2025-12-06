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
(function() {
    const modalId = '{{ $modalId }}';
    const vendorId = {{ $vendor_id }};

    // دالة لتحديث نص الشحن - استخدام updateVendorShippingText المركزية
    function updateTryotoShippingDisplay_{{ $vendor_id }}(selectedRadio) {
        // استخدام الدالة المركزية لضمان العرض الموحد
        if (typeof updateVendorShippingText === 'function') {
            updateVendorShippingText(vendorId);
        } else {
            // Fallback إذا لم تكن الدالة موجودة
            if (!selectedRadio) return;

            const shippingText = document.getElementById('shipping_text' + vendorId);
            const logo = selectedRadio.getAttribute('data-logo') || '';
            const service = selectedRadio.getAttribute('data-service') || '';
            const companyName = selectedRadio.getAttribute('data-form') || '';
            const viewPrice = selectedRadio.getAttribute('view') || '';

            if (shippingText) {
                let html = '<div class="muaadh-shipping-option">';
                if (logo) {
                    html += '<img src="' + logo + '" alt="' + companyName + '" class="muaadh-shipping-logo">';
                }
                html += '<div class="muaadh-shipping-info">';
                html += '<span class="muaadh-shipping-company">' + companyName + '</span>';
                if (service) {
                    html += '<small class="muaadh-shipping-service">' + service + '</small>';
                }
                html += '</div>';
                if (viewPrice) {
                    html += '<span class="muaadh-shipping-price">+ ' + viewPrice + '</span>';
                }
                html += '</div>';
                shippingText.innerHTML = html;
            }

            if (typeof recalcTotals === 'function') {
                recalcTotals();
            }
        }
    }

    // Initialize when DOM is ready
    function initTryotoModal_{{ $vendor_id }}() {
        const modal = document.getElementById(modalId);
        if (!modal) {
            setTimeout(initTryotoModal_{{ $vendor_id }}, 500);
            return;
        }

        // Handle manual radio changes in Tryoto modal
        modal.addEventListener('change', function(e) {
            if (e.target.matches('input[type="radio"][name="shipping[' + vendorId + ']"]')) {
                updateTryotoShippingDisplay_{{ $vendor_id }}(e.target);
            }
        });

        // تحديث العرض عند فتح Modal
        modal.addEventListener('shown.bs.modal', function() {
            const selectedRadio = modal.querySelector('input[type="radio"][name="shipping[' + vendorId + ']"]:checked');
            if (selectedRadio) {
                updateTryotoShippingDisplay_{{ $vendor_id }}(selectedRadio);
            }
        });

        // تحديث العرض للعنصر المختار فقط (بدون اختيار تلقائي)
        setTimeout(function() {
            const checkedRadio = modal.querySelector('input[type="radio"][name="shipping[' + vendorId + ']"]:checked');
            if (checkedRadio) {
                updateTryotoShippingDisplay_{{ $vendor_id }}(checkedRadio);
            }
        }, 500);
    }

    // Listen for Livewire events
    document.addEventListener('livewire:load', function() {
        if (window.Livewire) {
            Livewire.on('shipping-updated', function(data) {
                if (data.vendorId === vendorId || data['vendorId'] === vendorId || data['vendor_id'] === vendorId) {
                    const modal = document.getElementById(modalId);
                    if (modal) {
                        const selectedRadio = modal.querySelector('input[type="radio"][name="shipping[' + vendorId + ']"]:checked');
                        updateTryotoShippingDisplay_{{ $vendor_id }}(selectedRadio);
                    }
                }
            });
        }

        // Initialize modal after Livewire loads
        setTimeout(initTryotoModal_{{ $vendor_id }}, 500);
    });

    // Fallback if Livewire doesn't exist
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initTryotoModal_{{ $vendor_id }}, 1000);
        });
    } else {
        setTimeout(initTryotoModal_{{ $vendor_id }}, 1000);
    }

    // Make function globally accessible for restoration
    window['updateTryotoShippingDisplay_' + vendorId] = updateTryotoShippingDisplay_{{ $vendor_id }};
})();
</script>
