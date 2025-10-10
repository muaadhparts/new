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

    // دالة لتحديث نص الشحن مع الشعار
    function updateTryotoShippingDisplay_{{ $vendor_id }}(selectedRadio) {
        if (!selectedRadio) return;

        const shippingText = document.getElementById('shipping_text' + vendorId);
        const value = selectedRadio.value;
        const logo = selectedRadio.getAttribute('data-logo') || '';
        const service = selectedRadio.getAttribute('data-service') || '';
        const companyName = selectedRadio.getAttribute('data-form') || '';
        const viewPrice = selectedRadio.getAttribute('view') || '';

        if (shippingText) {
            // عرض: الشعار + اسم الشركة + الخدمة + السعر
            let html = '<div style="display: flex; align-items: center; gap: 10px;">';

            // الشعار
            if (logo) {
                html += '<img src="' + logo + '" alt="' + companyName + '" style="max-width: 40px; max-height: 40px; object-fit: contain; border-radius: 4px;">';
            }

            // التفاصيل
            html += '<div style="display: flex; flex-direction: column;">';
            html += '<span style="font-weight: 600; color: #4C3533;">' + companyName + '</span>';
            if (service) {
                html += '<small style="color: #6c757d;">' + service + '</small>';
            }
            html += '</div>';

            // السعر
            html += '<span style="margin-left: auto; font-weight: 600; color: #EE1243;">+ ' + viewPrice + '</span>';
            html += '</div>';

            shippingText.innerHTML = html;
        }

        // Trigger recalculation of totals
        if (typeof recalcTotals === 'function') {
            recalcTotals();
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

        // اختيار أول عنصر تلقائياً عند تحميل الصفحة
        setTimeout(function() {
            const firstRadio = modal.querySelector('input[type="radio"][name="shipping[' + vendorId + ']"]:first-of-type');
            if (firstRadio && !document.querySelector('input[type="radio"][name="shipping[' + vendorId + ']"]:checked')) {
                firstRadio.checked = true;
                updateTryotoShippingDisplay_{{ $vendor_id }}(firstRadio);
            } else {
                const checkedRadio = modal.querySelector('input[type="radio"][name="shipping[' + vendorId + ']"]:checked');
                if (checkedRadio) {
                    updateTryotoShippingDisplay_{{ $vendor_id }}(checkedRadio);
                }
            }
        }, 1500);
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
