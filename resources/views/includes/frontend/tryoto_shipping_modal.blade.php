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
                            <div class="provider-methods-wrapper" id="tryoto-options-container-{{ $vendor_id }}">
                                {{-- سيتم تحميل خيارات الشحن عبر API --}}
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
    const modalId = '{{ $modalId }}';
    const vendorId = {{ $vendor_id }};
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

        fetch('{{ route("api.shipping.tryoto.html") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
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

                // ✅ تحديث سعر الشحن في الملخص
                updateShippingSummary();
            });
        });
    }

    // ✅ تحديث سعر الشحن في الملخص والإجمالي
    function updateShippingSummary() {
        // جمع أسعار الشحن من جميع التجار
        let totalShipping = 0;

        // 1. جمع الشحن من Tryoto radios
        document.querySelectorAll('.shipping-option:checked, input[name^="shipping["]:checked').forEach(function(radio) {
            const price = parseFloat(radio.getAttribute('data-price')) || 0;
            totalShipping += price;
        });

        // 2. جمع الشحن من الخيارات العادية (.shipping)
        document.querySelectorAll('.shipping:checked').forEach(function(radio) {
            const price = parseFloat(radio.getAttribute('data-price')) || 0;
            totalShipping += price;
        });

        console.log('🚚 Tryoto: Total shipping updated:', totalShipping);

        // تحديث المتغير العام mship
        if (typeof window.mship !== 'undefined') {
            window.mship = totalShipping;
        }

        // تحديث عرض سعر الشحن في الملخص
        const shippingView = document.querySelector('.shipping_cost_view');
        if (shippingView) {
            const currSign = '{{ $curr->sign ?? "SAR" }}';
            const pos = {{ $gs->currency_format ?? 0 }};
            if (pos == 0) {
                shippingView.textContent = currSign + totalShipping.toFixed(2);
            } else {
                shippingView.textContent = totalShipping.toFixed(2) + currSign;
            }
        }

        // تحديث الإجمالي النهائي
        if (typeof window.updateFinalTotal === 'function') {
            window.updateFinalTotal();
        } else {
            // Fallback: حساب يدوي
            const baseTotal = parseFloat(document.getElementById('tgrandtotal')?.value) || 0;
            const taxAmount = parseFloat(document.getElementById('tax_amount_value')?.value) || 0;
            const packingTotal = parseFloat(window.mpack) || 0;

            const finalTotal = baseTotal + taxAmount + totalShipping + packingTotal;

            const finalCostEl = document.getElementById('final-cost');
            if (finalCostEl) {
                const currSign = '{{ $curr->sign ?? "SAR" }}';
                const pos = {{ $gs->currency_format ?? 0 }};
                if (pos == 0) {
                    finalCostEl.textContent = currSign + finalTotal.toFixed(2);
                } else {
                    finalCostEl.textContent = finalTotal.toFixed(2) + currSign;
                }
            }

            const grandtotalInput = document.getElementById('grandtotal');
            if (grandtotalInput) {
                grandtotalInput.value = finalTotal.toFixed(2);
            }
        }
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
