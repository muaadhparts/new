{{--
    Fitment Details Modal Component
    ================================
    Shows brands and vehicles that a part fits.

    Usage:
    <x-fitment-details-modal />

    Then trigger with JavaScript:
    openFitmentDetailsModal(catalogItemId, partNumber)
--}}

<!-- Fitment Details Modal -->
<div class="modal fade" id="fitmentDetailsModal" tabindex="-1" aria-labelledby="fitmentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fitmentDetailsModalLabel">
                    <i class="fas fa-car me-2"></i>
                    <span id="fitmentDetailsTitle">@lang('Vehicle Compatibility')</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="fitmentDetailsBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">@lang('Loading...')</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Styles now use unified .catalog-offers-* classes from muaadh-system.css --}}

<script>
/**
 * Open the fitment details modal
 * @param {number} catalogItemId - The catalog item ID
 * @param {string} partNumber - Part number for display (optional)
 */
function openFitmentDetailsModal(catalogItemId, partNumber) {
    const modalEl = document.getElementById('fitmentDetailsModal');
    let modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) {
        modal = new bootstrap.Modal(modalEl);
    }

    const modalBody = document.getElementById('fitmentDetailsBody');
    const modalTitle = document.getElementById('fitmentDetailsTitle');

    // Update title
    if (partNumber) {
        modalTitle.innerHTML = '<i class="fas fa-car me-2"></i>' + partNumber;
    } else {
        modalTitle.innerHTML = '<i class="fas fa-car me-2"></i>{{ __("Vehicle Compatibility") }}';
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

    // Fetch fitment details
    fetch('/modal/fitment/' + catalogItemId, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.json();
    })
    .then(data => {
        if (data.html) {
            modalBody.innerHTML = data.html;
        } else {
            modalBody.innerHTML = `
                <div class="catalog-empty">
                    <i class="fas fa-car"></i>
                    <p>{{ __('No fitment information available') }}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading fitment:', error);
        modalBody.innerHTML = `
            <div class="catalog-empty">
                <i class="fas fa-exclamation-triangle" style="color: var(--action-danger, #ef4444);"></i>
                <p>{{ __('Failed to load fitment information') }}</p>
            </div>
        `;
    });
}

// Event delegation for fitment buttons
document.addEventListener('click', function(e) {
    // Handle fitment button clicks
    const btn = e.target.closest('.fitment-details-btn');
    if (btn) {
        e.preventDefault();
        const catalogItemId = btn.dataset.catalogItemId;
        const partNumber = btn.dataset.partNumber || '';
        if (catalogItemId) {
            openFitmentDetailsModal(catalogItemId, partNumber);
        }
        return;
    }

    // Handle brand tab switching inside fitment modal
    const tab = e.target.closest('.fitment-brand-tab');
    if (tab) {
        const targetId = tab.dataset.target;
        const brandIndex = tab.dataset.brandIndex;
        const container = document.getElementById(targetId);
        if (!container) return;

        // Update tabs
        container.querySelectorAll('.fitment-brand-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        // Update panels
        container.querySelectorAll('.fitment-brand-panel').forEach(p => p.classList.remove('active'));
        const panel = container.querySelector(`.fitment-brand-panel[data-brand-panel="${brandIndex}"]`);
        if (panel) panel.classList.add('active');
    }
});
</script>
