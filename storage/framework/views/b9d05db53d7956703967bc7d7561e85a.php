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
                            <div class="provider-methods-wrapper">
                                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('tryoto-componet', ['products' => $array_product,'vendorId' => $vendor_id]);

$__html = app('livewire')->mount($__name, $__params, 'lw-1302242749-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
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

    // دالة لتحديث نص الشحن - استخدام updateVendorShippingText المركزية
    function updateTryotoShippingDisplay_<?php echo e($vendor_id); ?>(selectedRadio) {
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
                let html = '<div style="display: flex; align-items: center; gap: 10px;">';
                if (logo) {
                    html += '<img src="' + logo + '" alt="' + companyName + '" style="max-width: 40px; max-height: 40px; object-fit: contain; border-radius: 4px;">';
                }
                html += '<div style="display: flex; flex-direction: column;">';
                html += '<span style="font-weight: 600; color: #4C3533;">' + companyName + '</span>';
                if (service) {
                    html += '<small style="color: #6c757d;">' + service + '</small>';
                }
                html += '</div>';
                if (viewPrice) {
                    html += '<span style="margin-left: auto; font-weight: 600; color: #EE1243;">+ ' + viewPrice + '</span>';
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
    function initTryotoModal_<?php echo e($vendor_id); ?>() {
        const modal = document.getElementById(modalId);
        if (!modal) {
            setTimeout(initTryotoModal_<?php echo e($vendor_id); ?>, 500);
            return;
        }

        // Handle manual radio changes in Tryoto modal
        modal.addEventListener('change', function(e) {
            if (e.target.matches('input[type="radio"][name="shipping[' + vendorId + ']"]')) {
                updateTryotoShippingDisplay_<?php echo e($vendor_id); ?>(e.target);
            }
        });

        // تحديث العرض عند فتح Modal
        modal.addEventListener('shown.bs.modal', function() {
            const selectedRadio = modal.querySelector('input[type="radio"][name="shipping[' + vendorId + ']"]:checked');
            if (selectedRadio) {
                updateTryotoShippingDisplay_<?php echo e($vendor_id); ?>(selectedRadio);
            }
        });

        // تحديث العرض للعنصر المختار فقط (بدون اختيار تلقائي)
        setTimeout(function() {
            const checkedRadio = modal.querySelector('input[type="radio"][name="shipping[' + vendorId + ']"]:checked');
            if (checkedRadio) {
                updateTryotoShippingDisplay_<?php echo e($vendor_id); ?>(checkedRadio);
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
                        updateTryotoShippingDisplay_<?php echo e($vendor_id); ?>(selectedRadio);
                    }
                }
            });
        }

        // Initialize modal after Livewire loads
        setTimeout(initTryotoModal_<?php echo e($vendor_id); ?>, 500);
    });

    // Fallback if Livewire doesn't exist
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initTryotoModal_<?php echo e($vendor_id); ?>, 1000);
        });
    } else {
        setTimeout(initTryotoModal_<?php echo e($vendor_id); ?>, 1000);
    }

    // Make function globally accessible for restoration
    window['updateTryotoShippingDisplay_' + vendorId] = updateTryotoShippingDisplay_<?php echo e($vendor_id); ?>;
})();
</script>
<?php /**PATH C:\Users\hp\Herd\new\resources\views/includes/frontend/tryoto_shipping_modal.blade.php ENDPATH**/ ?>