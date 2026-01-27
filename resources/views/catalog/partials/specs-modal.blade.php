{{--
    Specifications Modal - Custom Dropdowns (Flow-based)

    Required variables:
    - $catalog: Catalog model
    - $filters: Array of filter specifications (with pre-computed selectedLabel, hasValue)
    - $selectedFilters: Currently selected filters from session
    - $isVinMode: Boolean - whether VIN mode is active

    All display values pre-computed in VehicleCatalogController (DATA_FLOW_POLICY)
--}}

{{-- Specifications Button - using pre-computed selectedCount from filters._meta --}}
<button type="button"
        class="btn btn-primary position-relative specs-btn-mobile"
        data-bs-toggle="modal"
        data-bs-target="#specsModal">
    <i class="fas fa-filter me-1"></i>
    {{ __('catalog.specifications') }}

    @if(($filters['_meta']['selectedCount'] ?? 0) > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
            {{ $filters['_meta']['selectedCount'] }}
        </span>
    @endif
</button>

{{-- Specifications Modal --}}
<div class="modal fade" id="specsModal" tabindex="-1" aria-labelledby="specsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content specs-modal-content">

            {{-- Header --}}
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="specsModalLabel">
                    <i class="fas fa-cog me-2"></i>
                    {{ __('catalog.specifications') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="{{ __('catalog.close') }}"></button>
            </div>

            {{-- Body - Only scrollable element --}}
            <div class="modal-body specs-modal-body">

                {{-- VIN Mode Alert --}}
                @if($isVinMode)
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-car me-1"></i>
                        <strong>{{ __('catalog.vin_mode') }}</strong> - {{ __('catalog.values_readonly') }}
                    </div>
                @endif

                {{-- Filters Form --}}
                <form id="specsForm">
                    <input type="hidden" name="catalog_code" value="{{ $filters['_meta']['catalogCode'] ?? $catalog->code ?? '' }}">

                    {{-- Date Filters (Year & Month) --}}
                    @if(isset($filters['year']) || isset($filters['month']))
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-calendar-alt me-1 text-muted"></i>
                                {{ __('catalog.build_date') }}
                                @if(($filters['year']['readonly'] ?? false) || ($filters['month']['readonly'] ?? false))
                                    <span class="catalog-specs-badge catalog-specs-badge-vin">VIN</span>
                                @endif
                            </label>
                            <div class="row g-2">
                                {{-- Month (using pre-computed selectedLabel) --}}
                                @if(isset($filters['month']))
                                    <div class="col-6">
                                        <div class="specs-select {{ $filters['month']['readonly'] ? 'specs-select--readonly' : '' }}">
                                            <input type="hidden" name="filters[month]" value="{{ $filters['month']['selected'] ?? '' }}">
                                            <button type="button" class="specs-select__trigger" {{ $filters['month']['readonly'] ? 'disabled' : '' }}>
                                                <span class="specs-select__text">{{ $filters['month']['selectedLabel'] }}</span>
                                                <i class="fas fa-chevron-down specs-select__arrow"></i>
                                            </button>
                                            @if(!$filters['month']['readonly'])
                                                <div class="specs-select__list">
                                                    <div class="specs-select__item {{ empty($filters['month']['selected']) ? 'specs-select__item--active' : '' }}" data-value="">
                                                        {{ __('catalog.month') }}
                                                    </div>
                                                    @foreach($filters['month']['items'] as $item)
                                                        <div class="specs-select__item {{ ($filters['month']['selected'] ?? '') == $item['value_id'] ? 'specs-select__item--active' : '' }}"
                                                             data-value="{{ $item['value_id'] }}">
                                                            {{ $item['label'] }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                {{-- Year (using pre-computed selectedLabel) --}}
                                @if(isset($filters['year']))
                                    <div class="col-6">
                                        <div class="specs-select {{ $filters['year']['readonly'] ? 'specs-select--readonly' : '' }}">
                                            <input type="hidden" name="filters[year]" value="{{ $filters['year']['selected'] ?? '' }}">
                                            <button type="button" class="specs-select__trigger" {{ $filters['year']['readonly'] ? 'disabled' : '' }}>
                                                <span class="specs-select__text">{{ $filters['year']['selectedLabel'] }}</span>
                                                <i class="fas fa-chevron-down specs-select__arrow"></i>
                                            </button>
                                            @if(!$filters['year']['readonly'])
                                                <div class="specs-select__list">
                                                    <div class="specs-select__item {{ empty($filters['year']['selected']) ? 'specs-select__item--active' : '' }}" data-value="">
                                                        {{ __('catalog.year') }}
                                                    </div>
                                                    @foreach($filters['year']['items'] as $item)
                                                        <div class="specs-select__item {{ ($filters['year']['selected'] ?? '') == $item['value_id'] ? 'specs-select__item--active' : '' }}"
                                                             data-value="{{ $item['value_id'] }}">
                                                            {{ $item['label'] }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <hr class="my-3">
                    @endif

                    {{-- Other Specification Filters (using pre-computed selectedLabel, hasValue) --}}
                    @foreach($filters as $key => $filter)
                        @if(!in_array($key, ['year', 'month', '_meta']))
                            <div class="mb-3">
                                <label class="form-label">
                                    <span>{{ $filter['label'] }}</span>
                                    @if($filter['readonly'] ?? false)
                                        <span class="catalog-specs-badge catalog-specs-badge-vin">VIN</span>
                                    @elseif($filter['hasValue'] ?? false)
                                        <span class="catalog-specs-badge catalog-specs-badge-set">SET</span>
                                    @endif
                                </label>

                                <div class="specs-select {{ ($filter['readonly'] ?? false) ? 'specs-select--readonly' : '' }}">
                                    <input type="hidden" name="filters[{{ $key }}]" value="{{ $filter['selected'] ?? '' }}">
                                    <button type="button" class="specs-select__trigger" {{ ($filter['readonly'] ?? false) ? 'disabled' : '' }}>
                                        <span class="specs-select__text">{{ $filter['selectedLabel'] }}</span>
                                        <i class="fas fa-chevron-down specs-select__arrow"></i>
                                    </button>
                                    @if(!($filter['readonly'] ?? false))
                                        <div class="specs-select__list">
                                            <div class="specs-select__item {{ empty($filter['selected']) ? 'specs-select__item--active' : '' }}" data-value="">
                                                -- {{ __('catalog.select') }} --
                                            </div>
                                            @foreach($filter['items'] as $item)
                                                <div class="specs-select__item {{ ($filter['selected'] ?? '') == $item['value_id'] ? 'specs-select__item--active' : '' }}"
                                                     data-value="{{ $item['value_id'] }}">
                                                    {{ $item['label'] }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if(empty($filters) || (count($filters) <= 1 && isset($filters['_meta'])))
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            {{ __('catalog.no_specifications') }}
                        </div>
                    @endif
                </form>
            </div>

            {{-- Footer Actions - Always visible --}}
            <div class="modal-footer specs-modal-footer">
                @if(!$isVinMode)
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btnClearSpecs">
                        <i class="fas fa-times me-1"></i>
                        {{ __('catalog.clear_all') }}
                    </button>
                    <button type="button" class="btn btn-success btn-sm" id="btnApplySpecs">
                        <i class="fas fa-check me-1"></i>
                        {{ __('catalog.apply_filters') }}
                    </button>
                @else
                    <div class="text-muted small">
                        <i class="fas fa-lock me-1"></i>
                        {{ __('catalog.filters_locked_vin') }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

{{-- JavaScript for Specs Modal --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('specsForm');
    const btnApply = document.getElementById('btnApplySpecs');
    const btnClear = document.getElementById('btnClearSpecs');
    const modalEl = document.getElementById('specsModal');
    const redirectUrl = @json($specsRedirectUrl ?? null);

    if (!modalEl) return;

    // ========== Custom Select Logic (Flow-based) ==========
    const selects = modalEl.querySelectorAll('.specs-select:not(.specs-select--readonly)');

    function closeAllSelects() {
        if (!selects || !selects.length) return;
        selects.forEach(sel => {
            if (!sel) return;
            sel.classList.remove('specs-select--open');
            const arrow = sel.querySelector('.specs-select__arrow');
            if (arrow) arrow.style.transform = '';
        });
    }

    function toggleSelect(select) {
        const isOpen = select.classList.contains('specs-select--open');
        closeAllSelects();

        if (!isOpen) {
            select.classList.add('specs-select--open');
            const arrow = select.querySelector('.specs-select__arrow');
            if (arrow) arrow.style.transform = 'rotate(180deg)';

            // Scroll to show selected item
            const list = select.querySelector('.specs-select__list');
            const activeItem = list?.querySelector('.specs-select__item--active');
            if (activeItem && list) {
                setTimeout(() => {
                    activeItem.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }, 50);
            }
        }
    }

    function selectItem(select, item) {
        const value = item.dataset.value;
        const label = item.textContent.trim();
        const input = select.querySelector('input[type="hidden"]');
        const textEl = select.querySelector('.specs-select__text');

        // Update value
        if (input) input.value = value;
        if (textEl) textEl.textContent = label;

        // Update active state
        select.querySelectorAll('.specs-select__item').forEach(i => {
            i.classList.remove('specs-select__item--active');
        });
        item.classList.add('specs-select__item--active');

        // Update SET badge for this field
        const formLabel = select.closest('.mb-3')?.querySelector('.form-label');
        if (formLabel && !formLabel.querySelector('.catalog-specs-badge-vin')) {
            const existingBadge = formLabel.querySelector('.catalog-specs-badge-set');
            if (value && value.trim() !== '') {
                if (!existingBadge) {
                    const badge = document.createElement('span');
                    badge.className = 'catalog-specs-badge catalog-specs-badge-set';
                    badge.textContent = 'SET';
                    formLabel.appendChild(badge);
                }
            } else {
                if (existingBadge) existingBadge.remove();
            }
        }

        closeAllSelects();
    }

    // Bind events
    selects.forEach(select => {
        const trigger = select.querySelector('.specs-select__trigger');
        const list = select.querySelector('.specs-select__list');

        if (trigger) {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                toggleSelect(select);
            });
        }

        if (list) {
            list.querySelectorAll('.specs-select__item').forEach(item => {
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    selectItem(select, item);
                });
            });
        }
    });

    // Close on click outside - wrapped in try-catch to prevent blocking other handlers
    document.addEventListener('click', (e) => {
        try {
            if (!e.target.closest('.specs-select')) {
                closeAllSelects();
            }
        } catch (err) {
            console.error('specs-modal click handler error:', err);
        }
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllSelects();
        }
    });

    // Close when modal hides
    modalEl.addEventListener('hidden.bs.modal', closeAllSelects);

    // ========== Form Actions ==========
    const specsButton = document.querySelector('[data-bs-target="#specsModal"]');
    const chipsBar = document.getElementById('specsChipsBar');

    // Update badge count on button
    function updateBadgeCount(count) {
        if (!specsButton) return;

        let badge = specsButton.querySelector('.badge');

        if (count > 0) {
            if (badge) {
                badge.textContent = count;
            } else {
                badge = document.createElement('span');
                badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success';
                badge.textContent = count;
                specsButton.appendChild(badge);
            }
        } else {
            if (badge) badge.remove();
        }
    }

    // Count selected filters
    function countSelectedFilters() {
        let count = 0;
        modalEl.querySelectorAll('.specs-select input[type="hidden"]').forEach(input => {
            if (input.value && input.value.trim() !== '') {
                count++;
            }
        });
        return count;
    }

    // Collect chips data from form
    function collectChipsData() {
        const chips = [];

        modalEl.querySelectorAll('.specs-select').forEach(select => {
            const input = select.querySelector('input[type="hidden"]');
            if (!input || !input.value || input.value.trim() === '') return;

            const inputName = input.name || '';
            const key = inputName.replace('filters[', '').replace(']', '');

            const formGroup = select.closest('.mb-3');
            const labelEl = formGroup?.querySelector('.form-label');
            const labelText = labelEl?.querySelector('span')?.textContent?.trim()
                           || labelEl?.childNodes[0]?.textContent?.trim()
                           || key;

            // Use value_id from hidden input
            const valueText = input.value;

            const isVin = select.classList.contains('specs-select--readonly')
                       || labelEl?.querySelector('.catalog-specs-badge-vin');

            chips.push({
                key: key,
                label: labelText,
                value: valueText,
                source: isVin ? 'vin' : 'manual'
            });
        });

        return chips;
    }

    // Build chips HTML
    function buildChipsHtml(chips) {
        if (!chips || chips.length === 0) return '';

        const hasVin = chips.some(c => c.source === 'vin');
        const icon = hasVin ? 'fa-car' : 'fa-filter';
        const title = hasVin ? '{{ __("catalog.vin_specs") }}' : '{{ __("catalog.selected_specs") }}';
        const vinText = '{{ __("catalog.vin") }}';
        const manualText = '{{ __("catalog.manual") }}';

        let html = `
            <div class="catalog-chips-bar">
                <div class="catalog-chips-header">
                    <strong class="catalog-chips-name">
                        <i class="fas ${icon}"></i>
                        ${title}
                    </strong>
                    <span class="catalog-chips-count">${chips.length}</span>
                </div>
                <div class="catalog-chips-container">
        `;

        chips.forEach(chip => {
            const sourceClass = chip.source === 'vin' ? 'catalog-chip-source-vin' : 'catalog-chip-source-manual';
            const sourceText = chip.source === 'vin' ? vinText : manualText;

            html += `
                <span class="catalog-chip" data-key="${chip.key}">
                    <span class="catalog-chip-label">${chip.label}:</span>
                    <span class="catalog-chip-value">${chip.value}</span>
                    <span class="catalog-chip-source ${sourceClass}">${sourceText}</span>
                </span>
            `;
        });

        html += `
                </div>
            </div>
        `;

        return html;
    }

    // Update chips bar
    function updateChipsBar(chips) {
        if (!chipsBar) return;
        chipsBar.innerHTML = buildChipsHtml(chips);
    }

    // Update SET badges in modal
    function updateSetBadges() {
        modalEl.querySelectorAll('.specs-select').forEach(select => {
            const input = select.querySelector('input[type="hidden"]');
            const label = select.closest('.mb-3')?.querySelector('.form-label');
            if (!label) return;

            const existingBadge = label.querySelector('.catalog-specs-badge-set');
            const hasValue = input && input.value && input.value.trim() !== '';
            const isVin = label.querySelector('.catalog-specs-badge-vin');

            if (hasValue && !isVin) {
                if (!existingBadge) {
                    const badge = document.createElement('span');
                    badge.className = 'catalog-specs-badge catalog-specs-badge-set';
                    badge.textContent = 'SET';
                    label.appendChild(badge);
                }
            } else {
                if (existingBadge) existingBadge.remove();
            }
        });
    }

    // Close modal
    function closeModal() {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }

    // Apply Filters
    if (btnApply) {
        btnApply.addEventListener('click', async function() {
            btnApply.disabled = true;
            btnApply.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> {{ __("catalog.saving") }}';

            try {
                const formData = new FormData(form);
                const filters = {};

                for (const [key, value] of formData.entries()) {
                    if (key.startsWith('filters[') && value) {
                        const filterKey = key.replace('filters[', '').replace(']', '');
                        filters[filterKey] = value;
                    }
                }

                const response = await fetch('/api/specs/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        catalog_code: formData.get('catalog_code'),
                        filters: filters
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // If redirect URL exists (illustrations page), redirect
                    if (redirectUrl) {
                        closeModal();
                        setTimeout(() => window.location.href = redirectUrl, 200);
                    } else {
                        // Update UI without reload
                        const chips = collectChipsData();
                        updateBadgeCount(chips.length);
                        updateChipsBar(chips);
                        updateSetBadges();
                        closeModal();
                    }
                } else {
                    alert(result.message || 'Error saving filters');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error saving filters');
            } finally {
                btnApply.disabled = false;
                btnApply.innerHTML = '<i class="fas fa-check me-1"></i> {{ __("catalog.apply_filters") }}';
            }
        });
    }

    // Clear Filters
    if (btnClear) {
        btnClear.addEventListener('click', async function() {
            btnClear.disabled = true;
            btnClear.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> {{ __("catalog.clearing") }}';

            try {
                const catalogCode = document.querySelector('input[name="catalog_code"]').value;

                const response = await fetch('/api/specs/clear', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        catalog_code: catalogCode
                    })
                });

                const result = await response.json();

                if (result.success) {
                    // If redirect URL exists (illustrations page), redirect
                    if (redirectUrl) {
                        closeModal();
                        setTimeout(() => window.location.href = redirectUrl, 200);
                    } else {
                        // Reset all selects to default (first option) - only non-VIN
                        modalEl.querySelectorAll('.specs-select:not(.specs-select--readonly)').forEach(select => {
                            const input = select.querySelector('input[type="hidden"]');
                            const textEl = select.querySelector('.specs-select__text');
                            const firstItem = select.querySelector('.specs-select__item');

                            if (input) input.value = '';
                            if (textEl && firstItem) textEl.textContent = firstItem.textContent.trim();

                            // Update active state
                            select.querySelectorAll('.specs-select__item').forEach((item, idx) => {
                                item.classList.toggle('specs-select__item--active', idx === 0);
                            });
                        });

                        // Update UI - collect remaining chips (VIN only)
                        const remainingChips = collectChipsData();
                        updateBadgeCount(remainingChips.length);
                        updateChipsBar(remainingChips);
                        updateSetBadges();
                        closeModal();
                    }
                } else {
                    alert(result.message || 'Error clearing filters');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error clearing filters');
            } finally {
                btnClear.disabled = false;
                btnClear.innerHTML = '<i class="fas fa-times me-1"></i> {{ __("catalog.clear_all") }}';
            }
        });
    }
});
</script>
