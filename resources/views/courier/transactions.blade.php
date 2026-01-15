@extends('layouts.front')

@section('content')
    <div class="gs-user-panel-review wow-replaced" data-wow-delay=".1s">
        <div class="container">
            <div class="d-flex">
                <!-- sidebar -->
                @include('includes.courier.sidebar')
                <!-- main content -->
                <div class="gs-dashboard-user-content-wrapper gs-dashboard-outlet">
                    <div class="ud-page-name-box">
                        <h3 class="ud-page-name">@lang('Delivery History')</h3>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-3 col-md-6">
                            <div class="account-info-box text-center">
                                <h6>@lang('Total Deliveries')</h6>
                                <h4>{{ $report['deliveries_count'] ?? 0 }}</h4>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="account-info-box text-center">
                                <h6>@lang('Completed')</h6>
                                <h4 class="text-success">{{ $report['deliveries_completed'] ?? 0 }}</h4>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="account-info-box text-center">
                                <h6>@lang('COD Collected')</h6>
                                <h4 class="text-warning">{{ $currency->sign ?? 'SAR ' }}{{ number_format($report['total_cod_collected'] ?? 0, 2) }}</h4>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                            <div class="account-info-box text-center">
                                <h6>@lang('Fees Earned')</h6>
                                <h4 class="text-primary">{{ $currency->sign ?? 'SAR ' }}{{ number_format($report['total_delivery_fees'] ?? 0, 2) }}</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Filter -->
                    <div class="mb-4">
                        <form action="{{ route('courier-transactions') }}" method="GET" class="d-flex gap-2 flex-wrap">
                            <select name="status" class="form-select" style="max-width: 200px;">
                                <option value="">@lang('All Status')</option>
                                <option value="pending_approval" {{ request('status') == 'pending_approval' ? 'selected' : '' }}>@lang('Pending Approval')</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>@lang('Approved')</option>
                                <option value="picked_up" {{ request('status') == 'picked_up' ? 'selected' : '' }}>@lang('Picked Up')</option>
                                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>@lang('Delivered')</option>
                                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>@lang('Confirmed')</option>
                            </select>
                            <button type="submit" class="btn btn-primary">@lang('Filter')</button>
                            <a href="{{ route('courier-transactions') }}" class="btn btn-secondary">@lang('Reset')</a>
                        </form>
                    </div>

                    <!-- Deliveries Table -->
                    <div class="user-table recent-orders-table table-responsive wow-replaced" data-wow-delay=".1s">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th><span class="header-name">{{ __('#') }}</span></th>
                                    <th><span class="header-name">{{ __('Purchase') }}</span></th>
                                    <th><span class="header-name">{{ __('Payment') }}</span></th>
                                    <th><span class="header-name">{{ __('Amount') }}</span></th>
                                    <th><span class="header-name">{{ __('Fee') }}</span></th>
                                    <th><span class="header-name">{{ __('Status') }}</span></th>
                                    <th><span class="header-name">{{ __('Date') }}</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($deliveries as $key => $delivery)
                                    <tr>
                                        <td data-label="{{ __('#') }}">
                                            {{ $deliveries->firstItem() + $key }}
                                        </td>
                                        <td data-label="{{ __('Purchase') }}">
                                            @if($delivery->purchase)
                                                <a href="{{ route('courier-purchase-details', $delivery->id) }}">
                                                    {{ $delivery->purchase->purchase_number }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td data-label="{{ __('Payment') }}">
                                            @if($delivery->payment_method === 'cod')
                                                <span class="badge bg-warning">@lang('COD')</span>
                                            @else
                                                <span class="badge bg-success">@lang('Online')</span>
                                            @endif
                                        </td>
                                        <td data-label="{{ __('Amount') }}">
                                            {{ $currency->sign ?? 'SAR ' }}{{ number_format($delivery->purchase_amount ?? 0, 2) }}
                                        </td>
                                        <td data-label="{{ __('Fee') }}">
                                            <span class="text-success">{{ $currency->sign ?? 'SAR ' }}{{ number_format($delivery->delivery_fee ?? 0, 2) }}</span>
                                        </td>
                                        <td data-label="{{ __('Status') }}">
                                            @if($delivery->isDelivered() || $delivery->isConfirmed())
                                                <span class="badge bg-success">@lang('Delivered')</span>
                                            @elseif($delivery->isPickedUp())
                                                <span class="badge bg-primary">@lang('In Transit')</span>
                                            @elseif($delivery->isReadyForPickup())
                                                <span class="badge bg-info">@lang('Ready')</span>
                                            @elseif($delivery->isApproved())
                                                <span class="badge bg-secondary">@lang('Preparing')</span>
                                            @elseif($delivery->isPendingApproval())
                                                <span class="badge bg-warning">@lang('Pending')</span>
                                            @elseif($delivery->isRejected())
                                                <span class="badge bg-danger">@lang('Rejected')</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $delivery->status }}</span>
                                            @endif
                                        </td>
                                        <td data-label="{{ __('Date') }}">
                                            {{ $delivery->created_at->format('d-m-Y H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">{{ __('No deliveries found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $deliveries->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
