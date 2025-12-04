<div>
    {{-- ========================================
         زر فتح نافذة المواصفات
         ======================================== --}}
    <button class="btn btn-primary position-relative" type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#specsOffcanvas"
            aria-controls="specsOffcanvas">
        <i class="fas fa-sliders-h me-1"></i>
        {{ __('Specifications') }}

        @if($selectedCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success">
                {{ $selectedCount }}
            </span>
        @endif
    </button>

    {{-- ========================================
         نافذة المواصفات (Offcanvas)
         ======================================== --}}
    <div class="offcanvas offcanvas-start" tabindex="-1" id="specsOffcanvas"
         aria-labelledby="specsOffcanvasLabel" data-bs-backdrop="static" style="width: 320px;">

        {{-- Header --}}
        <div class="offcanvas-header border-bottom bg-light">
            <h5 class="offcanvas-title" id="specsOffcanvasLabel">
                <i class="fas fa-cog me-2"></i>
                {{ __('Specifications') }}
                @if($catalogName)
                    <small class="d-block text-muted fs-6">{{ $catalogName }}</small>
                @endif
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

            {{-- Filters List --}}
            <div class="p-3">

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
                                    <select class="form-select form-select-sm {{ ($filters['month']['readonly'] ?? false) ? 'bg-light' : '' }}"
                                            wire:model.live="selectedValues.month"
                                            @if($filters['month']['readonly'] ?? false) disabled @endif>
                                        <option value="">{{ __('Month') }}</option>
                                        @foreach($filters['month']['items'] as $item)
                                            <option value="{{ $item['value_id'] }}">{{ $item['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Year --}}
                            @if(isset($filters['year']))
                                <div class="col-6">
                                    <select class="form-select form-select-sm {{ ($filters['year']['readonly'] ?? false) ? 'bg-light' : '' }}"
                                            wire:model.live="selectedValues.year"
                                            @if($filters['year']['readonly'] ?? false) disabled @endif>
                                        <option value="">{{ __('Year') }}</option>
                                        @foreach($filters['year']['items'] as $item)
                                            <option value="{{ $item['value_id'] }}">{{ $item['label'] }}</option>
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
                            $currentValue = $selectedValues[$key] ?? '';
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

                            <select class="form-select form-select-sm {{ $isReadonly ? 'bg-light' : '' }}"
                                    wire:model.live="selectedValues.{{ $key }}"
                                    @if($isReadonly) disabled @endif>
                                <option value="">-- {{ __('Select') }} --</option>
                                @foreach($filter['items'] as $item)
                                    <option value="{{ $item['value_id'] }}">{{ $item['label'] }}</option>
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

            </div>
        </div>

        {{-- Footer Actions --}}
        <div class="offcanvas-footer border-top bg-light p-3">
            @if(!$isVinMode)
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" wire:click="save" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">
                            <i class="fas fa-check me-1"></i>
                            {{ __('Apply Filters') }}
                        </span>
                        <span wire:loading wire:target="save">
                            <i class="fas fa-spinner fa-spin me-1"></i>
                            {{ __('Saving...') }}
                        </span>
                    </button>
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="clearFilters">
                            <i class="fas fa-times me-1"></i>
                            {{ __('Clear All') }}
                        </span>
                        <span wire:loading wire:target="clearFilters">
                            <i class="fas fa-spinner fa-spin me-1"></i>
                            {{ __('Clearing...') }}
                        </span>
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

    {{-- ========================================
         JavaScript Events
         ======================================== --}}
    @script
    <script>
        function closeAndReload() {
            console.log('closeAndReload called');
            const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('specsOffcanvas'));
            if (offcanvas) offcanvas.hide();
            setTimeout(() => window.location.reload(), 300);
        }

        $wire.on('specs-saved', () => {
            console.log('specs-saved event received');
            closeAndReload();
        });

        $wire.on('specs-cleared', () => {
            console.log('specs-cleared event received');
            closeAndReload();
        });
    </script>
    @endscript

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
</div>
