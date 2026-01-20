{{-- Catalog Offers Modal - Global Component --}}
{{-- Shows offers grouped by: Quality Brand → Merchant → Branch --}}

<div class="modal fade" id="catalogOffersModal" tabindex="-1" aria-labelledby="catalogOffersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="catalogOffersModalLabel">
                    <i class="fas fa-tags me-2"></i>
                    <span id="offersModalTitle">@lang('Available Offers')</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="catalogOffersModalBody">
                {{-- Content loaded via AJAX --}}
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">@lang('Loading...')</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Open the catalog offers modal
 * @param {number} catalogItemId - The catalog item ID
 * @param {string} partNumber - Part number for display (optional)
 */
function openCatalogOffersModal(catalogItemId, partNumber) {
    const modal = new bootstrap.Modal(document.getElementById('catalogOffersModal'));
    const modalBody = document.getElementById('catalogOffersModalBody');
    const modalTitle = document.getElementById('offersModalTitle');

    // Update title
    if (partNumber) {
        modalTitle.textContent = '{{ __("Offers for") }} ' + partNumber;
    } else {
        modalTitle.textContent = '{{ __("Available Offers") }}';
    }

    // Show loading
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">{{ __('Loading...') }}</span>
            </div>
        </div>
    `;

    // Show modal
    modal.show();

    // Fetch offers
    fetch(`/modal/offers/${catalogItemId}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.text();
    })
    .then(html => {
        modalBody.innerHTML = html;

        // Initialize quantity controls
        initializeQtyControls(modalBody);

        // Initialize cart buttons
        if (typeof initCartButtons === 'function') {
            initCartButtons(modalBody);
        }
    })
    .catch(error => {
        console.error('Error loading offers:', error);
        modalBody.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ __('Failed to load offers. Please try again.') }}
            </div>
        `;
    });
}

/**
 * Initialize quantity controls for dynamically loaded content
 */
function initializeQtyControls(container) {
    // Plus buttons
    container.querySelectorAll('.qty-plus').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.getElementById('qty_' + targetId);
            if (!input) return;

            const stock = parseInt(this.dataset.stock) || 999;
            const preordered = parseInt(this.dataset.preordered) || 0;
            const maxQty = preordered ? 999 : stock;
            let val = parseInt(input.value) || 1;

            if (val < maxQty) {
                input.value = val + 1;
            }
        });
    });

    // Minus buttons
    container.querySelectorAll('.qty-minus').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const input = document.getElementById('qty_' + targetId);
            if (!input) return;

            const minQty = parseInt(this.dataset.min) || 1;
            let val = parseInt(input.value) || 1;

            if (val > minQty) {
                input.value = val - 1;
            }
        });
    });
}

// Event delegation for offers buttons
document.addEventListener('click', function(e) {
    const offersBtn = e.target.closest('.catalog-offers-btn, .open-offers-modal');
    if (offersBtn) {
        e.preventDefault();
        const catalogItemId = offersBtn.dataset.catalogItemId;
        const partNumber = offersBtn.dataset.partNumber || '';
        if (catalogItemId) {
            openCatalogOffersModal(catalogItemId, partNumber);
        }
    }
});
</script>
