{{-- resources/views/partials/api/compatibility-tabs.blade.php --}}
{{-- API-based compatibility tabs partial (No Livewire) --}}
{{-- Uses catalog-unified.css for styling --}}
{{-- Note: $results items should be objects from source (DATA_FLOW_POLICY) --}}

<div class="catalog-modal-content">
    @if($results && count($results))
        {{-- Header --}}
        <div class="catalog-section-header">
            <h5>
                <i class="fas fa-car"></i>
                @lang('labels.fits')
            </h5>
            <span class="badge bg-secondary">{{ count($results) }} @lang('items')</span>
        </div>

        {{-- Desktop Table --}}
        <div class="d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle catalog-table">
                    <thead>
                        <tr>
                            <th>@lang('compatibility.part_number')</th>
                            <th>@lang('compatibility.vehicle_name')</th>
                            <th>@lang('compatibility.catalog_code')</th>
                            <th class="text-center">@lang('compatibility.from_year')</th>
                            <th class="text-center">@lang('compatibility.to_year')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $item)
                            <tr>
                                <td><code class="fw-bold text-dark">{{ $item->part_number ?? $item['part_number'] ?? '' }}</code></td>
                                <td>{{ $item->label ?? $item['label'] ?? '' }}</td>
                                <td><span class="catalog-badge catalog-badge-light">{{ $item->catalog_code ?? $item['catalog_code'] ?? '' }}</span></td>
                                <td class="text-center">{{ $item->begin_year ?? $item['begin_year'] ?? '' }}</td>
                                <td class="text-center">
                                    @if(($item->end_year ?? $item['end_year'] ?? 0) != 0)
                                        {{ $item->end_year ?? $item['end_year'] }}
                                    @else
                                        <span class="catalog-badge catalog-badge-success">@lang('compatibility.until_now')</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Cards --}}
        <div class="d-block d-md-none catalog-cards">
            @foreach($results as $item)
                <div class="catalog-card">
                    <div class="catalog-card-header">
                        <code class="fw-bold">{{ $item->part_number ?? $item['part_number'] ?? '' }}</code>
                        @if(($item->end_year ?? $item['end_year'] ?? 0) != 0)
                            <span class="catalog-badge catalog-badge-secondary">{{ $item->begin_year ?? $item['begin_year'] ?? '' }} - {{ $item->end_year ?? $item['end_year'] }}</span>
                        @else
                            <span class="catalog-badge catalog-badge-success">{{ $item->begin_year ?? $item['begin_year'] ?? '' }} - @lang('compatibility.until_now')</span>
                        @endif
                    </div>
                    <div class="catalog-card-body">
                        <div class="catalog-card-name">{{ $item->label ?? $item['label'] ?? '' }}</div>
                        <div class="catalog-card-details">
                            <div class="catalog-card-detail">
                                <span class="catalog-card-label">@lang('compatibility.catalog'):</span>
                                <span class="catalog-badge catalog-badge-light">{{ $item->catalog_code ?? $item['catalog_code'] ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="catalog-empty">
            <i class="fas fa-car"></i>
            <p>@lang('compatibility.no_results')</p>
        </div>
    @endif
</div>
