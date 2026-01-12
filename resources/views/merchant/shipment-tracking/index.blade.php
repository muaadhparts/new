@extends('layouts.merchant')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">@lang('Shipment Tracking')</h4>
                </div>
                <div class="card-body">

                    {{-- Stats Cards --}}
                    <div class="row mb-4">
                        <div class="col-md-2 col-6">
                            <div class="card bg-primary text-white text-center p-3">
                                <h3 class="mb-0">{{ $stats['total'] ?? 0 }}</h3>
                                <small>@lang('Total')</small>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="card bg-info text-white text-center p-3">
                                <h3 class="mb-0">{{ $stats['created'] ?? 0 }}</h3>
                                <small>@lang('Pending')</small>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="card bg-warning text-white text-center p-3">
                                <h3 class="mb-0">{{ $stats['in_transit'] ?? 0 }}</h3>
                                <small>@lang('In Transit')</small>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="card bg-success text-white text-center p-3">
                                <h3 class="mb-0">{{ $stats['delivered'] ?? 0 }}</h3>
                                <small>@lang('Delivered')</small>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="card bg-danger text-white text-center p-3">
                                <h3 class="mb-0">{{ $stats['failed'] ?? 0 }}</h3>
                                <small>@lang('Failed')</small>
                            </div>
                        </div>
                        <div class="col-md-2 col-6">
                            <div class="card bg-secondary text-white text-center p-3">
                                <h3 class="mb-0">{{ $stats['returned'] ?? 0 }}</h3>
                                <small>@lang('Returned')</small>
                            </div>
                        </div>
                    </div>

                    {{-- Filters --}}
                    <form method="GET" class="row mb-4">
                        <div class="col-md-3">
                            <select name="status" class="form-control">
                                <option value="">@lang('All Statuses')</option>
                                @foreach(\App\Models\ShipmentTracking::getAllStatuses() as $status)
                                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                        {{ \App\Models\ShipmentTracking::getStatusTranslation($status) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="type" class="form-control">
                                <option value="">@lang('All Types')</option>
                                <option value="api" {{ request('type') == 'api' ? 'selected' : '' }}>@lang('API (External)')</option>
                                <option value="manual" {{ request('type') == 'manual' ? 'selected' : '' }}>@lang('Manual')</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control"
                                   placeholder="@lang('Search by tracking or purchase number')"
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> @lang('Search')
                            </button>
                        </div>
                    </form>

                    {{-- Shipments Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>@lang('Purchase')</th>
                                    <th>@lang('Tracking #')</th>
                                    <th>@lang('Company')</th>
                                    <th>@lang('Type')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Location')</th>
                                    <th>@lang('Last Update')</th>
                                    <th>@lang('Actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shipments as $shipment)
                                    <tr>
                                        <td>
                                            <strong>{{ $shipment->purchase->purchase_number ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $shipment->purchase->customer_name ?? '' }}</small>
                                        </td>
                                        <td>
                                            @if($shipment->tracking_number)
                                                <code>{{ $shipment->tracking_number }}</code>
                                            @else
                                                <span class="text-muted">@lang('Not assigned')</span>
                                            @endif
                                        </td>
                                        <td>{{ $shipment->company_name ?? $shipment->provider }}</td>
                                        <td>
                                            @if($shipment->integration_type === 'api')
                                                <span class="badge badge-info">API</span>
                                            @else
                                                <span class="badge badge-secondary">@lang('Manual')</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $shipment->status_color }}">
                                                <i class="{{ $shipment->status_icon }}"></i>
                                                {{ $shipment->status_ar }}
                                            </span>
                                        </td>
                                        <td>{{ $shipment->location ?? '-' }}</td>
                                        <td>
                                            {{ $shipment->occurred_at ? $shipment->occurred_at->diffForHumans() : '-' }}
                                        </td>
                                        <td>
                                            <a href="{{ route('merchant.shipment-tracking.show', $shipment->purchase_id) }}"
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($shipment->integration_type === 'api' && $shipment->tracking_number)
                                                <button type="button" class="btn btn-sm btn-info refresh-btn"
                                                        data-purchase-id="{{ $shipment->purchase_id }}">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">@lang('No shipments found')</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-center mt-4">
                        {{ $shipments->withQueryString()->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Refresh from API
    $('.refresh-btn').click(function() {
        var btn = $(this);
        var purchaseId = btn.data('purchase-id');

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.get('{{ url("merchant/shipment-tracking") }}/' + purchaseId + '/refresh')
            .done(function(response) {
                if (response.success) {
                    toastr.success('@lang("Status updated")');
                    location.reload();
                } else {
                    toastr.error(response.error || '@lang("Failed to refresh")');
                }
            })
            .fail(function() {
                toastr.error('@lang("Connection error")');
            })
            .always(function() {
                btn.prop('disabled', false).html('<i class="fas fa-sync"></i>');
            });
    });
});
</script>
@endpush
@endsection
