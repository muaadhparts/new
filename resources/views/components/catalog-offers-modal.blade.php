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
 * @param {string} sort - Sort order (optional, defaults to 'price_asc')
 */
function openCatalogOffersModal(catalogItemId, partNumber, sort = 'price_asc') {
    const modalEl = document.getElementById('catalogOffersModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) {
        modal = new bootstrap.Modal(modalEl);
    }

    const modalBody = document.getElementById('catalogOffersModalBody');
    const modalTitle = document.getElementById('offersModalTitle');

    // Store current catalog item info for sorting
    modalEl.dataset.catalogItemId = catalogItemId;
    modalEl.dataset.partNumber = partNumber || '';

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

    // Fetch offers with sort parameter
    fetch(`/modal/offers/${catalogItemId}?sort=${sort}`, {
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

        // Note: Qty controls handled globally by qty-control.js (delegated events)

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
 * Reload offers with new sort order (silent update without loading spinner)
 * @param {string} sort - Sort order ('price_asc' or 'price_desc')
 */
function reloadOffersWithSort(sort) {
    const modalEl = document.getElementById('catalogOffersModal');
    const modalBody = document.getElementById('catalogOffersModalBody');
    const catalogItemId = modalEl.dataset.catalogItemId;

    if (!catalogItemId) return;

    // Add loading overlay without replacing content
    const offersContent = modalBody.querySelector('.catalog-offers-content');
    if (offersContent) {
        offersContent.style.opacity = '0.5';
        offersContent.style.pointerEvents = 'none';
    }

    // Fetch offers with new sort (add timestamp to prevent caching)
    fetch(`/modal/offers/${catalogItemId}?sort=${sort}&_t=${Date.now()}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Cache-Control': 'no-cache'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.text();
    })
    .then(html => {
        modalBody.innerHTML = html;

        // Note: Qty controls handled globally by qty-control.js (delegated events)

        // Initialize cart buttons
        if (typeof initCartButtons === 'function') {
            initCartButtons(modalBody);
        }
    })
    .catch(error => {
        console.error('Error loading offers:', error);
        // Restore opacity on error
        if (offersContent) {
            offersContent.style.opacity = '1';
            offersContent.style.pointerEvents = 'auto';
        }
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

// Event delegation for sort dropdown inside modal
document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'offersSort') {
        reloadOffersWithSort(e.target.value);
    }
});
</script>
