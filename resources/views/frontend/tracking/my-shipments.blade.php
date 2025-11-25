@extends('layouts.front')

@section('content')
<div class="gs-myshipments-page py-5">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fas fa-shipping-fast me-2"></i>
                        @lang('My Shipments')
                    </h2>
                    <a href="{{ route('front.tracking') }}" class="btn btn-outline-primary">
                        <i class="fas fa-search me-1"></i> @lang('Track Another Shipment')
                    </a>
                </div>
            </div>
        </div>

        @if(count($shipments) > 0)
            <div class="row g-4">
                @foreach($shipments as $shipment)
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
                        $color = $statusColors[$shipment['status']] ?? 'info';
                        $icon = $statusIcons[$shipment['status']] ?? 'fa-box';
                    @endphp
                    <div class="col-lg-6">
                        <div class="card shipment-card shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="status-icon bg-{{ $color }} text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center"
                                             style="width: 50px; height: 50px;">
                                            <i class="fas {{ $icon }} fa-lg"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1 text-{{ $color }}">{{ $shipment['status_ar'] }}</h5>
                                            <p class="mb-0 text-muted small">
                                                <i class="far fa-clock me-1"></i>
                                                {{ $shipment['date']?->format('d/m/Y H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted">@lang('Tracking Number')</small>
                                        <p class="fw-bold mb-0">
                                            <a href="{{ route('front.tracking', ['tracking' => $shipment['tracking_number']]) }}"
                                               class="text-decoration-none">
                                                {{ $shipment['tracking_number'] }}
                                            </a>
                                        </p>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">@lang('Order Number')</small>
                                        <p class="fw-bold mb-0">
                                            <a href="{{ route('user-order', $shipment['order_id']) }}"
                                               class="text-decoration-none">
                                                {{ $shipment['order_number'] }}
                                            </a>
                                        </p>
                                    </div>
                                </div>

                                @if($shipment['company'])
                                    <div class="d-flex align-items-center text-muted small mb-3">
                                        <i class="fas fa-truck me-2"></i>
                                        <span>{{ $shipment['company'] }}</span>
                                    </div>
                                @endif

                                <!-- Mini Progress -->
                                @php
                                    $steps = ['created', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered'];
                                    $currentIndex = array_search($shipment['status'], $steps);
                                    if ($currentIndex === false) $currentIndex = 0;
                                    $progress = (($currentIndex + 1) / count($steps)) * 100;
                                @endphp
                                <div class="progress mb-3" style="height: 5px;">
                                    <div class="progress-bar bg-{{ $color }}" role="progressbar"
                                         style="width: {{ $progress }}%"></div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('front.tracking', ['tracking' => $shipment['tracking_number']]) }}"
                                       class="btn btn-sm btn-outline-{{ $color }}">
                                        <i class="fas fa-eye me-1"></i> @lang('Track Details')
                                    </a>
                                    <a href="{{ route('user-order', $shipment['order_id']) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-receipt me-1"></i> @lang('View Order')
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-4x text-muted mb-4"></i>
                        <h4>@lang('No Shipments Yet')</h4>
                        <p class="text-muted mb-4">
                            @lang('You don\'t have any shipments to track yet. Once your orders are shipped, they will appear here.')
                        </p>
                        <div class="d-flex gap-3 justify-content-center">
                            <a href="{{ route('front.tracking') }}" class="btn btn-primary">
                                <i class="fas fa-search me-1"></i> @lang('Track a Shipment')
                            </a>
                            <a href="{{ route('front.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-shopping-cart me-1"></i> @lang('Continue Shopping')
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<style>
.shipment-card {
    border: none;
    border-radius: 12px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.shipment-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}

.status-icon {
    flex-shrink: 0;
}
</style>
@endsection
