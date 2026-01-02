<div class="modal fade gs-modal" id="vendor_package{{ $vendor_id }}" tabindex="-1" role="dialog"
    aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog send-message-modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content send-message-modal-content form-group">
            <div class="modal-header w-100">
                <h4 class="title" id="exampleModalLongTitle">@lang('Packaging')</h4>
                <button type="button" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-regular fa-circle-xmark gs-modal-close-btn"></i>
                </button>
            </div>
            <div class="packeging-area">
                <div class="summary-inner-box">
                    <div class="inputs-wrapper">
                        @forelse($packaging as $data)
                        <div class="gs-radio-wrapper">
                            <input type="radio" class="packing"
                                view="{{ $curr->sign }}{{ round($data->price * $curr->value, 2) }}"
                                data-form="{{ $data->title }}"
                                id="free-package{{ $data->id }}"
                                ref="{{ $vendor_id }}"
                                data-price="{{ round($data->price * $curr->value, 2) }}"
                                name="packeging[{{ $vendor_id }}]"
                                value="{{ $data->id }}">
                            <label class="icon-label" for="free-package{{ $data->id }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" fill="#FDFDFD" />
                                    <rect x="0.5" y="0.5" width="19" height="19" rx="9.5" stroke="#EE1243" />
                                    <circle cx="10" cy="10" r="4" fill="#EE1243" />
                                </svg>
                            </label>
                            <label for="free-package{{ $data->id }}">
                                {{ $data->title }}
                                @if ($data->price != 0)
                                + {{ $curr->sign }}{{ round($data->price * $curr->value, 2) }}
                                @endif
                                <small>{{ $data->subtitle }}</small>
                            </label>
                        </div>
                        @empty
                        <p>@lang('No Packaging Method Available')</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const merchantId = {{ $vendor_id }};
    const modalId = 'vendor_package' + merchantId;
    const currSign = '{{ $curr->sign }}';

    function initPackagingModal() {
        const modal = document.getElementById(modalId);
        if (!modal) {
            setTimeout(initPackagingModal, 100);
            return;
        }

        const packingRadios = modal.querySelectorAll('input.packing[ref="' + merchantId + '"]');

        packingRadios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                if (!this.checked) return;

                const price = parseFloat(this.getAttribute('data-price')) || 0;
                const title = this.getAttribute('data-form');

                // Update packing text display
                const packingText = document.getElementById('packing_text' + merchantId);
                if (packingText) {
                    packingText.textContent = title + ': ' + currSign + price.toFixed(2);
                }

                // ✅ Update PriceSummary directly
                if (typeof window.PriceSummary !== 'undefined') {
                    window.PriceSummary.updatePacking(price);
                    console.log('✅ Packing updated via PriceSummary:', { price: price });
                }

                // Also call global function for backward compatibility
                if (typeof window.getPacking === 'function') {
                    window.getPacking();
                }
            });
        });

        // Trigger update when modal opens if already selected
        modal.addEventListener('shown.bs.modal', function() {
            const checked = modal.querySelector('input.packing[ref="' + merchantId + '"]:checked');
            if (checked) {
                checked.dispatchEvent(new Event('change'));
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPackagingModal);
    } else {
        initPackagingModal();
    }
})();
</script>
