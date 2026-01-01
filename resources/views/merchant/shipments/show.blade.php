@extends('layouts.merchant')

@section('content')
<div class="gs-vendor-outlet">
    <!-- Breadcrumb -->
    <div class="gs-vendor-breadcrumb has-mb">
        <div class="gs-deposit-title d-flex align-items-center gap-4">
            <a href="{{ route('merchant.shipments.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> @lang('Back')
            </a>
            <h4 class="text-capitalize mb-0">@lang('Shipment Details')</h4>
        </div>
    </div>

    <div class="row g-4">
        <!-- Main Info -->
        <div class="col-lg-8">
            <!-- Current Status Card -->
            <div class="card mb-4">
                <div class="card-body">
                    @php
                        $statusColors = [
                            'created' => 'info',
                            'picked_up' => 'primary',
                            'in_transit' => 'warning',
                            'out_for_delivery' => 'warning',
                            'delivered' => 'success',
                            'failed' => 'danger',
                            'returned' => 'secondary',
                            'cancelled' => 'dark',
                        ];
                        $statusIcons = [
                            'created' => 'fa-box',
                            'picked_up' => 'fa-truck-loading',
                            'in_transit' => 'fa-truck',
                            'out_for_delivery' => 'fa-motorcycle',
                            'delivered' => 'fa-check-circle',
                            'failed' => 'fa-exclamation-circle',
                            'returned' => 'fa-undo',
                            'cancelled' => 'fa-times-circle',
                        ];
                        $color = $statusColors[$shipment->status] ?? 'info';
                        $icon = $statusIcons[$shipment->status] ?? 'fa-box';
                    @endphp

                    <div class="d-flex align-items-center mb-4">
                        <div class="status-icon bg-{{ $color }} text-white rounded-circle p-3 me-3"
                             style="width: 70px; height: 70px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas {{ $icon }} fa-2x"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 text-{{ $color }}">{{ $shipment->status_ar }}</h4>
                            <p class="mb-0 text-muted">{{ $shipment->message_ar }}</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    @php
                        $steps = ['created', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
                        $currentIndex = array_search($shipment->status, $steps);
                        if ($currentIndex === false) $currentIndex = 0;
                        $progress = (($currentIndex + 1) / count($steps)) * 100;
                    @endphp

                    <div class="tracking-progress mb-4">
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-{{ $color }}" role="progressbar"
                                 style="width: {{ $progress }}%"></div>
                        </div>
                    </div>

                    <div class="tracking-steps d-flex justify-content-between">
                        @foreach($steps as $index => $step)
                            @php
                                $isActive = $index <= $currentIndex;
                                $stepNames = [
                                    'created' => __('Created'),
                                    'picked_up' => __('Picked Up'),
                                    'in_transit' => __('In Transit'),
                                    'out_for_delivery' => __('Out for Delivery'),
                                    'delivered' => __('Delivered'),
                                ];
                            @endphp
                            <div class="tracking-step text-center {{ $isActive ? 'active' : '' }}">
                                <div class="step-icon {{ $isActive ? 'bg-'.$color.' text-white' : 'bg-light' }} rounded-circle mx-auto mb-2"
                                     style="width: 40px; height: 40px; line-height: 40px;">
                                    <i class="fas {{ $statusIcons[$step] ?? 'fa-box' }}"></i>
                                </div>
                                <small class="{{ $isActive ? 'fw-bold' : 'text-muted' }}">{{ $stepNames[$step] }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Shipment Info -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>@lang('Shipment Information')</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small">@lang('Tracking Number')</label>
                            <p class="fw-bold mb-0">
                                {{ $shipment->tracking_number }}
                                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="copyToClipboard('{{ $shipment->tracking_number }}')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">@lang('Shipment ID')</label>
                            <p class="fw-bold mb-0">{{ $shipment->shipment_id ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">@lang('Shipping Company')</label>
                            <p class="fw-bold mb-0">{{ $shipment->company_name ?? 'Tryoto' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">@lang('Last Update')</label>
                            <p class="fw-bold mb-0">{{ $shipment->status_date?->format('d/m/Y H:i') ?? 'N/A' }}</p>
                        </div>
                        @if($shipment->location)
                        <div class="col-12">
                            <label class="text-muted small">@lang('Current Location')</label>
                            <p class="fw-bold mb-0">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                {{ $shipment->location }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Timeline History -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>@lang('Shipment History')</h5>
                    <a href="{{ route('merchant.shipments.refresh', $shipment->tracking_number) }}" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-sync-alt me-1"></i> @lang('Refresh')
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
                                                    <i class="fas {{ $statusIcons[$log->status] ?? 'fa-box' }} me-1"></i>
                                                    {{ $log->status_ar }}
                                                </h6>
                                                <p class="mb-1">{{ $log->message_ar }}</p>
                                                @if($log->location)
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        {{ $log->location }}
                                                    </small>
                                                @endif
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted">
                                                    {{ $log->status_date?->format('d/m/Y') }}<br>
                                                    <strong>{{ $log->status_date?->format('H:i') }}</strong>
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
                            <p class="text-muted">@lang('No history available')</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Order Info -->
            @if($purchase)
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>@lang('Order Information')</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">@lang('Order Number')</td>
                            <td class="text-end fw-bold">
                                <a href="{{ route('merchant-purchase-show', $purchase->purchase_number) }}">
                                    {{ $purchase->purchase_number }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">@lang('Customer')</td>
                            <td class="text-end">{{ $purchase->customer_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">@lang('Phone')</td>
                            <td class="text-end">{{ $purchase->customer_phone ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">@lang('Amount')</td>
                            <td class="text-end fw-bold text-success">
                                {{ $purchase->currency_sign }}{{ number_format($purchase->pay_amount, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">@lang('Order Status')</td>
                            <td class="text-end">
                                <span class="badge bg-{{ $purchase->status == 'completed' ? 'success' : ($purchase->status == 'pending' ? 'warning' : 'info') }}">
                                    {{ ucfirst($purchase->status) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                    <hr>
                    <a href="{{ route('merchant-purchase-show', $purchase->purchase_number) }}" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-eye me-1"></i> @lang('View Order Details')
                    </a>
                </div>
            </div>
            @endif

            <!-- Live Status -->
            @if($liveStatus && $liveStatus['success'])
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-satellite-dish me-2 text-success"></i>
                        @lang('Live Status')
                        <span class="badge bg-success ms-2">@lang('Online')</span>
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
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>@lang('Quick Actions')</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('merchant.shipments.refresh', $shipment->tracking_number) }}" class="btn btn-info">
                            <i class="fas fa-sync-alt me-1"></i> @lang('Refresh Status')
                        </a>

                        @if(!in_array($shipment->status, ['delivered', 'cancelled', 'returned']))
                            <button type="button" class="btn btn-danger"
                                    data-bs-toggle="modal" data-bs-target="#cancelModal">
                                <i class="fas fa-times me-1"></i> @lang('Cancel Shipment')
                            </button>
                        @endif

                        <a href="{{ route('front.tracking', ['tracking' => $shipment->tracking_number]) }}"
                           target="_blank" class="btn btn-outline-secondary">
                            <i class="fas fa-external-link-alt me-1"></i> @lang('Public Tracking Page')
                        </a>

                        <button class="btn btn-outline-primary" onclick="printShipmentDetails()">
                            <i class="fas fa-print me-1"></i> @lang('Print Details')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('merchant.shipments.cancel', $shipment->tracking_number) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Cancel Shipment')</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        @lang('This action cannot be undone!')
                    </div>
                    <p>@lang('Are you sure you want to cancel this shipment?')</p>
                    <p><strong>@lang('Tracking'): </strong>{{ $shipment->tracking_number }}</p>
                    <div class="mb-3">
                        <label class="form-label">@lang('Cancellation Reason')</label>
                        <textarea name="reason" class="form-control" rows="3"
                                  placeholder="@lang('Please provide a reason for cancellation')" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        @lang('Close')
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i> @lang('Cancel Shipment')
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
    background: var(--theme-border, #d4c4a8);
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
    box-shadow: 0 0 0 2px var(--theme-border, #d4c4a8);
}

.timeline-item.active .timeline-marker {
    width: 16px;
    height: 16px;
    left: -27px;
}

.tracking-step.active .step-icon {
    box-shadow: 0 0 0 4px var(--theme-success-light, rgba(16, 185, 129, 0.2));
}
</style>

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        toastr.success('{{ __("Copied to clipboard!") }}');
    });
}

function printShipmentDetails() {
    window.print();
}
</script>
@endpush
@endsection
