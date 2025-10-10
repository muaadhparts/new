@if (isset($order))

    {{-- معلومات الطلب --}}
    <div class="order-info mb-4" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
        <h4 style="color: #EE1243; margin-bottom: 15px;">@lang('Order Information')</h4>
        <p><strong>@lang('Order Number'):</strong> {{ $order->order_number }}</p>
        <p><strong>@lang('Status'):</strong> <span class="badge bg-primary">{{ ucwords($order->status) }}</span></p>
        <p><strong>@lang('Date'):</strong> {{ date('d M Y', strtotime($order->created_at)) }}</p>
    </div>

    {{-- Shipment Tracking Timeline --}}
    @if(isset($shipmentLogs) && $shipmentLogs->isNotEmpty())
        <div class="shipment-tracking mb-5">
            <h4 style="color: #EE1243; margin-bottom: 20px;">@lang('Shipment Tracking')</h4>

            @php
                // Group logs by tracking number
                $groupedLogs = $shipmentLogs->groupBy('tracking_number');
            @endphp

            @foreach($groupedLogs as $trackingNumber => $logs)
                <div class="tracking-group mb-4" style="border: 2px solid #EE1243; border-radius: 10px; padding: 20px;">
                    <div class="tracking-header mb-3" style="border-bottom: 1px solid #ddd; padding-bottom: 10px;">
                        <h5 style="color: #EE1243; margin-bottom: 5px;">
                            <i class="fas fa-truck"></i> {{ $logs->first()->company_name ?? __('Shipping Company') }}
                        </h5>
                        <p style="margin-bottom: 5px;">
                            <strong>@lang('Tracking Number'):</strong>
                            <span style="font-size: 1.1em; color: #333; font-weight: 600;">{{ $trackingNumber }}</span>
                        </p>
                        @if($logs->first()->status)
                            <p style="margin-bottom: 0;">
                                <strong>@lang('Current Status'):</strong>
                                <span class="badge" style="background-color: {{ $logs->first()->status === 'delivered' ? '#28a745' : ($logs->first()->status === 'failed' ? '#dc3545' : '#007bff') }}; padding: 5px 10px;">
                                    {{ $logs->first()->status_ar ?? ucwords(str_replace('_', ' ', $logs->first()->status)) }}
                                </span>
                            </p>
                        @endif
                    </div>

                    <div class="wrapper">
                        <ul class="stepprogress" style="position: relative;">
                            @foreach($logs->reverse() as $index => $log)
                                <li class="stepprogress-item {{ $index === 0 ? 'is-done' : '' }} mb-3" style="position: relative; padding-left: 30px;">
                                    <div style="position: absolute; left: 0; top: 5px; width: 20px; height: 20px; background: {{ $log->status === 'delivered' ? '#28a745' : '#EE1243' }}; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px {{ $log->status === 'delivered' ? '#28a745' : '#EE1243' }};"></div>

                                    <strong class="fs-5 mb-2" style="color: #333;">
                                        {{ $log->status_ar ?? ucwords(str_replace('_', ' ', $log->status)) }}
                                    </strong>

                                    <div class="track-date" style="color: #666; font-size: 0.9em; margin: 5px 0;">
                                        <i class="far fa-calendar"></i> {{ date('d M Y - h:i A', strtotime($log->status_date ?? $log->created_at)) }}
                                    </div>

                                    @if($log->message_ar || $log->message)
                                        <div style="color: #555; margin-top: 5px;">
                                            {{ $log->message_ar ?? $log->message }}
                                        </div>
                                    @endif

                                    @if($log->location)
                                        <div style="color: #777; font-size: 0.9em; margin-top: 5px;">
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
        <h4 style="color: #EE1243; margin-bottom: 20px;">@lang('Order Timeline')</h4>
        <div class="wrapper">
            <ul class="stepprogress">
                @foreach ($order->tracks as $track)
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
        <i class="fas fa-search" style="font-size: 4em; color: #ddd; margin-bottom: 20px;"></i>
        <h3 style="color: #666;">{{ __('No Order Found.') }}</h3>
        <p style="color: #999;">{{ __('Please check your order number or tracking number and try again.') }}</p>
    </div>
@endif
              