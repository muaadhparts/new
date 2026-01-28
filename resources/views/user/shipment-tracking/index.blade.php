@extends('layouts.user')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">@lang('My Shipments')</h4>
    </div>
    <div class="card-body">
        @if($shipments->count() > 0)
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>@lang('Purchase')</th>
                            <th>@lang('Tracking #')</th>
                            <th>@lang('Merchant')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Last Update')</th>
                            <th>@lang('Actions')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shipments as $shipment)
                            <tr>
                                <td>
                                    <strong>{{ $shipment->purchase->purchase_number ?? 'N/A' }}</strong>
                                    <br>
                                    <small class="text-muted">
                                        {{ $shipment->purchase->pay_amount_formatted ?? '-' }}
                                    </small>
                                </td>
                                <td>
                                    @if($shipment->tracking_number)
                                        <code>{{ $shipment->tracking_number }}</code>
                                    @else
                                        <span class="text-muted">@lang('Pending')</span>
                                    @endif
                                </td>
                                <td>{{ $shipment->merchant->shop_name ?? $shipment->merchant->name ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-{{ $shipment->status_color }}">
                                        <i class="{{ $shipment->status_icon }}"></i>
                                        {{ $shipment->status_ar }}
                                    </span>
                                </td>
                                <td>
                                    {{ $shipment->occurred_at ? $shipment->occurred_at->diffForHumans() : '-' }}
                                </td>
                                <td>
                                    <a href="{{ route('user.shipment-tracking.show', $shipment->purchase_id) }}"
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> @lang('Details')
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-4"></i>
                <h5>@lang('No Shipments Yet')</h5>
                <p class="text-muted">
                    @lang('Your shipment tracking will appear here once your orders are shipped.')
                </p>
                <a href="{{ route('user-purchases') }}" class="btn btn-primary">
                    <i class="fas fa-shopping-bag"></i> @lang('View Purchases')
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
