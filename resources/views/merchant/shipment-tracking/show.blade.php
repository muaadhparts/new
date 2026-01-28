@extends('layouts.merchant')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            {{-- Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>
                    @lang('Shipment Details')
                    @if($shipmentInfo['current']['tracking_number'])
                        - <code>{{ $shipmentInfo['current']['tracking_number'] }}</code>
                    @endif
                </h4>
                <a href="{{ route('merchant.shipment-tracking.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> @lang('Back')
                </a>
            </div>

            <div class="row">
                {{-- Shipment Info --}}
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">@lang('Shipment Info')</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th>@lang('Purchase'):</th>
                                    <td>{{ $purchase->purchase_number ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('Customer'):</th>
                                    <td>{{ $purchase->customer_name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('City'):</th>
                                    <td>{{ $purchase->customer_city ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('Company'):</th>
                                    <td>{{ $shipmentInfo['current']['company_name'] }}</td>
                                </tr>
                                <tr>
                                    <th>@lang('Type'):</th>
                                    <td>
                                        @if($shipmentInfo['current']['integration_type'] === 'api')
                                            <span class="badge badge-info">API</span>
                                        @else
                                            <span class="badge badge-secondary">@lang('Manual')</span>
                                        @endif
                                    </td>
                                </tr>
                                @if($shipmentInfo['current']['shipping_cost'])
                                <tr>
                                    <th>@lang('Shipping Cost'):</th>
                                    <td>{{ monetaryUnit()->format($shipmentInfo['current']['shipping_cost']) }}</td>
                                </tr>
                                @endif
                                @if($shipmentInfo['current']['cod_amount'])
                                <tr>
                                    <th>@lang('COD Amount'):</th>
                                    <td>{{ monetaryUnit()->format($shipmentInfo['current']['cod_amount']) }}</td>
                                </tr>
                                @endif
                                @if($shipmentInfo['current']['awb_url'])
                                <tr>
                                    <th>@lang('AWB'):</th>
                                    <td>
                                        <a href="{{ $shipmentInfo['current']['awb_url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-file-pdf"></i> @lang('Download')
                                        </a>
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    {{-- Current Status --}}
                    <div class="card mt-3">
                        <div class="card-header bg-{{ $shipmentInfo['current']['status_color'] }} text-white">
                            <h5 class="mb-0">
                                <i class="{{ $shipmentInfo['current']['status_icon'] }}"></i>
                                {{ $shipmentInfo['current']['status_ar'] }}
                            </h5>
                        </div>
                        <div class="card-body">
                            {{-- Progress Bar --}}
                            <div class="progress mb-3" style="height: 10px;">
                                <div class="progress-bar bg-{{ $shipmentInfo['current']['status_color'] }}"
                                     style="width: {{ $shipmentInfo['current']['progress_percent'] }}%"></div>
                            </div>
                            <p class="text-center mb-0">
                                {{ $shipmentInfo['current']['progress_percent'] }}% @lang('Complete')
                            </p>
                            @if($shipmentInfo['current']['location'])
                                <p class="text-muted text-center mt-2 mb-0">
                                    <i class="fas fa-map-marker-alt"></i> {{ $shipmentInfo['current']['location'] }}
                                </p>
                            @endif
                        </div>
                    </div>

                    {{-- Manual Update Form --}}
                    @if($shipmentInfo['can_update'])
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">@lang('Update Status')</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('merchant.shipment-tracking.update', $purchase->id) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="form-group">
                                    <label>@lang('New Status')</label>
                                    <select name="status" class="form-control" required>
                                        <option value="">@lang('Select...')</option>
                                        @foreach($manualUpdateStatuses as $statusOption)
                                            <option value="{{ $statusOption['value'] }}">
                                                {{ $statusOption['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>@lang('Location') (@lang('Optional'))</label>
                                    <input type="text" name="location" class="form-control"
                                           placeholder="@lang('Current location')">
                                </div>
                                <div class="form-group">
                                    <label>@lang('Note') (@lang('Optional'))</label>
                                    <textarea name="message" class="form-control" rows="2"
                                              placeholder="@lang('Additional notes')"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save"></i> @lang('Update')
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Timeline --}}
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">@lang('Tracking Timeline')</h5>
                            @if($shipmentInfo['current']['integration_type'] === 'api')
                                <button type="button" class="btn btn-sm btn-info" id="refreshBtn">
                                    <i class="fas fa-sync"></i> @lang('Refresh')
                                </button>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                @foreach($shipmentInfo['history'] as $event)
                                    <div class="timeline-item">
                                        <div class="timeline-marker bg-{{ $event['status_color'] }}">
                                            <i class="{{ $event['status_icon'] }}"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <h6 class="mb-1">
                                                <span class="badge badge-{{ $event['status_color'] }}">
                                                    {{ $event['status_ar'] }}
                                                </span>
                                            </h6>
                                            @if($event['message_ar'] ?? $event['message'])
                                                <p class="mb-1">{{ $event['message_ar'] ?? $event['message'] }}</p>
                                            @endif
                                            @if($event['location'])
                                                <p class="mb-1 text-muted">
                                                    <i class="fas fa-map-marker-alt"></i> {{ $event['location'] }}
                                                </p>
                                            @endif
                                            <small class="text-muted">
                                                <i class="far fa-clock"></i>
                                                {{ $event['occurred_at_formatted'] }}
                                                @if($event['source'] !== 'system')
                                                    <span class="badge badge-light">{{ $event['source'] }}</span>
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}
.timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--border-default, #dee2e6);
}
.timeline-item {
    position: relative;
    padding-bottom: 20px;
}
.timeline-marker {
    position: absolute;
    left: -30px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 10px;
}
.timeline-content {
    padding: 10px 15px;
    background: var(--bg-secondary, #f8f9fa);
    border-radius: 5px;
}
</style>

@push('scripts')
<script>
$(document).ready(function() {
    $('#refreshBtn').click(function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> @lang("Refreshing")...');

        $.get('{{ route("merchant.shipment-tracking.refresh", $purchase->id) }}')
            .done(function(response) {
                if (response.success) {
                    toastr.success('@lang("Status updated")');
                    location.reload();
                } else {
                    toastr.error(response.error || '@lang("Failed to refresh")');
                    btn.prop('disabled', false).html('<i class="fas fa-sync"></i> @lang("Refresh")');
                }
            })
            .fail(function() {
                toastr.error('@lang("Connection error")');
                btn.prop('disabled', false).html('<i class="fas fa-sync"></i> @lang("Refresh")');
            });
    });
});
</script>
@endpush
@endsection
