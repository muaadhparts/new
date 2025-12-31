@if (isset($order))

    {{-- معلومات الطلب --}}
    <div class="track-order-info mb-4">
        <h4>@lang('Order Information')</h4>
        <p><strong>@lang('Order Number'):</strong> {{ $order->purchase_number }}</p>
        <p><strong>@lang('Status'):</strong> <span class="badge bg-primary">{{ ucwords($order->status) }}</span></p>
        <p><strong>@lang('Date'):</strong> {{ date('d M Y', strtotime($order->created_at)) }}</p>
    </div>

    {{-- Shipment Tracking Timeline --}}
    @if(isset($shipmentLogs) && $shipmentLogs->isNotEmpty())
        <div class="shipment-tracking mb-5">
            <h4 class="track-section-title mb-4">@lang('Shipment Tracking')</h4>

            @php
                // Group logs by tracking number
                $groupedLogs = $shipmentLogs->groupBy('tracking_number');
            @endphp

            @foreach($groupedLogs as $trackingNumber => $logs)
                <div class="track-tracking-group mb-4">
                    <div class="track-tracking-header mb-3">
                        <h5>
                            <i class="fas fa-truck"></i> {{ $logs->first()->company_name ?? __('Shipping Company') }}
                        </h5>
                        <p class="mb-1">
                            <strong>@lang('Tracking Number'):</strong>
                            <span class="track-tracking-number">{{ $trackingNumber }}</span>
                        </p>
                        @if($logs->first()->status)
                            <p class="mb-0">
                                <strong>@lang('Current Status'):</strong>
                                <span class="badge track-status-badge {{ $logs->first()->status === 'delivered' ? 'delivered' : ($logs->first()->status === 'failed' ? 'failed' : 'in-progress') }}">
                                    {{ $logs->first()->status_ar ?? ucwords(str_replace('_', ' ', $logs->first()->status)) }}
                                </span>
                            </p>
                        @endif
                    </div>

                    <div class="wrapper">
                        <ul class="stepprogress">
                            @foreach($logs->reverse() as $index => $log)
                                <li class="stepprogress-item {{ $index === 0 ? 'is-done' : '' }} mb-3 track-timeline-item">
                                    <div class="track-timeline-dot {{ $log->status === 'delivered' ? 'success' : 'primary' }}"></div>

                                    <strong class="fs-5 mb-2 track-timeline-title">
                                        {{ $log->status_ar ?? ucwords(str_replace('_', ' ', $log->status)) }}
                                    </strong>

                                    <div class="track-timeline-date">
                                        <i class="far fa-calendar"></i> {{ date('d M Y - h:i A', strtotime($log->status_date ?? $log->created_at)) }}
                                    </div>

                                    @if($log->message_ar || $log->message)
                                        <div class="track-timeline-message">
                                            {{ $log->message_ar ?? $log->message }}
                                        </div>
                                    @endif

                                    @if($log->location)
                                        <div class="track-timeline-location">
                                            <i class="fas fa-map-marker-alt"></i> {{ $log->location }}
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Order Timeline --}}
    <div class="order-timeline">
        <h4 class="track-section-title mb-4">@lang('Order Timeline')</h4>
        <div class="wrapper">
            <ul class="stepprogress">
                @foreach ($order->timelines as $track)
                    <li class="stepprogress-item is-done mb-3">
                        <strong class="fs-5 mb-2">{{ ucwords($track->title) }}</strong>
                        <div class="track-date">{{ date('d M Y', strtotime($track->created_at)) }}</div>
                        {{ $track->text }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

@else
    <div class="text-center py-5">
        <i class="fas fa-search track-empty-icon"></i>
        <h3 class="track-empty-title">{{ __('No Order Found.') }}</h3>
        <p class="track-empty-text">{{ __('Please check your order number or tracking number and try again.') }}</p>
    </div>
@endif
