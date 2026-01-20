{{--
    Fitment Brands Modal Component
    ===============================
    Shows all vehicle brands a part fits in a responsive modal.

    Usage:
    <x-fitment-brands-modal />

    Then trigger with JavaScript:
    openFitmentModal(brands, partNumber)
--}}

<!-- Fitment Brands Modal -->
<div class="modal fade" id="fitmentBrandsModal" tabindex="-1" aria-labelledby="fitmentBrandsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fitmentBrandsModalLabel">
                    <i class="fas fa-car me-2"></i>
                    <span id="fitmentModalTitle">@lang('Vehicle Compatibility')</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3" id="fitmentModalSubtitle">
                    @lang('This part fits the following vehicle brands'):
                </p>
                <div class="row g-3" id="fitmentBrandsContainer">
                    <!-- Brands will be inserted here via JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Close')</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Fitment Modal Styles */
    .fitment-brand-card {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        background: var(--surface-secondary, #f8f9fa);
        border-radius: 8px;
        border: 1px solid var(--border-default, #dee2e6);
        transition: all 0.2s ease;
        text-decoration: none;
        color: inherit;
    }

    .fitment-brand-card:hover {
        background: var(--surface-primary, #fff);
        border-color: var(--action-primary, #0d6efd);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }

    .fitment-brand-logo {
        width: 48px;
        height: 48px;
        object-fit: contain;
        margin-inline-end: 12px;
        border-radius: 4px;
        background: #fff;
        padding: 4px;
    }

    .fitment-brand-logo-placeholder {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-inline-end: 12px;
        border-radius: 4px;
        background: var(--action-primary, #0d6efd);
        color: #fff;
        font-size: 20px;
    }

    .fitment-brand-name {
        font-weight: 600;
        font-size: 1rem;
        color: var(--text-primary, #212529);
    }

    /* Mobile responsive */
    @media (max-width: 576px) {
        .fitment-brand-card {
            padding: 10px 12px;
        }

        .fitment-brand-logo,
        .fitment-brand-logo-placeholder {
            width: 40px;
            height: 40px;
        }

        .fitment-brand-name {
            font-size: 0.9rem;
        }
    }

    /* Fitment Badge Button in Cards */
    .fitment-brands-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 2px 8px;
        font-size: 0.75rem;
        background: var(--action-primary, #0d6efd);
        color: #fff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .fitment-brands-btn:hover {
        background: var(--action-primary-hover, #0b5ed7);
        transform: scale(1.05);
    }

    .fitment-brands-btn i {
        font-size: 0.7rem;
    }
</style>

<script>
    /**
     * Open the fitment brands modal
     * @param {Array} brands - Array of {id, name, logo, slug}
     * @param {string} partNumber - Part number for display
     */
    function openFitmentModal(brands, partNumber) {
        const container = document.getElementById('fitmentBrandsContainer');
        const title = document.getElementById('fitmentModalTitle');
        const subtitle = document.getElementById('fitmentModalSubtitle');

        // Update title with part number if provided
        if (partNumber) {
            title.innerHTML = '<i class="fas fa-car me-2"></i>' + partNumber;
        }

        // Update subtitle with count
        subtitle.textContent = '{{ __("This part fits") }} ' + brands.length + ' {{ __("vehicle brands") }}:';

        // Clear previous content
        container.innerHTML = '';

        // Add brand cards
        brands.forEach(function(brand) {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-4';

            const logoHtml = brand.logo
                ? '<img src="' + brand.logo + '" alt="' + brand.name + '" class="fitment-brand-logo">'
                : '<div class="fitment-brand-logo-placeholder"><i class="fas fa-car"></i></div>';

            col.innerHTML = `
                <a href="/catalog/${brand.slug || ''}" class="fitment-brand-card">
                    ${logoHtml}
                    <span class="fitment-brand-name">${brand.name}</span>
                </a>
            `;

            container.appendChild(col);
        });

        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('fitmentBrandsModal'));
        modal.show();
    }

    // Initialize click handlers for fitment buttons
    document.addEventListener('DOMContentLoaded', function() {
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.fitment-brands-btn');
            if (btn) {
                e.preventDefault();
                const brands = JSON.parse(btn.dataset.brands || '[]');
                const partNumber = btn.dataset.partNumber || '';
                openFitmentModal(brands, partNumber);
            }
        });
    });
</script>
