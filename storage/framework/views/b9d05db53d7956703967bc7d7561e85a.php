<div class="modal fade gs-modal" id="<?php echo e($modalId); ?>" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog xsend-message-modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content send-message-modal-content form-group">
            <div class="modal-header w-100">
                <h4 class="title" id="exampleModalLongTitle"><?php echo e($providerLabel); ?></h4>
                <button type="button" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-regular fa-circle-xmark gs-modal-close-btn"></i>
                </button>
            </div>
            <div class="packeging-area">
                <div class="summary-inner-box">
                    <div class="inputs-wrapper">
                        <div class="shipping-provider-section tryoto-shipping-section">
                            <div class="provider-methods-wrapper" id="tryoto-options-container-<?php echo e($vendor_id); ?>">
                                
                                <div class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">جاري التحميل...</span>
                                    </div>
                                    <p class="mt-2 text-muted">جاري تحميل خيارات الشحن...</p>
                                </div>
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
    const modalId = '<?php echo e($modalId); ?>';
    const vendorId = <?php echo e($vendor_id); ?>;
    const containerId = 'tryoto-options-container-' + vendorId;
    let optionsLoaded = false;

    // دالة لتحديث نص الشحن
    function updateTryotoShippingDisplay(selectedRadio) {
        if (typeof updateVendorShippingText === 'function') {
            updateVendorShippingText(vendorId);
        } else {
            if (!selectedRadio) return;

            const shippingText = document.getElementById('shipping_text' + vendorId);
            const logo = selectedRadio.getAttribute('data-logo') || '';
            const service = selectedRadio.getAttribute('data-service') || '';
            const companyName = selectedRadio.getAttribute('data-company') || selectedRadio.getAttribute('data-form') || '';
            const viewPrice = selectedRadio.getAttribute('data-view') || selectedRadio.getAttribute('view') || '';

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

    // جلب خيارات الشحن من API
    function loadTryotoOptions() {
        if (optionsLoaded) return;

        const container = document.getElementById(containerId);
        if (!container) return;

        fetch('<?php echo e(route("api.shipping.tryoto.html")); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '<?php echo e(csrf_token()); ?>',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                vendor_id: vendorId
            })
        })
        .then(response => response.json())
        .then(data => {
            optionsLoaded = true;
            if (data.success && data.html) {
                container.innerHTML = data.html;
                initRadioListeners();
            } else if (data.error) {
                container.innerHTML = '<div class="alert alert-warning">' +
                    '<i class="fas fa-exclamation-triangle me-2"></i>' +
                    '<strong>خدمة الشحن الذكي غير متاحة</strong><br>' +
                    '<span>' + (data.error || 'حدث خطأ غير متوقع') + '</span></div>';
            } else {
                container.innerHTML = '<div class="alert alert-info">' +
                    '<i class="fas fa-info-circle me-2"></i>' +
                    'لا توجد خيارات شحن متاحة</div>';
            }
        })
        .catch(error => {
            console.error('Tryoto API Error:', error);
            container.innerHTML = '<div class="alert alert-danger">' +
                '<i class="fas fa-times-circle me-2"></i>' +
                'حدث خطأ في تحميل خيارات الشحن</div>';
        });
    }

    // ربط الأحداث بالـ radio buttons
    function initRadioListeners() {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.querySelectorAll('input[type="radio"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                updateTryotoShippingDisplay(this);
            });
        });
    }

    // Initialize modal
    function initTryotoModal() {
        const modal = document.getElementById(modalId);
        if (!modal) {
            setTimeout(initTryotoModal, 500);
            return;
        }

        // تحميل الخيارات عند فتح Modal
        modal.addEventListener('show.bs.modal', function() {
            loadTryotoOptions();
        });

        // تحديث العرض عند فتح Modal
        modal.addEventListener('shown.bs.modal', function() {
            const selectedRadio = modal.querySelector('input[type="radio"][name="shipping[' + vendorId + ']"]:checked');
            if (selectedRadio) {
                updateTryotoShippingDisplay(selectedRadio);
            }
        });
    }

    // Start initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTryotoModal);
    } else {
        initTryotoModal();
    }

    // Make function globally accessible
    window['updateTryotoShippingDisplay_' + vendorId] = updateTryotoShippingDisplay;
    window['loadTryotoOptions_' + vendorId] = loadTryotoOptions;
})();
</script>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/includes/frontend/tryoto_shipping_modal.blade.php ENDPATH**/ ?>