@extends('layouts.front')

@section('content')
<div class="gs-tracking-page py-5 muaadh-section-gray">
    <div class="container">
        <!-- Search Box -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8">
                <div class="tracking-search-box text-center">
                    <h2 class="mb-4">
                        <i class="fas fa-shipping-fast me-2"></i>
                        @lang('Track Your Shipment')
                    </h2>
                    <p class="text-muted mb-4">@lang('Enter your tracking number or purchase number to track your shipment')</p>

                    <form action="{{ route('front.tracking') }}" method="GET" class="tracking-form">
                        <div class="row g-3 justify-content-center">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                    <input type="text" name="tracking" class="form-control form-control-lg"
                                           placeholder="@lang('Tracking Number')"
                                           value="{{ $trackingNumber ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-1 d-flex align-items-center justify-content-center">
                                <span class="text-muted">@lang('OR')</span>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-receipt"></i></span>
                                    <input type="text" name="purchase" class="form-control form-control-lg"
                                           placeholder="@lang('Purchase Number')"
                                           value="{{ $orderNumber ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-search"></i> @lang('Track')
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if($shipment)
            <!-- Current Status -->
            <div class="row justify-content-center mb-4">
                <div class="col-lg-10">
                    <div class="card tracking-status-card shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-3">
                                        @php
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
                                            $icon = $statusIcons[$shipment->status] ?? 'fa-box';
                                            $color = $statusColors[$shipment->status] ?? 'info';
                                        @endphp
                                        <div class="status-icon bg-{{ $color }} text-white rounded-circle p-3 me-3">
                                            <i class="fas {{ $icon }} fa-2x"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-1 text-{{ $color }}">{{ $shipment->status_ar }}</h4>
                                            <p class="mb-0 text-muted">{{ $shipment->message_ar }}</p>
                                        </div>
                                    </div>

                                    <div class="tracking-details">
                                        <div class="row">
                                            <div class="col-6 col-md-3">
                                                <small class="text-muted">@lang('Tracking Number')</small>
                                                <p class="mb-0 fw-bold">{{ $shipment->tracking_number }}</p>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <small class="text-muted">@lang('Carrier')</small>
                                                <p class="mb-0 fw-bold">{{ $shipment->company_name ?? 'Tryoto' }}</p>
                                            </div>
                                            @if($shipment->location)
                                            <div class="col-6 col-md-3">
                                                <small class="text-muted">@lang('Location')</small>
                                                <p class="mb-0">{{ $shipment->location }}</p>
                                            </div>
                                            @endif
                                            <div class="col-6 col-md-3">
                                                <small class="text-muted">@lang('Last Update')</small>
                                                <p class="mb-0">{{ $shipment->status_date?->format('d/m/Y H:i') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if($purchase)
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <small class="text-muted d-block">@lang('Purchase Number')</small>
                                    <p class="fw-bold mb-2">{{ $purchase->purchase_number }}</p>
                                    @if(Auth::check() && Auth::id() == $purchase->user_id)
                                        <a href="{{ route('user-purchase', $purchase->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i> @lang('View Purchase')
                                        </a>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="row justify-content-center mb-4">
                <div class="col-lg-10">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            @php
                                $steps = ['created', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
                                $currentIndex = array_search($shipment->status, $steps);
                                if ($currentIndex === false) $currentIndex = 0;
                                $progress = (($currentIndex + 1) / count($steps)) * 100;
                            @endphp

                            <div class="tracking-progress mb-4">
                                <div class="progress muaadh-progress-medium">
                                    <div class="progress-bar bg-success" role="progressbar"
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
                                        <div class="step-icon {{ $isActive ? 'bg-success text-white' : 'bg-light' }} rounded-circle mx-auto mb-2 muaadh-step-icon">
                                            <i class="fas {{ $statusIcons[$step] ?? 'fa-box' }}"></i>
                                        </div>
                                        <small class="{{ $isActive ? 'fw-bold' : 'text-muted' }}">{{ $stepNames[$step] }}</small>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- History Timeline -->
            @if($history->count() > 0)
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>
                                @lang('Shipment History')
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                @foreach($history as $log)
                                    <div class="timeline-item {{ $loop->first ? 'active' : '' }}">
                                        <div class="timeline-marker bg-{{ $statusColors[$log->status] ?? 'info' }}"></div>
                                        <div class="timeline-content">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1 text-{{ $statusColors[$log->status] ?? 'info' }}">
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
                                                <small class="text-muted text-nowrap">
                                                    {{ $log->status_date?->format('d/m/Y') }}<br>
                                                    {{ $log->status_date?->format('H:i') }}
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

        @elseif($trackingNumber || $orderNumber)
            <!-- Not Found -->
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <h5>@lang('Shipment Not Found')</h5>
                        <p class="mb-0">@lang('Please check your tracking number or purchase number and try again.')</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<style>
.tracking-search-box {
    background: linear-gradient(135deg, var(--theme-bg-light, #faf8f5) 0%, var(--theme-bg-gray, #f5f2ec) 100%);
    padding: 40px;
    border-radius: 15px;
}

.tracking-status-card {
    border: none;
    border-radius: 15px;
}

.status-icon {
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
}

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
    border: 2px solid var(--color-surface, #fff);
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
@endsection
