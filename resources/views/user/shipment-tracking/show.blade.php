@extends('layouts.user')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">
            @lang('Shipment Tracking')
            <span class="text-muted">- {{ $purchase->purchase_number }}</span>
        </h4>
        <a href="{{ route('user.shipment-tracking.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> @lang('Back')
        </a>
    </div>
    <div class="card-body">
        {{-- Purchase Info --}}
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">@lang('Purchase Info')</h6>
                        <table class="table table-sm mb-0">
                            <tr>
                                <th>@lang('Purchase Number'):</th>
                                <td>{{ $purchase->purchase_number }}</td>
                            </tr>
                            <tr>
                                <th>@lang('Date'):</th>
                                <td>{{ $purchase->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                            <tr>
                                <th>@lang('Total'):</th>
                                <td>{{ $purchase->currency_sign }}{{ number_format($purchase->pay_amount, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tracking per Merchant --}}
        @foreach($trackings as $tracking)
            <div class="card mb-4">
                <div class="card-header bg-{{ $tracking->status_color }} text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="{{ $tracking->status_icon }}"></i>
                            <strong>{{ $tracking->merchant->shop_name ?? $tracking->merchant->name ?? 'N/A' }}</strong>
                            -
                            {{ $tracking->status_ar }}
                        </div>
                        @if($tracking->tracking_number)
                            <code class="text-white bg-dark px-2 py-1 rounded">{{ $tracking->tracking_number }}</code>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    {{-- Progress Bar --}}
                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar bg-{{ $tracking->status_color }}"
                             style="width: {{ $tracking->progress_percent }}%"></div>
                    </div>

                    {{-- Shipping Company --}}
                    @if($tracking->company_name)
                        <p class="mb-3">
                            <i class="fas fa-truck"></i>
                            @lang('Shipping Company'): <strong>{{ $tracking->company_name }}</strong>
                        </p>
                    @endif

                    {{-- Timeline --}}
                    <h6 class="mb-3">@lang('Tracking History')</h6>
                    <div class="timeline-user">
                        @foreach($histories[$tracking->merchant_id] ?? [] as $event)
                            <div class="timeline-item-user">
                                <div class="timeline-marker-user bg-{{ $event['status_color'] }}">
                                    <i class="{{ $event['status_icon'] }}"></i>
                                </div>
                                <div class="timeline-content-user">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <span class="badge badge-{{ $event['status_color'] }}">
                                                {{ $event['status_ar'] }}
                                            </span>
                                            @if($event['message_ar'] ?? $event['message'])
                                                <p class="mb-1 mt-1">{{ $event['message_ar'] ?? $event['message'] }}</p>
                                            @endif
                                            @if($event['location'])
                                                <small class="text-muted">
                                                    <i class="fas fa-map-marker-alt"></i> {{ $event['location'] }}
                                                </small>
                                            @endif
                                        </div>
                                        <small class="text-muted">
                                            {{ $event['occurred_at'] ? \Carbon\Carbon::parse($event['occurred_at'])->format('d/m H:i') : '' }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
.timeline-user {
    position: relative;
    padding-left: 30px;
}
.timeline-user::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: var(--border-default, #dee2e6);
}
.timeline-item-user {
    position: relative;
    padding-bottom: 15px;
}
.timeline-marker-user {
    position: absolute;
    left: -30px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 9px;
}
.timeline-content-user {
    padding: 8px 12px;
    background: var(--bg-secondary, #f8f9fa);
    border-radius: 5px;
}
</style>
@endsection
