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
                        <h3 class="ud-page-name">@lang('Financial Report')</h3>
                    </div>

                    <!-- Date Filter -->
                    <div class="mb-4">
                        <form action="{{ route('courier-financial-report') }}" method="GET" class="d-flex gap-2 flex-wrap align-items-end">
                            <div>
                                <label class="form-label small">@lang('From Date')</label>
                                <input type="date" name="start_date" class="form-control" value="{{ $startDate ?? '' }}">
                            </div>
                            <div>
                                <label class="form-label small">@lang('To Date')</label>
                                <input type="date" name="end_date" class="form-control" value="{{ $endDate ?? '' }}">
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary">@lang('Filter')</button>
                                <a href="{{ route('courier-financial-report') }}" class="btn btn-secondary">@lang('Reset')</a>
                            </div>
                        </form>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-4 col-md-6">
                            <div class="account-info-box text-center {{ ($report['current_balance'] ?? 0) < 0 ? 'border-danger' : (($report['current_balance'] ?? 0) > 0 ? 'border-success' : '') }}">
                                <h6>@lang('Current Balance')</h6>
                                <h3 class="{{ ($report['current_balance'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $currency->sign ?? 'SAR ' }}{{ number_format($report['current_balance'] ?? 0, 2) }}
                                </h3>
                                @if(($report['is_in_debt'] ?? false))
                                    <span class="badge bg-danger">@lang('You owe to platform')</span>
                                @elseif(($report['has_credit'] ?? false))
                                    <span class="badge bg-success">@lang('Platform owes you')</span>
                                @else
                                    <span class="badge bg-secondary">@lang('Balanced')</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="account-info-box text-center">
                                <h6>@lang('Total COD Collected')</h6>
                                <h3 class="text-warning">{{ $currency->sign ?? 'SAR ' }}{{ number_format($report['total_collected'] ?? 0, 2) }}</h3>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="account-info-box text-center">
                                <h6>@lang('Total Fees Earned')</h6>
                                <h3 class="text-success">{{ $currency->sign ?? 'SAR ' }}{{ number_format($report['total_fees_earned'] ?? 0, 2) }}</h3>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Stats -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-6">
                            <div class="account-info-box">
                                <h5>@lang('Delivery Statistics')</h5>
                                <table class="table table-borderless mb-0">
                                    <tr>
                                        <td>@lang('Total Deliveries')</td>
                                        <td class="text-end"><strong>{{ $report['deliveries_count'] ?? 0 }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>@lang('Completed Deliveries')</td>
                                        <td class="text-end"><span class="badge bg-success">{{ $report['deliveries_completed'] ?? 0 }}</span></td>
                                    </tr>
                                    <tr>
                                        <td>@lang('Pending Deliveries')</td>
                                        <td class="text-end"><span class="badge bg-warning">{{ $report['deliveries_pending'] ?? 0 }}</span></td>
                                    </tr>
                                    <tr>
                                        <td>@lang('Unsettled Deliveries')</td>
                                        <td class="text-end"><span class="badge bg-danger">{{ $report['unsettled_deliveries'] ?? 0 }}</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="account-info-box">
                                <h5>@lang('Payment Method Breakdown')</h5>
                                <table class="table table-borderless mb-0">
                                    <tr>
                                        <td>@lang('COD Deliveries')</td>
                                        <td class="text-end"><span class="badge bg-warning">{{ $report['cod_deliveries'] ?? 0 }}</span></td>
                                    </tr>
                                    <tr>
                                        <td>@lang('Online Payment Deliveries')</td>
                                        <td class="text-end"><span class="badge bg-info">{{ $report['online_deliveries'] ?? 0 }}</span></td>
                                    </tr>
                                    <tr>
                                        <td>@lang('Total Delivery Fees')</td>
                                        <td class="text-end"><strong>{{ $currency->sign ?? 'SAR ' }}{{ number_format($report['total_delivery_fees'] ?? 0, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>@lang('Total COD Amount')</td>
                                        <td class="text-end"><strong>{{ $currency->sign ?? 'SAR ' }}{{ number_format($report['total_cod_collected'] ?? 0, 2) }}</strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="account-info-box">
                                <h5>@lang('Financial Summary')</h5>
                                <table class="table table-bordered">
                                    <tr>
                                        <td width="60%">@lang('Total COD Collected (You owe to Platform)')</td>
                                        <td class="text-end text-danger"><strong>{{ $currency->sign ?? 'SAR ' }}{{ number_format($report['total_collected'] ?? 0, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>@lang('Total Delivery Fees Earned (Platform owes to You)')</td>
                                        <td class="text-end text-success"><strong>{{ $currency->sign ?? 'SAR ' }}{{ number_format($report['total_fees_earned'] ?? 0, 2) }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>@lang('Amount Settled/Delivered to Platform')</td>
                                        <td class="text-end"><strong>{{ $currency->sign ?? 'SAR ' }}{{ number_format($report['total_delivered'] ?? 0, 2) }}</strong></td>
                                    </tr>
                                    <tr class="table-active">
                                        <td><strong>@lang('Current Balance')</strong></td>
                                        <td class="text-end {{ ($report['current_balance'] ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                            <strong>{{ $currency->sign ?? 'SAR ' }}{{ number_format($report['current_balance'] ?? 0, 2) }}</strong>
                                            @if(($report['current_balance'] ?? 0) < 0)
                                                <br><small>(@lang('You need to pay this amount to platform'))</small>
                                            @elseif(($report['current_balance'] ?? 0) > 0)
                                                <br><small>(@lang('Platform will pay this amount to you'))</small>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="row g-4 mt-2">
                        <div class="col-lg-6">
                            <a href="{{ route('courier-transactions') }}" class="btn btn-outline-primary w-100">
                                <i class="fas fa-list me-2"></i>@lang('View All Transactions')
                            </a>
                        </div>
                        <div class="col-lg-6">
                            <a href="{{ route('courier-settlements') }}" class="btn btn-outline-success w-100">
                                <i class="fas fa-hand-holding-usd me-2"></i>@lang('View Settlements')
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
