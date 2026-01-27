{{-- resources/views/partials/api/fitment-details.blade.php --}}
{{-- Fitment Details: Shows brands and vehicles that a part fits --}}
{{-- Uses same styling as catalog-item-offers.blade.php --}}
{{-- Pre-computed: $brandCount, $totalVehicles, $hasMultipleBrands, $uniqueId in CatalogItemApiController (DATA_FLOW_POLICY) --}}

<div class="catalog-offers-content" id="{{ $uniqueId }}">
    @if($brandCount > 0)
        {{-- Summary Header --}}
        <div class="catalog-offers-header">
            <div class="catalog-offers-summary d-flex align-items-center gap-2 flex-wrap">
                <span class="catalog-badge catalog-badge-primary">
                    <i class="fas fa-car"></i>
                    {{ $brandCount }} @lang('brands')
                </span>
                <span class="catalog-badge catalog-badge-secondary">
                    <i class="fas fa-list"></i>
                    {{ $totalVehicles }} @lang('vehicles')
                </span>
            </div>
        </div>

        @if($hasMultipleBrands)
            {{-- Brand Tabs for Multiple Brands --}}
            <div class="fitment-brand-tabs d-flex flex-wrap gap-2 mb-3">
                @foreach($brands as $index => $brand)
                    <button type="button"
                            class="fitment-brand-tab {{ $loop->first ? 'active' : '' }}"
                            data-brand-index="{{ $index }}"
                            data-target="{{ $uniqueId }}">
                        @if($brand['logo'] ?? null)
                            <img src="{{ $brand['logo'] }}" alt="" class="fitment-brand-tab-logo">
                        @else
                            <i class="fas fa-car"></i>
                        @endif
                        <span>{{ $brand['name'] }}</span>
                        <small class="fitment-brand-tab-count">{{ count($brand['vehicles'] ?? []) }}</small>
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Brand Content Panels --}}
        @foreach($brands as $index => $brand)
            <div class="fitment-brand-panel {{ $loop->first ? 'active' : '' }}"
                 data-brand-panel="{{ $index }}"
                 data-parent="{{ $uniqueId }}">

                @if(!$hasMultipleBrands)
                    {{-- Single Brand Header --}}
                    <div class="catalog-offers-quality-header mb-3">
                        <div class="d-flex align-items-center gap-2">
                            @if($brand['logo'] ?? null)
                                <img src="{{ $brand['logo'] }}"
                                     alt="{{ $brand['name'] }}"
                                     class="catalog-offers-quality-logo">
                            @else
                                <div class="catalog-offers-merchant-avatar catalog-offers-merchant-avatar--placeholder">
                                    <i class="fas fa-car"></i>
                                </div>
                            @endif
                            <div>
                                <span class="catalog-offers-quality-name">{{ $brand['name'] }}</span>
                                <small class="text-muted ms-2">
                                    ({{ count($brand['vehicles'] ?? []) }} @lang('vehicles'))
                                </small>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Vehicles Table --}}
                @if(!empty($brand['vehicles']))
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle fitment-table mb-0">
                            <thead>
                                <tr>
                                    <th>@lang('Vehicle')</th>
                                    <th class="text-center" style="width: 100px;">@lang('Code')</th>
                                    <th class="text-center" style="width: 80px;">@lang('From')</th>
                                    <th class="text-center" style="width: 80px;">@lang('To')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($brand['vehicles'] as $vehicle)
                                    <tr>
                                        <td>
                                            <i class="fas fa-car-side text-muted me-2" style="font-size: 0.75rem;"></i>
                                            {{ $vehicle['localized_name'] ?? '—' }}
                                        </td>
                                        <td class="text-center">
                                            <code class="text-muted">{{ $vehicle['code'] ?? '—' }}</code>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-muted">{{ $vehicle['formatted_begin'] ?? '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-muted">{{ $vehicle['formatted_end'] ?? '—' }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-3 text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        @lang('No vehicles found')
                    </div>
                @endif
            </div>
        @endforeach
    @else
        {{-- No Fitment --}}
        <div class="catalog-empty">
            <i class="fas fa-car"></i>
            <p>@lang('No fitment information available')</p>
        </div>
    @endif
</div>

{{-- Styles in muaadh-system.css, Tab switching in fitment-details-modal component --}}
