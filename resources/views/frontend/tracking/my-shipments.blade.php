@extends('layouts.front')

@section('content')
<div class="gs-myshipments-page py-5 muaadh-section-gray">
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
                {{-- statusColor, statusIcon, progressPercent pre-computed in TrackingDataBuilder (DATA_FLOW_POLICY) --}}
                @foreach($shipments as $shipment)
                    <div class="col-lg-6">
                        <div class="card shipment-card shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="status-icon bg-{{ $shipment['statusColor'] }} text-white rounded-circle p-2 me-3 muaadh-shipment-status-icon">
                                            <i class="fas {{ $shipment['statusIcon'] }} fa-lg"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-1 text-{{ $shipment['statusColor'] }}">{{ $shipment['status_ar'] }}</h5>
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
                                        <small class="text-muted">@lang('Purchase Number')</small>
                                        <p class="fw-bold mb-0">
                                            <a href="{{ route('user-purchase', $shipment['purchase_id']) }}"
                                               class="text-decoration-none">
                                                {{ $shipment['purchase_number'] }}
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
                                <div class="progress mb-3 muaadh-progress-thin">
                                    <div class="progress-bar bg-{{ $shipment['statusColor'] }}" role="progressbar"
                                         style="width: {{ $shipment['progressPercent'] }}%"></div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('front.tracking', ['tracking' => $shipment['tracking_number']]) }}"
                                       class="btn btn-sm btn-outline-{{ $shipment['statusColor'] }}">
                                        <i class="fas fa-eye me-1"></i> @lang('Track Details')
                                    </a>
                                    <a href="{{ route('user-purchase', $shipment['purchase_id']) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-receipt me-1"></i> @lang('View Purchase')
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
                            @lang('You don\'t have any shipments to track yet. Once your purchases are shipped, they will appear here.')
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

{{-- Styles moved to MUAADH.css: .shipment-card, .status-icon --}}
@endsection
