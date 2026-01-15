{{-- resources/views/partials/api/compatibility-tabs.blade.php --}}
{{-- API-based compatibility tabs partial (No Livewire) --}}
{{-- Uses catalog-unified.css for styling --}}

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
                            @php
                                $row = is_array($item) ? (object) $item : $item;
                            @endphp
                            <tr>
                                <td><code class="fw-bold text-dark">{{ $row->part_number ?? '' }}</code></td>
                                <td>{{ $row->label ?? '' }}</td>
                                <td><span class="catalog-badge catalog-badge-light">{{ $row->catalog_code ?? '' }}</span></td>
                                <td class="text-center">{{ $row->begin_year ?? '' }}</td>
                                <td class="text-center">
                                    @if(($row->end_year ?? 0) != 0)
                                        {{ $row->end_year }}
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
                @php
                    $row = is_array($item) ? (object) $item : $item;
                @endphp
                <div class="catalog-card">
                    <div class="catalog-card-header">
                        <code class="fw-bold">{{ $row->part_number ?? '' }}</code>
                        @if(($row->end_year ?? 0) != 0)
                            <span class="catalog-badge catalog-badge-secondary">{{ $row->begin_year ?? '' }} - {{ $row->end_year }}</span>
                        @else
                            <span class="catalog-badge catalog-badge-success">{{ $row->begin_year ?? '' }} - @lang('compatibility.until_now')</span>
                        @endif
                    </div>
                    <div class="catalog-card-body">
                        <div class="catalog-card-name">{{ $row->label ?? '' }}</div>
                        <div class="catalog-card-details">
                            <div class="catalog-card-detail">
                                <span class="catalog-card-label">@lang('compatibility.catalog'):</span>
                                <span class="catalog-badge catalog-badge-light">{{ $row->catalog_code ?? '' }}</span>
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
