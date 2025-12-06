{{-- resources/views/partials/api/compatibility-tabs.blade.php --}}
{{-- API-based compatibility tabs partial (No Livewire) --}}

<div class="modal-content-wrapper">
    @if($results && count($results))
        {{-- Header --}}
        <div class="modal-section-header">
            <h5 class="mb-0 fw-bold text-primary">
                <i class="fas fa-car me-2"></i>
                @lang('labels.fits')
            </h5>
            <span class="badge bg-secondary">{{ count($results) }} @lang('items')</span>
        </div>

        {{-- Desktop Table --}}
        <div class="d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 modal-table">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">@lang('compatibility.part_number')</th>
                            <th>@lang('compatibility.vehicle_name')</th>
                            <th class="text-nowrap">@lang('compatibility.catalog_code')</th>
                            <th class="text-center text-nowrap">@lang('compatibility.from_year')</th>
                            <th class="text-center text-nowrap">@lang('compatibility.to_year')</th>
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
                                <td><span class="badge bg-light text-dark border">{{ $row->catalog_code ?? '' }}</span></td>
                                <td class="text-center">{{ $row->begin_year ?? '' }}</td>
                                <td class="text-center">
                                    @if(($row->end_year ?? 0) != 0)
                                        {{ $row->end_year }}
                                    @else
                                        <span class="badge bg-success">@lang('compatibility.until_now')</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile Cards --}}
        <div class="d-block d-md-none modal-cards">
            @foreach($results as $item)
                @php
                    $row = is_array($item) ? (object) $item : $item;
                @endphp
                <div class="modal-card">
                    <div class="modal-card-header">
                        <code class="fw-bold">{{ $row->part_number ?? '' }}</code>
                        @if(($row->end_year ?? 0) != 0)
                            <span class="badge bg-secondary">{{ $row->begin_year ?? '' }} - {{ $row->end_year }}</span>
                        @else
                            <span class="badge bg-success">{{ $row->begin_year ?? '' }} - @lang('compatibility.until_now')</span>
                        @endif
                    </div>
                    <div class="modal-card-body">
                        <div class="modal-card-title">{{ $row->label ?? '' }}</div>
                        <div class="modal-card-details">
                            <div class="modal-detail">
                                <span class="modal-label">@lang('compatibility.catalog'):</span>
                                <span class="badge bg-light text-dark border">{{ $row->catalog_code ?? '' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="modal-empty">
            <i class="fas fa-car fa-3x text-muted mb-3"></i>
            <p class="text-muted mb-0">@lang('compatibility.no_results')</p>
        </div>
    @endif
</div>

<style>
/* ========== Unified Modal Styles ========== */
.modal-content-wrapper {
    max-height: 70vh;
    overflow-y: auto;
    padding: 0;
}

.modal-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 0;
    position: sticky;
    top: 0;
    z-index: 10;
}

/* Desktop Table */
.modal-table {
    font-size: 0.9rem;
    margin-bottom: 0;
}

.modal-table th {
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #555;
    border-bottom: 2px solid #dee2e6;
    padding: 10px 12px;
    background: #f8f9fa;
}

.modal-table td {
    padding: 10px 12px;
    vertical-align: middle;
}

.modal-table tbody tr {
    transition: background-color 0.15s ease;
}

.modal-table tbody tr:hover {
    background-color: #f8f9fa;
}

/* Mobile Cards */
.modal-cards {
    padding: 10px;
}

.modal-card {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 10px;
    overflow: hidden;
    transition: box-shadow 0.15s ease;
}

.modal-card:active {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.modal-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.modal-card-body {
    padding: 12px;
}

.modal-card-title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 8px;
    color: #333;
}

.modal-card-details {
    font-size: 0.85rem;
}

.modal-detail {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    border-bottom: 1px dashed #eee;
}

.modal-detail:last-child {
    border-bottom: none;
}

.modal-label {
    color: #666;
    font-weight: 500;
}

/* Empty State */
.modal-empty {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

/* Scrollbar */
.modal-content-wrapper::-webkit-scrollbar {
    width: 6px;
}

.modal-content-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.modal-content-wrapper::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.modal-content-wrapper::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* RTL Support */
[dir="rtl"] .modal-detail {
    flex-direction: row-reverse;
}

[dir="rtl"] .modal-section-header {
    flex-direction: row-reverse;
}
</style>
