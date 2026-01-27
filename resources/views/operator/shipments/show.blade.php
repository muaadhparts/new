@extends('layouts.operator')

@section('content')
<div class="content-area">
    <div class="mr-breadcrumb">
        <div class="row">
            <div class="col-lg-12">
                <h4 class="heading">
                    <a href="{{ route('operator.shipments.index') }}" class="btn btn-secondary btn-sm mr-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    {{ __('Shipment Details') }}
                </h4>
                <ul class="links">
                    <li><a href="{{ route('operator.dashboard') }}">{{ __('Dashboard') }}</a></li>
                    <li><a href="{{ route('operator.shipments.index') }}">{{ __('Shipments') }}</a></li>
                    <li><a href="javascript:;">{{ $shipment->tracking_number }}</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Current Status -->
            <div class="card mb-4">
                {{-- statusColor, statusIcon, progressPercent, stepsDisplay pre-computed in controller (DATA_FLOW_POLICY) --}}
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="bg-{{ $statusColor }} text-white rounded-circle p-3 mr-3 d-flex align-items-center justify-content-center"
                             style="width: 70px; height: 70px;">
                            <i class="fas {{ $statusIcon }} fa-2x"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 text-{{ $statusColor }}">{{ $shipment->status_ar }}</h4>
                            <p class="mb-0 text-muted">{{ $shipment->message_ar }}</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-{{ $statusColor }}" role="progressbar" style="width: {{ $progressPercent }}%"></div>
                    </div>

                    <div class="d-flex justify-content-between">
                        @foreach($stepsDisplay as $step)
                            <div class="text-center">
                                <div class="{{ $step['bgClass'] }} rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center"
                                     style="width: 35px; height: 35px;">
                                    <i class="fas {{ $step['icon'] }} small"></i>
                                </div>
                                <small class="{{ $step['textClass'] }}">{{ $step['name'] }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Shipment Info -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle mr-2"></i>{{ __('Shipment Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">{{ __('Tracking Number') }}</label>
                            <p class="font-weight-bold mb-0">
                                {{ $shipment->tracking_number }}
                                <button class="btn btn-sm btn-outline-secondary ml-2" onclick="copyToClipboard('{{ $shipment->tracking_number }}')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">{{ __('Shipment ID') }}</label>
                            <p class="font-weight-bold mb-0">{{ $shipment->shipment_id ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">{{ __('Shipping Company') }}</label>
                            <p class="font-weight-bold mb-0">{{ $shipment->company_name ?? 'Tryoto' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">{{ __('Last Update') }}</label>
                            <p class="font-weight-bold mb-0">{{ $status_date_formatted }}</p>
                        </div>
                        @if($shipment->location)
                        <div class="col-12">
                            <label class="text-muted small">{{ __('Current Location') }}</label>
                            <p class="font-weight-bold mb-0">
                                <i class="fas fa-map-marker-alt text-danger mr-1"></i>
                                {{ $shipment->location }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- History Timeline -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history mr-2"></i>{{ __('Shipment History') }}</h5>
                    <a href="{{ route('operator.shipments.refresh', $shipment->tracking_number) }}" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-sync-alt mr-1"></i> {{ __('Refresh') }}
                    </a>
                </div>
                <div class="card-body">
                    @if($history->count() > 0)
                        <div class="timeline">
                            @foreach($history as $log)
                                <div class="timeline-item {{ $loop->first ? 'active' : '' }}">
                                    <div class="timeline-marker bg-{{ $statusColors[$log->status] ?? 'info' }}"></div>
                                    <div class="timeline-content">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1 text-{{ $statusColors[$log->status] ?? 'info' }}">
                                                    <i class="fas {{ $statusIcons[$log->status] ?? 'fa-box' }} mr-1"></i>
                                                    {{ $log->status_ar }}
                                                </h6>
                                                <p class="mb-1">{{ $log->message_ar }}</p>
                                                @if($log->location)
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                                        {{ $log->location }}
                                                    </small>
                                                @endif
                                            </div>
                                            <div class="text-right">
                                                <small class="text-muted">
                                                    {{ $log->status_date_date_formatted }}<br>
                                                    <strong>{{ $log->status_date_time_formatted }}</strong>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ __('No history available') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Purchase Info -->
            @if($purchase)
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart mr-2"></i>{{ __('Purchase Information') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">{{ __('Purchase Number') }}</td>
                            <td class="text-right font-weight-bold">
                                <a href="{{ route('operator-purchase-show', $purchase->id) }}">
                                    {{ $purchase->purchase_number }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Customer') }}</td>
                            <td class="text-right">{{ $purchase->customer_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Phone') }}</td>
                            <td class="text-right">{{ $purchase->customer_phone ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Amount') }}</td>
                            <td class="text-right font-weight-bold text-success">
                                {{ $purchase->currency_sign }}{{ number_format($purchase->pay_amount, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Status') }}</td>
                            <td class="text-right">
                                <span class="badge badge-{{ $purchase->status == 'completed' ? 'success' : ($purchase->status == 'pending' ? 'warning' : 'info') }}">
                                    {{ ucfirst($purchase->status) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                    <hr>
                    <a href="{{ route('operator-purchase-show', $purchase->id) }}" class="btn btn-outline-primary btn-sm btn-block">
                        <i class="fas fa-eye mr-1"></i> {{ __('View Purchase') }}
                    </a>
                </div>
            </div>
            @endif

            <!-- Merchant Info -->
            @if($merchant)
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-store mr-2"></i>{{ __('Merchant Information') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">{{ __('Shop Name') }}</td>
                            <td class="text-right font-weight-bold">{{ $merchant->shop_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Owner') }}</td>
                            <td class="text-right">{{ $merchant->name }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Email') }}</td>
                            <td class="text-right">{{ $merchant->email }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">{{ __('Phone') }}</td>
                            <td class="text-right">{{ $merchant->phone ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            @endif

            <!-- Live Status -->
            @if($liveStatus && $liveStatus['success'])
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-satellite-dish mr-2 text-success"></i>
                        {{ __('Live Status') }}
                        <span class="badge badge-success ml-2">{{ __('Online') }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($liveStatus['current_status']))
                        <div class="alert alert-{{ $statusColors[$liveStatus['current_status']] ?? 'info' }} mb-0">
                            <strong>{{ $liveStatus['status_ar'] ?? $liveStatus['current_status'] }}</strong>
                            @if(isset($liveStatus['message']))
                                <p class="mb-0 small">{{ $liveStatus['message'] }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-bolt mr-2"></i>{{ __('Quick Actions') }}</h5>
                </div>
                <div class="card-body">
                    <a href="{{ route('operator.shipments.refresh', $shipment->tracking_number) }}" class="btn btn-info btn-block mb-2">
                        <i class="fas fa-sync-alt mr-1"></i> {{ __('Refresh Status') }}
                    </a>

                    @if(!in_array($shipment->status, ['delivered', 'cancelled', 'returned']))
                        <button type="button" class="btn btn-danger btn-block mb-2"
                                data-bs-toggle="modal" data-bs-target="#cancelModal">
                            <i class="fas fa-times mr-1"></i> {{ __('Cancel Shipment') }}
                        </button>
                    @endif

                    <a href="{{ route('front.tracking', ['tracking' => $shipment->tracking_number]) }}"
                       target="_blank" class="btn btn-outline-secondary btn-block mb-2">
                        <i class="fas fa-external-link-alt mr-1"></i> {{ __('Public Tracking Page') }}
                    </a>

                    <button class="btn btn-outline-primary btn-block" onclick="window.print()">
                        <i class="fas fa-print mr-1"></i> {{ __('Print Details') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('operator.shipments.cancel', $shipment->tracking_number) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-name">{{ __('Cancel Shipment') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        {{ __('This action cannot be undone!') }}
                    </div>
                    <p>{{ __('Are you sure you want to cancel this shipment?') }}</p>
                    <p><strong>{{ __('Tracking') }}: </strong>{{ $shipment->tracking_number }}</p>
                    <div class="form-group">
                        <label>{{ __('Cancellation Reason') }}</label>
                        <textarea name="reason" class="form-control" rows="3"
                                  placeholder="{{ __('Please provide a reason for cancellation') }}" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{ __('Close') }}
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times mr-1"></i> {{ __('Cancel Shipment') }}
                    </button>
                </div>
            </form>
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
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    padding-bottom: 25px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -25px;
    top: 0;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-item.active .timeline-marker {
    width: 16px;
    height: 16px;
    left: -27px;
}
</style>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('{{ __("Copied to clipboard!") }}');
    });
}
</script>
@endsection
