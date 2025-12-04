{{--
    Specifications Modal - Pure JavaScript/Alpine.js

    Required variables:
    - $catalog: Catalog model
    - $filters: Array of filter specifications
    - $selectedFilters: Currently selected filters from session
    - $isVinMode: Boolean - whether VIN mode is active
--}}

@php
    $catalogCode = $catalog->code ?? '';
    $catalogName = $catalog->name ?? $catalog->shortName ?? $catalogCode;
    $catalogYears = formatYearRange($catalog->beginYear ?? null, $catalog->endYear ?? null);
@endphp

{{-- Specifications Button --}}
<button type="button"
        class="btn btn-primary position-relative"
        data-bs-toggle="offcanvas"
        data-bs-target="#specsOffcanvas"
        aria-controls="specsOffcanvas">
    <i class="fas fa-sliders-h me-1"></i>
    {{ __('Specifications') }}

    @php
        $selectedCount = collect($selectedFilters)->filter(fn($v) =>
            is_array($v) ? !empty($v['value_id']) : !empty($v)
        )->count();
    @endphp

    @if($selectedCount > 0)
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
            {{ $selectedCount }}
        </span>
    @endif
</button>

{{-- Offcanvas Modal --}}
<div class="offcanvas offcanvas-start" tabindex="-1" id="specsOffcanvas"
     aria-labelledby="specsOffcanvasLabel" data-bs-backdrop="static" style="width: 340px;">

    {{-- Header --}}
    <div class="offcanvas-header border-bottom bg-light">
        <h5 class="offcanvas-title" id="specsOffcanvasLabel">
            <i class="fas fa-cog me-2"></i>
            {{ __('Specifications') }}
            <small class="d-block text-muted fs-6 mt-1">
                {{ $catalogName }} {{ $catalogYears ? "($catalogYears)" : '' }}
            </small>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    {{-- Body --}}
    <div class="offcanvas-body p-0">

        {{-- VIN Mode Alert --}}
        @if($isVinMode)
            <div class="alert alert-info m-3 py-2 mb-0 rounded-2">
                <i class="fas fa-car me-1"></i>
                <strong>VIN Mode</strong> - {{ __('Values are read-only') }}
            </div>
        @endif

        {{-- Filters Form --}}
        <form id="specsForm" class="p-3">
            <input type="hidden" name="catalog_code" value="{{ $catalogCode }}">

            {{-- Date Filters (Year & Month) --}}
            @if(isset($filters['year']) || isset($filters['month']))
                <div class="mb-3">
                    <label class="form-label fw-semibold mb-2">
                        <i class="fas fa-calendar-alt me-1 text-muted"></i>
                        {{ __('Build Date') }}
                        @if(($filters['year']['readonly'] ?? false) || ($filters['month']['readonly'] ?? false))
                            <span class="badge bg-primary ms-1" style="font-size: 0.65rem;">VIN</span>
                        @endif
                    </label>
                    <div class="row g-2">
                        {{-- Month --}}
                        @if(isset($filters['month']))
                            <div class="col-6">
                                <select name="filters[month]"
                                        class="form-select form-select-sm {{ ($filters['month']['readonly'] ?? false) ? 'bg-light' : '' }}"
                                        {{ ($filters['month']['readonly'] ?? false) ? 'disabled' : '' }}>
                                    <option value="">{{ __('Month') }}</option>
                                    @foreach($filters['month']['items'] as $item)
                                        <option value="{{ $item['value_id'] }}"
                                                {{ ($filters['month']['selected'] ?? '') == $item['value_id'] ? 'selected' : '' }}>
                                            {{ $item['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Year --}}
                        @if(isset($filters['year']))
                            <div class="col-6">
                                <select name="filters[year]"
                                        class="form-select form-select-sm {{ ($filters['year']['readonly'] ?? false) ? 'bg-light' : '' }}"
                                        {{ ($filters['year']['readonly'] ?? false) ? 'disabled' : '' }}>
                                    <option value="">{{ __('Year') }}</option>
                                    @foreach($filters['year']['items'] as $item)
                                        <option value="{{ $item['value_id'] }}"
                                                {{ ($filters['year']['selected'] ?? '') == $item['value_id'] ? 'selected' : '' }}>
                                            {{ $item['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </div>
                <hr class="my-3">
            @endif

            {{-- Other Specification Filters --}}
            @foreach($filters as $key => $filter)
                @if(!in_array($key, ['year', 'month']))
                    @php
                        $isReadonly = $filter['readonly'] ?? false;
                        $currentValue = $filter['selected'] ?? '';
                        $hasValue = !empty($currentValue);
                    @endphp

                    <div class="mb-3">
                        <label class="form-label fw-semibold mb-1 d-flex align-items-center">
                            <span>{{ $filter['label'] }}</span>
                            @if($isReadonly)
                                <span class="badge bg-primary ms-auto" style="font-size: 0.6rem;">VIN</span>
                            @elseif($hasValue)
                                <span class="badge bg-success ms-auto" style="font-size: 0.6rem;">SET</span>
                            @endif
                        </label>

                        <select name="filters[{{ $key }}]"
                                class="form-select form-select-sm {{ $isReadonly ? 'bg-light' : '' }}"
                                {{ $isReadonly ? 'disabled' : '' }}>
                            <option value="">-- {{ __('Select') }} --</option>
                            @foreach($filter['items'] as $item)
                                <option value="{{ $item['value_id'] }}"
                                        {{ $currentValue == $item['value_id'] ? 'selected' : '' }}>
                                    {{ $item['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            @endforeach

            @if(empty($filters))
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    {{ __('No specifications available') }}
                </div>
            @endif
        </form>
    </div>

    {{-- Footer Actions --}}
    <div class="offcanvas-footer border-top bg-light p-3">
        @if(!$isVinMode)
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-success" id="btnApplySpecs">
                    <i class="fas fa-check me-1"></i>
                    {{ __('Apply Filters') }}
                </button>
                <button type="button" class="btn btn-outline-secondary" id="btnClearSpecs">
                    <i class="fas fa-times me-1"></i>
                    {{ __('Clear All') }}
                </button>
            </div>
        @else
            <div class="text-center text-muted small py-2">
                <i class="fas fa-lock me-1"></i>
                {{ __('Filters locked (VIN mode)') }}
            </div>
        @endif
    </div>
</div>

{{-- JavaScript for Specs Modal --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('specsForm');
    const btnApply = document.getElementById('btnApplySpecs');
    const btnClear = document.getElementById('btnClearSpecs');
    const offcanvasEl = document.getElementById('specsOffcanvas');

    // Apply Filters
    if (btnApply) {
        btnApply.addEventListener('click', async function() {
            btnApply.disabled = true;
            btnApply.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> {{ __("Saving...") }}';

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
                    // Close modal and reload page
                    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (offcanvas) offcanvas.hide();
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    alert(result.message || 'Error saving filters');
                    btnApply.disabled = false;
                    btnApply.innerHTML = '<i class="fas fa-check me-1"></i> {{ __("Apply Filters") }}';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error saving filters');
                btnApply.disabled = false;
                btnApply.innerHTML = '<i class="fas fa-check me-1"></i> {{ __("Apply Filters") }}';
            }
        });
    }

    // Clear Filters
    if (btnClear) {
        btnClear.addEventListener('click', async function() {
            btnClear.disabled = true;
            btnClear.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> {{ __("Clearing...") }}';

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
                    // Close modal and reload page
                    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (offcanvas) offcanvas.hide();
                    setTimeout(() => window.location.reload(), 200);
                } else {
                    alert(result.message || 'Error clearing filters');
                    btnClear.disabled = false;
                    btnClear.innerHTML = '<i class="fas fa-times me-1"></i> {{ __("Clear All") }}';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error clearing filters');
                btnClear.disabled = false;
                btnClear.innerHTML = '<i class="fas fa-times me-1"></i> {{ __("Clear All") }}';
            }
        });
    }
});
</script>

<style>
.offcanvas-footer {
    position: sticky;
    bottom: 0;
}
#specsOffcanvas .form-select-sm {
    font-size: 0.85rem;
}
#specsOffcanvas .form-label {
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
}
</style>
